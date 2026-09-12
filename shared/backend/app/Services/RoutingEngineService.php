<?php

namespace App\Services;

use App\Models\Hub;
use App\Models\Order;
use App\Models\OrderRoutePlan;

class RoutingEngineService
{
    public function __construct(private AddressParserService $addressParser) {}

    public function resolve(Order $order): array
    {
        $regional = $this->addressParser->parse($order->recipient_address);
        $parent = $regional->parentHub;
        $gateway = $parent?->type === 'gateway' ? $parent : ($parent?->parentHub ?? $parent);
        $national = $parent?->type === 'national_sorting' ? $parent : ($gateway?->parentHub?->type === 'national_sorting' ? $gateway->parentHub : Hub::where('type', 'national_sorting')->orderBy('id')->first());
        $steps = collect([$order->hub, $national, $gateway, $regional])->filter()->unique('id')->map(fn (Hub $hub) => ['id' => $hub->id, 'name' => $hub->name, 'type' => $hub->type])->values()->all();

        $plan = OrderRoutePlan::updateOrCreate(['order_id' => $order->id], [
            'national_hub_id' => $national?->id,
            'gateway_hub_id' => $gateway?->id,
            'regional_hub_id' => $regional->id,
            'route_path_json' => $steps,
        ]);

        return ['plan' => $plan->load('regionalHub'), 'regional_hub' => $regional, 'next_destination_hub_id' => $regional->id, 'route_path' => $steps];
    }
}