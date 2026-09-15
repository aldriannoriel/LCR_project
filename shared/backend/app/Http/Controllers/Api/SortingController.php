<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderRoutedToBin;
use App\Http\Controllers\Controller;
use App\Models\Bin;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\BinAssignment;
use App\Services\BinAssignmentService;
use App\Services\RoutingEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SortingController extends Controller
{
    public function autoSort(Request $request, RoutingEngineService $routing, BinAssignmentService $bins)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate(['order_ids' => ['nullable', 'array', 'min:1'], 'order_ids.*' => ['integer', 'exists:orders,id']]);
        $orders = Order::with('hub.parentHub')->where('status', 'received')
            ->when($validated['order_ids'] ?? null, fn ($query, $ids) => $query->whereIn('id', $ids))
            ->get();
        $sorted = [];
        $skipped = [];

        foreach ($orders as $order) {
            try {
                $route = $routing->resolve($order);
                $assignment = $bins->assign($order, $route['next_destination_hub_id'], $request->user()->id);
                $order->update(['status' => 'in_transit']);
                try { broadcast(new OrderRoutedToBin($assignment)); } catch (\Throwable $exception) { Log::warning('Sorting broadcast unavailable', ['error' => $exception->getMessage()]); }
                $sorted[] = ['awb_number' => $order->awb_number, 'destination_hub' => $route['regional_hub']->name, 'bin_code' => $assignment->bin->bin_code, 'route_path' => $route['route_path']];
            } catch (\Throwable $exception) {
                $skipped[] = ['awb_number' => $order->awb_number, 'reason' => 'No matching active bin or destination.'];
            }
        }

        return response()->json(['message' => count($sorted).' orders routed automatically.', 'sorted' => $sorted, 'skipped' => $skipped]);
    }

    public function routeAndBin(Request $request, RoutingEngineService $routing, BinAssignmentService $bins)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate(['awb_number' => ['required', 'string']]);
        $order = Order::where('awb_number', $validated['awb_number'])->first();
        if (! $order) return response()->json(['message' => 'AWB number was not found.'], 404);

        try {
            $route = $routing->resolve($order->load('hub.parentHub'));
            $assignment = $bins->assign($order, $route['next_destination_hub_id'], $request->user()->id);
            try { broadcast(new OrderRoutedToBin($assignment)); } catch (\Throwable $exception) { Log::warning('Sorting broadcast unavailable', ['error' => $exception->getMessage()]); }
        } catch (\Illuminate\Database\RecordsNotFoundException) {
            return response()->json(['message' => 'No active bin is available for this destination.'], 422);
        }

        return response()->json(['message' => 'Order routed successfully.', 'order' => $order->load('hub'), 'route' => $route, 'assignment' => $assignment]);
    }

    public function sortScan(Request $request)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate([
            'awb_number' => ['required', 'string'],
            'bin_id' => ['required', 'integer', 'exists:bins,id'],
        ]);

        $result = DB::transaction(function () use ($validated, $request) {
            $order = Order::where('awb_number', $validated['awb_number'])->lockForUpdate()->firstOrFail();
            $bin = Bin::lockForUpdate()->findOrFail($validated['bin_id']);
            $currentHubId = $order->current_hub_id ?: $order->hub_id;

            abort_if($bin->hub_id !== $currentHubId, 422, 'The selected bin is not at the parcel\'s current hub.');
            abort_if($bin->status !== 'active' || $bin->current_count >= $bin->capacity, 422, 'The selected bin is not available.');
            abort_if(! in_array($order->status, ['received', 'in_transit']), 422, 'This parcel is not ready for sorting.');

            $assignment = BinAssignment::firstOrCreate([
                'bin_id' => $bin->id,
                'order_id' => $order->id,
            ], [
                'scanned_by_user_id' => $request->user()->id,
                'assigned_at' => now(),
            ]);

            if ($assignment->wasRecentlyCreated) {
                $bin->increment('current_count');
                $bin->refresh();
                if ($bin->current_count >= $bin->capacity) $bin->update(['status' => 'full']);
            }

            $order->update(['status' => 'sorted']);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'sorted',
                'location_hub_id' => $currentHubId,
                'performed_by_user_id' => $request->user()->id,
                'notes' => 'Parcel placed in bin '.$bin->bin_code.'.',
            ]);

            return [$order, $assignment->load('bin.targetHub')];
        });

        return response()->json([
            'message' => 'Parcel sorted into bin.',
            'order' => $result[0]->fresh()->load(['hub', 'currentHub', 'statusHistories']),
            'assignment' => $result[1],
        ]);
    }

    public function bins(Request $request)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $request->validate(['hub_id' => ['required', 'integer', 'exists:hubs,id']]);
        return response()->json(Bin::with('targetHub')->where('hub_id', $request->integer('hub_id'))->orderBy('bin_code')->get());
    }
}