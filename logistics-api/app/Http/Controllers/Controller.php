<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function requireAnyRole(Request $request, array $roles): void
    {
        abort_unless($request->user()?->hasAnyRole($roles), 403, 'You are not authorized to perform this action.');
    }
}
