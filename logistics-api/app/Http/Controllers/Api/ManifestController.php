<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TransferManifest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManifestController extends Controller
{
    public function ready(Request $request)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $orders = Order::with(['routePlan.regionalHub', 'hub'])->whereHas('routePlan')->whereDoesntHave('manifests')->get();
        return response()->json($orders->groupBy(fn (Order $order) => $order->routePlan->regional_hub_id)->map(fn ($group) => ['destination_hub' => $group->first()->routePlan->regionalHub, 'orders' => $group->values()]));
    }

    public function generate(Request $request)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate(['order_ids' => ['required', 'array', 'min:1'], 'order_ids.*' => ['integer', 'exists:orders,id'], 'destination_hub_id' => ['required', 'integer', 'exists:hubs,id']]);
        $orders = Order::with('hub')->whereIn('id', $validated['order_ids'])->get();
        abort_if($orders->isEmpty(), 422, 'Select at least one order.');
        abort_if($orders->contains(fn (Order $order) => $order->manifests()->exists()), 422, 'One or more selected orders are already assigned to a manifest.');
        abort_if($orders->pluck('hub_id')->unique()->count() !== 1, 422, 'Selected orders must share one origin hub.');
        $manifest = TransferManifest::create(['manifest_number' => 'TM-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)), 'origin_hub_id' => $orders->first()->hub_id, 'destination_hub_id' => $validated['destination_hub_id'], 'status' => 'draft', 'total_orders' => $orders->count(), 'generated_by' => $request->user()->id]);
        $manifest->orders()->attach($orders->modelKeys());
        $pdf = Pdf::loadView('manifests.transfer', ['manifest' => $manifest->load(['originHub', 'destinationHub']), 'orders' => $orders])->setPaper('a4');
        return $pdf->stream($manifest->manifest_number.'.pdf');
    }
}