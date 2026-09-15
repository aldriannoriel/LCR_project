<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function requireAnyRole(Request $request, array $roles): void
    {
        $user = $request->user();
        $authorized = $user?->hasAnyRole($roles);

        // Courier Admin uses the shared operations APIs without becoming a rider.
        if (! $authorized && in_array('Admin', $roles, true)) {
            $authorized = $user?->hasRole('Courier Admin');
        }

        abort_unless($authorized, 403, 'You are not authorized to perform this action.');
    }
}
