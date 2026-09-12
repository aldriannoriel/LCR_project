<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        return response()->json($request->user()->load(['roles', 'hub', 'rider.performance']));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_initial' => 'nullable|string|max:10',
            'phone_number' => 'required|string|max:30',
            'province' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:500',
            'business_name' => 'nullable|string|max:255',
        ]);

        $middle = $validated['middle_initial'] ?? null;
        $fullName = trim("{$validated['first_name']} " . ($middle ? "{$middle} " : '') . "{$validated['last_name']}");

        $user->update([
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_initial' => $middle,
            'phone_number' => $validated['phone_number'],
            'province' => $validated['province'] ?? $user->province,
            'city_municipality' => $validated['city_municipality'] ?? $user->city_municipality,
            'barangay' => $validated['barangay'] ?? $user->barangay,
            'street_address' => $validated['street_address'] ?? $user->street_address,
            'business_name' => $validated['business_name'] ?? $user->business_name,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh()->load('roles', 'hub'),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The provided current password does not match our records.',
                'errors' => [
                    'current_password' => ['Incorrect current password.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
