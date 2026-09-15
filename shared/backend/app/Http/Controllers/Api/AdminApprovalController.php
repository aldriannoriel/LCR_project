<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager']);

        $status = $request->query('status', 'pending');
        $search = $request->query('search');

        $query = User::query()
            ->with('roles')
            ->when($status !== 'all', function ($q) use ($status) {
                $q->where('approval_status', $status);
            })
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->latest('id');

        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function show(User $user)
    {
        $this->requireAnyRole(request(), ['Admin', 'Super Admin', 'Hub Manager']);

        return response()->json($user->load('roles', 'hub'));
    }

    public function approve(Request $request, User $user)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager']);

        $user->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        if ($user->rider) {
            $user->rider->update([
                'application_status' => 'approved',
                'status' => 'available',
                'rejection_reason' => null,
            ]);
        }

        try {
            Mail::to($user->email)->send(new AccountApprovedMail($user));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => 'User account approved successfully.',
            'user' => $user->fresh(),
        ]);
    }

    public function reject(Request $request, User $user)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager']);

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:1000',
        ]);

        $user->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $validated['reason'],
        ]);

        if ($user->rider) {
            $user->rider->update([
                'application_status' => 'rejected',
                'status' => 'suspended',
                'rejection_reason' => $validated['reason'],
            ]);
        }

        try {
            Mail::to($user->email)->send(new AccountRejectedMail($user, $validated['reason']));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => 'User account rejected successfully.',
            'user' => $user->fresh(),
        ]);
    }

    public function viewDocument(Request $request, User $user, string $type)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager']);

        $path = match ($type) {
            'id', 'id_document' => $user->id_document_path,
            'permit', 'business_permit' => $user->business_permit_path,
            default => null,
        };

        if (! $path) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            // Also check absolute path or public fallback
            if (file_exists($path)) {
                return response()->file($path);
            }
            $fullPath = storage_path('app/private/' . ltrim($path, '/'));
            if (file_exists($fullPath)) {
                return response()->file($fullPath);
            }
            return response()->json(['message' => 'Document file does not exist on disk.'], 404);
        }

        return $disk->response($path);
    }
}

