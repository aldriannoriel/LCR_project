<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SortingController;
use App\Http\Controllers\Api\ManifestController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\HubController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Models\Hub;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return $request->user()->load('roles', 'hub');
    });
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/hubs', fn () => response()->json(Hub::whereIn('code', [
        'HUB-LUZ-CTR', 'HUB-LUZ-NL', 'HUB-LUZ-SL',
        'HUB-VIS-GW', 'HUB-VIS-CEB', 'HUB-VIS-ILO',
        'HUB-MIN-CG', 'HUB-MIN-DVO', 'HUB-MIN-CDO',
    ])->orderBy('name')->get(['id', 'name', 'code'])));
    Route::get('/archipelagos', fn () => response()->json(\App\Models\Archipelago::orderBy('name')->get(['id', 'name', 'code'])));
    Route::post('/orders/intake', [OrderController::class, 'intake']);
    Route::post('/orders/confirm-arrivals', [OrderController::class, 'confirmArrivals']);
    Route::post('/orders/{order}/override', [OrderController::class, 'override']);
    Route::post('/orders/route-and-bin', [SortingController::class, 'routeAndBin']);
    Route::post('/orders/auto-sort', [SortingController::class, 'autoSort']);
    Route::get('/bins', [SortingController::class, 'bins']);
    Route::get('/manifests/ready', [ManifestController::class, 'ready']);
    Route::post('/manifests/generate', [ManifestController::class, 'generate']);
    Route::apiResource('riders', RiderController::class)->only(['index', 'store', 'update']);
    Route::patch('/riders/{rider}/status', [RiderController::class, 'status']);
    Route::get('/riders/{rider}/details', [RiderController::class, 'details']);
    Route::post('/riders/assign-orders', [RiderController::class, 'assignOrders']);
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
});