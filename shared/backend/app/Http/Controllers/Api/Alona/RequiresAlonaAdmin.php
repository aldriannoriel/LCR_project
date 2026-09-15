<?php

namespace App\Http\Controllers\Api\Alona;

use Illuminate\Http\Request;

trait RequiresAlonaAdmin
{
    protected function requireAlonaAdmin(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole([
            'Admin', 'Super Admin', 'Logistics Admin',
            'admin', 'super_admin', 'logistics_admin',
        ]), 403, 'Logistics administrator access is required.');
    }
}
