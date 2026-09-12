<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationPendingMail;
use App\Models\Hub;
use App\Models\Rider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'account_type' => 'required|in:logistics,courier',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_initial' => 'nullable|string|max:10',
            'sex' => 'required|string|in:male,female,other',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone_number' => 'required|string|max:30',
            'birthdate' => 'required|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            'password' => 'required|string|min:8|confirmed',
            'province' => 'required|string|max:255',
            'city_municipality' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'street_address' => 'required|string|max:500',
            'business_name' => 'nullable|string|max:255',
            'id_document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'business_permit' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            // Courier-only fields
            'vehicle_type' => 'required_if:account_type,courier|nullable|in:motorcycle,tricycle,van,truck',
            'plate_number' => 'nullable|string|max:50',
            'license_number' => 'required_if:account_type,courier|nullable|string|max:50',
            'license_doc' => 'required_if:account_type,courier|nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'vehicle_or_cr' => 'required_if:account_type,courier|nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'hub_id' => 'required_if:account_type,courier|nullable|exists:hubs,id',
        ], [
            'birthdate.before_or_equal' => 'You must be at least 18 years old to register.',
            'id_document.required' => 'A valid government-issued ID is required for verification.',
            'id_document.max' => 'The ID document must not exceed 5MB.',
            'business_permit.max' => 'The business permit must not exceed 5MB.',
            'vehicle_type.required_if' => 'Please select a vehicle type.',
            'license_number.required_if' => 'Driver license number is required for couriers.',
            'license_doc.required_if' => 'Driver license document is required for couriers.',
            'vehicle_or_cr.required_if' => 'Vehicle OR/CR document is required for couriers.',
            'hub_id.required_if' => 'Please select your assigned hub.',
        ]);

        $birthDate = Carbon::parse($validated['birthdate']);
        $age = $birthDate->age;

        if ($age < 18) {
            throw ValidationException::withMessages([
                'birthdate' => ['You must be at least 18 years old to register.'],
            ]);
        }

        // Store private documents
        $idPath = $request->file('id_document')->store('documents', 'local');
        $permitPath = $request->hasFile('business_permit')
            ? $request->file('business_permit')->store('documents', 'local')
            : null;

        $fullName = trim("{$validated['first_name']} " . ($validated['middle_initial'] ? "{$validated['middle_initial']} " : '') . "{$validated['last_name']}");

        $result = DB::transaction(function () use ($request, $validated, $fullName, $birthDate, $age, $idPath, $permitPath) {
            $user = User::create([
                'name' => $fullName,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_initial' => $validated['middle_initial'] ?? null,
                'sex' => $validated['sex'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'birthdate' => $birthDate->toDateString(),
                'age' => $age,
                'province' => $validated['province'],
                'city_municipality' => $validated['city_municipality'],
                'barangay' => $validated['barangay'],
                'street_address' => $validated['street_address'],
                'business_name' => $validated['business_name'] ?? null,
                'id_document_path' => $idPath,
                'business_permit_path' => $permitPath,
                'password' => Hash::make($validated['password']),
                'approval_status' => 'pending',
            ]);

            if ($validated['account_type'] === 'courier') {
                // Store courier documents
                $licenseDoc = $request->file('license_doc')->store('documents', 'local');
                $orCrDoc = $request->file('vehicle_or_cr')->store('documents', 'local');

                Rider::create([
                    'user_id' => $user->id,
                    'hub_id' => $validated['hub_id'],
                    'vehicle_type' => $validated['vehicle_type'],
                    'plate_number' => $validated['plate_number'] ?? null,
                    'license_number' => $validated['license_number'],
                    'license_doc_path' => $licenseDoc,
                    'vehicle_or_cr_path' => $orCrDoc,
                    'phone_number' => $validated['phone_number'],
                    'status' => 'off_duty',
                    'application_status' => 'pending_review',
                ]);

                $riderRole = Role::whereIn('name', ['Rider', 'Courier', 'Driver'])->first();
                if ($riderRole) {
                    $user->assignRole($riderRole->name);
                }
            } else {
                // Logistics/Seller
                $defaultRole = Role::whereIn('name', ['Customer', 'Client', 'Seller', 'Merchant'])->first();
                if ($defaultRole) {
                    $user->assignRole($defaultRole->name);
                }
            }

            return $user;
        });

        // Send registration pending email notification
        try {
            Mail::to($result->email)->send(new RegistrationPendingMail($result));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => $validated['account_type'] === 'courier'
                ? 'Rider application submitted successfully. Your account is pending administrator review of your credentials.'
                : 'Registration submitted successfully. Your account is pending administrator approval.',
            'account_type' => $validated['account_type'],
            'user' => [
                'id' => $result->id,
                'email' => $result->email,
                'name' => $result->name,
                'approval_status' => $result->approval_status,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->approval_status !== 'approved') {
            return response()->json([
                'message' => 'Your registration is currently pending administrator approval. Please check your email for updates.',
                'approval_status' => $user->approval_status,
                'rejection_reason' => $user->rejection_reason,
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Load rider relation to determine redirect
        $user->load(['roles', 'hub', 'rider']);

        // Determine the appropriate redirect based on user type
        $redirect = $this->determineRedirect($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'redirect' => $redirect,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    private function determineRedirect($user): string
    {
        // If user has a rider record, they're a courier
        if ($user->rider) {
            return '/courier';
        }

        // Check roles
        $roleNames = $user->roles->pluck('name')->map(fn ($r) => strtolower($r))->toArray();

        if (in_array('admin', $roleNames) || in_array('super admin', $roleNames) || in_array('hub manager', $roleNames)) {
            return '/dashboard';
        }

        // Default to dashboard for sellers/customers
        return '/dashboard';
    }
}