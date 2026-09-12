<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminApprovalController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SortingController;
use App\Http\Controllers\Api\ManifestController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\HubController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PickupRequestController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CourierController;
use App\Models\Hub;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/registration/hubs', fn () => response()->json(Hub::whereIn('code', [
    'HUB-LUZ-CTR', 'HUB-LUZ-NL', 'HUB-LUZ-SL', 'HUB-LUZ-NCR',
    'HUB-VIS-GW', 'HUB-VIS-CEB', 'HUB-VIS-ILO', 'HUB-VIS-BCD',
    'HUB-MIN-CG', 'HUB-MIN-DVO', 'HUB-MIN-CDO', 'HUB-MIN-ZAM',
])->orderBy('name')->get(['id', 'name', 'code'])));

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return $request->user()->load('roles', 'hub', 'rider');
    });

    // Account & Profile Management
    Route::get('/user/profile', [ProfileController::class, 'getProfile']);
    Route::put('/user/profile', [ProfileController::class, 'updateProfile']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);

    // Admin Verification & Approval routes
    Route::get('/admin/users', [AdminApprovalController::class, 'index']);
    Route::get('/admin/users/{user}', [AdminApprovalController::class, 'show']);
    Route::post('/admin/users/{user}/approve', [AdminApprovalController::class, 'approve']);
    Route::post('/admin/users/{user}/reject', [AdminApprovalController::class, 'reject']);
    Route::get('/admin/users/{user}/documents/{type}', [AdminApprovalController::class, 'viewDocument']);

    // Parcel Pickup Requests (Seller -> Hub Dispatcher)
    Route::get('/pickup-requests', [PickupRequestController::class, 'index']);
    Route::post('/pickup-requests', [PickupRequestController::class, 'store']);
    Route::get('/pickup-requests/{pickupRequest}', [PickupRequestController::class, 'show']);
    Route::post('/pickup-requests/{pickupRequest}/verify', [PickupRequestController::class, 'verify']);
    Route::post('/pickup-requests/{pickupRequest}/assign-rider', [PickupRequestController::class, 'assignRider']);
    Route::post('/pickup-requests/{pickupRequest}/complete', [PickupRequestController::class, 'complete']);
    Route::post('/pickup-requests/{pickupRequest}/cancel', [PickupRequestController::class, 'cancel']);

    // Chat & Real-Time Messaging
    Route::get('/chat/contacts', [ChatController::class, 'contacts']);
    Route::get('/chat/conversations', [ChatController::class, 'conversations']);
    Route::post('/chat/conversations', [ChatController::class, 'startConversation']);
    Route::get('/chat/conversations/{conversation}/messages', [ChatController::class, 'messages']);
    Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);

    // Orders & Incoming Parcels
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/hubs', fn () => response()->json(Hub::whereIn('code', [
        'HUB-LUZ-CTR', 'HUB-LUZ-NL', 'HUB-LUZ-SL',
        'HUB-VIS-GW', 'HUB-VIS-CEB', 'HUB-VIS-ILO',
        'HUB-MIN-CG', 'HUB-MIN-DVO', 'HUB-MIN-CDO',
    ])->orderBy('name')->get(['id', 'name', 'code'])));
    Route::get('/archipelagos', fn () => response()->json(\App\Models\Archipelago::orderBy('name')->get(['id', 'name', 'code'])));
    Route::get('/coverage-areas', fn () => response()->json(\App\Models\CoverageArea::with('hub')->orderBy('province')->orderBy('city_municipality')->get()));
    Route::post('/orders/intake', [OrderController::class, 'intake']);
    Route::post('/orders/confirm-arrivals', [OrderController::class, 'confirmArrivals']);
    Route::post('/orders/{order}/override', [OrderController::class, 'override']);
    Route::post('/orders/route-and-bin', [SortingController::class, 'routeAndBin']);
    Route::post('/orders/auto-sort', [SortingController::class, 'autoSort']);
    Route::get('/bins', [SortingController::class, 'bins']);
    Route::get('/manifests/ready', [ManifestController::class, 'ready']);
    Route::post('/manifests/generate', [ManifestController::class, 'generate']);

    // Riders & Fleet Management
    Route::apiResource('riders', RiderController::class)->only(['index', 'store', 'update']);
    Route::patch('/riders/{rider}/status', [RiderController::class, 'status']);
    Route::patch('/riders/{rider}/toggle-active', [RiderController::class, 'toggleActive']);
    Route::post('/riders/{rider}/approve-application', [RiderController::class, 'approveApplication']);
    Route::post('/riders/{rider}/reject-application', [RiderController::class, 'rejectApplication']);
    Route::get('/riders/{rider}/details', [RiderController::class, 'details']);
    Route::post('/riders/assign-orders', [RiderController::class, 'assignOrders']);

    // Hubs, Transfers, Returns & Reports
    Route::get('/hubs/grid', [HubController::class, 'grid']);
    Route::get('/hubs/{hub}/inventory', [HubController::class, 'inventory']);
    Route::get('/transfer-requests', [HubController::class, 'requests']);
    Route::post('/transfer-requests', [HubController::class, 'createRequest']);
    Route::patch('/transfer-requests/{transferRequest}/status', [HubController::class, 'status']);
    Route::get('/returns', [ReturnController::class, 'index']);
    Route::post('/returns/intake', [ReturnController::class, 'intake']);
    Route::post('/returns/{return}/action', [ReturnController::class, 'action']);
    Route::get('/orders/{order}/history', [ReturnController::class, 'history']);
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics']);
    Route::get('/reports/analytics', [ReportController::class, 'analytics']);
    Route::get('/reports/export/csv', [ReportController::class, 'csv']);
    Route::get('/reports/export/pdf', [ReportController::class, 'pdf']);

    // ─── Courier / Rider App ─────────────────────────────────────────────────
    Route::prefix('courier')->group(function () {
        Route::get('/dashboard', [CourierController::class, 'dashboard']);

        // Pickup management
        Route::get('/pickups', [CourierController::class, 'pickups']);
        Route::get('/pickups/{pickup}', [CourierController::class, 'pickupDetail']);
        Route::post('/pickups/{pickup}/start', [CourierController::class, 'startPickup']);
        Route::post('/pickups/{pickup}/complete', [CourierController::class, 'completePickup']);

        // Delivery management
        Route::get('/deliveries', [CourierController::class, 'deliveries']);
        Route::get('/deliveries/{order}', [CourierController::class, 'deliveryDetail']);
        Route::post('/deliveries/{order}/start', [CourierController::class, 'startDelivery']);
        Route::post('/deliveries/{order}/complete', [CourierController::class, 'completeDelivery']);
        Route::post('/deliveries/{order}/failed', [CourierController::class, 'failedDelivery']);

        // Earnings & History
        Route::get('/earnings', [CourierController::class, 'earnings']);
        Route::get('/history', [CourierController::class, 'history']);

        // Status & Profile
        Route::post('/status', [CourierController::class, 'toggleStatus']);
        Route::get('/profile', [CourierController::class, 'profile']);
        Route::put('/profile', [CourierController::class, 'updateProfile']);
        Route::put('/password', [CourierController::class, 'changePassword']);
    });
});