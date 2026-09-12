<?php

namespace Tests\Feature;

use App\Models\Archipelago;
use App\Models\Hub;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class OrderIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_scan_is_idempotent_and_second_scan_is_rejected(): void
    {
        $archipelago = Archipelago::create(['name' => 'Test', 'code' => 'TST']);
        $hub = Hub::create(['name' => 'Test Hub', 'code' => 'HUB-TST', 'type' => 'regional', 'hub_type' => 'regional', 'archipelago_id' => $archipelago->id, 'capacity' => 100]);
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'Dispatcher', 'guard_name' => 'web']);
        $user->assignRole('Dispatcher');
        $order = Order::create(['awb_number' => 'TEST-INTAKE-1', 'hub_id' => $hub->id, 'status' => 'pending', 'sender_name' => 'Sender', 'recipient_name' => 'Recipient', 'recipient_address' => 'Address', 'weight_kg' => 1]);

        $this->actingAs($user, 'sanctum')->postJson('/api/orders/intake', ['awb_number' => $order->awb_number, 'hub_id' => $hub->id])->assertOk();
        $this->actingAs($user, 'sanctum')->postJson('/api/orders/intake', ['awb_number' => $order->awb_number, 'hub_id' => $hub->id])->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'received']);
        $this->assertSame(1, Order::where('awb_number', $order->awb_number)->count());
    }
}