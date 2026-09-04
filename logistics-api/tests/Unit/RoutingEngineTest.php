<?php

namespace Tests\Unit;

use App\Models\Archipelago;
use App\Models\CoverageArea;
use App\Models\Hub;
use App\Models\Order;
use App\Services\AddressParserService;
use App\Services\RoutingEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_parser_matches_city_case_insensitively_and_fuzzily(): void
    {
        [$regional] = $this->createHierarchy('Cebu Regional Hub', 'HUB-CEB', 'Cebu');
        CoverageArea::create(['province' => 'Cebu', 'city_municipality' => 'Cebu City', 'hub_id' => $regional->id]);

        $this->assertSame($regional->id, app(AddressParserService::class)->parse('Unit 4, CEBU CITY, Cebu!')->id);
    }

    public function test_routing_builds_national_gateway_regional_path(): void
    {
        [$regional, $national, $gateway] = $this->createHierarchy('Davao Regional Hub', 'HUB-DVO', 'Davao');
        CoverageArea::create(['province' => 'Davao del Sur', 'city_municipality' => 'Davao City', 'hub_id' => $regional->id]);
        $order = Order::create(['awb_number' => 'TEST-ROUTE-1', 'hub_id' => $regional->id, 'current_hub_id' => $regional->id, 'status' => 'pending', 'sender_name' => 'A', 'recipient_name' => 'B', 'recipient_address' => 'Davao City', 'weight_kg' => 1]);

        $result = app(RoutingEngineService::class)->resolve($order->load('hub.parentHub'));
        $this->assertSame([$regional->id, $national->id, $gateway->id], collect($result['route_path'])->pluck('id')->all());
        $this->assertSame($regional->id, $result['plan']->regional_hub_id);
    }

    private function createHierarchy(string $regionalName, string $regionalCode, string $city): array
    {
        $archipelago = Archipelago::create(['name' => $city, 'code' => strtoupper(substr($city, 0, 3))]);
        $national = Hub::create(['name' => 'National', 'code' => 'NAT-'.$regionalCode, 'type' => 'national_sorting', 'hub_type' => 'national', 'archipelago_id' => $archipelago->id, 'capacity' => 100]);
        $gateway = Hub::create(['name' => 'Gateway', 'code' => 'GW-'.$regionalCode, 'type' => 'gateway', 'hub_type' => 'gateway', 'archipelago_id' => $archipelago->id, 'parent_hub_id' => $national->id, 'capacity' => 100]);
        $regional = Hub::create(['name' => $regionalName, 'code' => $regionalCode, 'type' => 'regional', 'hub_type' => 'regional', 'archipelago_id' => $archipelago->id, 'parent_hub_id' => $gateway->id, 'capacity' => 100]);
        return [$regional, $national, $gateway];
    }
}