<?php

namespace App\Http\Controllers\Api\Alona;

use App\Http\Controllers\Controller;
use App\Models\AlonaAuditLog;
use App\Models\AlonaBulkManifest;
use App\Models\AlonaParcel;
use App\Models\AlonaRider;
use Illuminate\Http\Request;

class AlonaDashboardController extends Controller
{
    use RequiresAlonaAdmin;

    public function __invoke(Request $request)
    {
        $this->requireAlonaAdmin($request);
        return response()->json([
            'metrics' => [
                'pendingManifests' => AlonaBulkManifest::where('status', 'PENDING_APPROVAL')->count(),
                'activeRiders' => AlonaRider::whereIn('status', ['active', 'on_duty'])->count(),
                'outForDelivery' => AlonaParcel::where('status', 'OUT_FOR_DELIVERY')->count(),
                'failedDeliveries' => AlonaParcel::where('status', 'DELIVERY_FAILED')->count(),
            ],
            'activity' => AlonaAuditLog::with('actor')->latest('created_at')->limit(20)->get(),
        ]);
    }
}
