<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Hub;
use App\Models\PickupRequest;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PickupAndChatAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Client', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Rider', 'guard_name' => 'web']);
    }

    public function test_seller_can_create_pickup_request_and_admin_can_verify(): void
    {
        $seller = User::factory()->create(['approval_status' => 'approved']);
        $admin = User::factory()->create(['approval_status' => 'approved']);
        $admin->assignRole('Admin');

        $arch = \App\Models\Archipelago::create(['name' => 'Luzon', 'code' => 'LUZ']);
        $hub = Hub::create([
            'name' => 'Central Hub',
            'code' => 'HUB-TEST',
            'type' => 'regional',
            'hub_type' => 'regional',
            'archipelago_id' => $arch->id,
            'capacity' => 1000,
        ]);

        $response = $this->actingAs($seller, 'sanctum')->postJson('/api/pickup-requests', [
            'contact_person' => 'Seller Juan',
            'contact_number' => '09170001122',
            'province' => 'Metro Manila',
            'city_municipality' => 'Quezon City',
            'barangay' => 'Batasan Hills',
            'pickup_address' => '123 Market Street',
            'scheduled_date' => now()->toDateString(),
            'time_slot' => 'morning',
            'estimated_parcels' => 10,
            'package_type' => 'parcels',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending');

        $pickup = PickupRequest::first();
        $this->assertNotNull($pickup);

        // Admin verifies pickup
        $verifyRes = $this->actingAs($admin, 'sanctum')->postJson("/api/pickup-requests/{$pickup->id}/verify", [
            'hub_id' => $hub->id,
            'verification_notes' => 'Verified seller location and parcel quantity.',
        ]);

        $verifyRes->assertOk();
        $this->assertDatabaseHas('pickup_requests', [
            'id' => $pickup->id,
            'status' => 'verified',
            'hub_id' => $hub->id,
        ]);
    }

    public function test_admin_can_approve_or_reject_rider_application_and_toggle_active(): void
    {
        $admin = User::factory()->create(['approval_status' => 'approved']);
        $admin->assignRole('Admin');

        $arch = \App\Models\Archipelago::firstOrCreate(['code' => 'LUZ'], ['name' => 'Luzon']);
        $hub = Hub::create([
            'name' => 'Luzon Hub',
            'code' => 'HUB-LUZ',
            'type' => 'regional',
            'hub_type' => 'regional',
            'archipelago_id' => $arch->id,
            'capacity' => 1000,
        ]);

        $riderUser = User::factory()->create(['approval_status' => 'approved']);
        $rider = Rider::create([
            'user_id' => $riderUser->id,
            'hub_id' => $hub->id,
            'vehicle_type' => 'motorcycle',
            'plate_number' => 'ABC-1234',
            'status' => 'suspended',
            'application_status' => 'pending_review',
        ]);

        // Approve application
        $this->actingAs($admin, 'sanctum')->postJson("/api/riders/{$rider->id}/approve-application")
            ->assertOk();

        $this->assertDatabaseHas('riders', [
            'id' => $rider->id,
            'application_status' => 'approved',
            'status' => 'available',
        ]);

        // Toggle active (deactivate)
        $this->actingAs($admin, 'sanctum')->patchJson("/api/riders/{$rider->id}/toggle-active")
            ->assertOk();

        $this->assertDatabaseHas('riders', [
            'id' => $rider->id,
            'status' => 'suspended',
        ]);
    }

    public function test_users_can_start_conversation_and_send_messages(): void
    {
        $user1 = User::factory()->create(['approval_status' => 'approved', 'name' => 'Alice']);
        $user2 = User::factory()->create(['approval_status' => 'approved', 'name' => 'Bob']);

        $startRes = $this->actingAs($user1, 'sanctum')->postJson('/api/chat/conversations', [
            'recipient_id' => $user2->id,
        ]);

        $startRes->assertOk();
        $convId = $startRes->json('id');

        $msgRes = $this->actingAs($user1, 'sanctum')->postJson("/api/chat/conversations/{$convId}/messages", [
            'message' => 'Hello Bob, is the package ready for pickup?',
        ]);

        $msgRes->assertStatus(201)
            ->assertJsonPath('message', 'Hello Bob, is the package ready for pickup?');

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $convId,
            'sender_id' => $user1->id,
        ]);
    }

    public function test_user_can_update_profile_and_change_password(): void
    {
        $user = User::factory()->create([
            'approval_status' => 'approved',
            'password' => Hash::make('OldPassword123!'),
            'first_name' => 'Old',
            'last_name' => 'Name',
            'phone_number' => '09170000000',
        ]);

        $profileRes = $this->actingAs($user, 'sanctum')->putJson('/api/user/profile', [
            'first_name' => 'NewFirst',
            'last_name' => 'NewLast',
            'phone_number' => '09179998877',
            'province' => 'Cebu',
            'city_municipality' => 'Cebu City',
            'barangay' => 'Lahug',
            'street_address' => 'Salinas Drive',
        ]);

        $profileRes->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'NewFirst',
            'last_name' => 'NewLast',
            'phone_number' => '09179998877',
        ]);

        // Change password
        $pwRes = $this->actingAs($user, 'sanctum')->putJson('/api/user/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $pwRes->assertOk();
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }
}
