<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderRoutedToBin;
use App\Http\Controllers\Controller;
use App\Models\Bin;
use App\Models\Order;
use App\Services\BinAssignmentService;
use App\Services\RoutingEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SortingController extends Controller
{
    public function autoSort(Request $request, RoutingEngineService $routing, BinAssignmentService $bins)
    {
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

    public function bins(Request $request)
    {
        $request->validate(['hub_id' => ['required', 'integer', 'exists:hubs,id']]);
        return response()->json(Bin::with('targetHub')->where('hub_id', $request->integer('hub_id'))->orderBy('bin_code')->get());
    }
}