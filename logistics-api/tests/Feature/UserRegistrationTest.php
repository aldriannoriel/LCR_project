<?php

namespace Tests\Feature;

use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Mail\RegistrationPendingMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
    }

    public function test_user_can_register_with_valid_details_and_documents(): void
    {
        Storage::fake('local');
        Mail::fake();

        $file = UploadedFile::fake()->create('valid_id.jpg', 500, 'image/jpeg');
        $permit = UploadedFile::fake()->create('dti_permit.pdf', 800, 'application/pdf');

        $response = $this->postJson('/api/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_initial' => 'P',
            'sex' => 'male',
            'email' => 'juan.delacruz@example.ph',
            'phone_number' => '09171234567',
            'birthdate' => '1995-06-15',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'province' => 'Metro Manila',
            'city_municipality' => 'Quezon City',
            'barangay' => 'Batasan Hills',
            'street_address' => 'Blk 12 Lot 4, Commonwealth Ave',
            'business_name' => 'Dela Cruz Logistics Ent.',
            'id_document' => $file,
            'business_permit' => $permit,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.approval_status', 'pending');

        $this->assertDatabaseHas('users', [
            'email' => 'juan.delacruz@example.ph',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_initial' => 'P',
            'sex' => 'male',
            'age' => \Carbon\Carbon::parse('1995-06-15')->age,
            'approval_status' => 'pending',
        ]);

        $user = User::where('email', 'juan.delacruz@example.ph')->first();
        $this->assertNotNull($user->id_document_path);
        $this->assertNotNull($user->business_permit_path);
        Storage::disk('local')->assertExists($user->id_document_path);
        Storage::disk('local')->assertExists($user->business_permit_path);

        Mail::assertSent(RegistrationPendingMail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });
    }

    public function test_registration_fails_if_underage(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('id.jpg', 500, 'image/jpeg');

        $underageBirthday = now()->subYears(17)->format('Y-m-d');

        $response = $this->postJson('/api/register', [
            'first_name' => 'Minor',
            'last_name' => 'User',
            'sex' => 'female',
            'email' => 'minor@example.ph',
            'phone_number' => '09171234568',
            'birthdate' => $underageBirthday,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'province' => 'Cebu',
            'city_municipality' => 'Cebu City',
            'barangay' => 'Lahug',
            'street_address' => 'Gorordo Ave',
            'id_document' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birthdate']);
    }

    public function test_unapproved_user_cannot_login(): void
    {
        $user = User::create([
            'name' => 'Pending Shipper',
            'email' => 'pending@example.ph',
            'password' => Hash::make('password123'),
            'approval_status' => 'pending',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'pending@example.ph',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Your registration is currently pending administrator approval. Please check your email for updates.');
    }

    public function test_approved_user_can_login(): void
    {
        $user = User::create([
            'name' => 'Approved Shipper',
            'email' => 'approved@example.ph',
            'password' => Hash::make('password123'),
            'approval_status' => 'approved',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'approved@example.ph',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    public function test_admin_can_approve_pending_user(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['approval_status' => 'approved']);
        $admin->assignRole('Admin');

        $pendingUser = User::create([
            'name' => 'Pending Client',
            'first_name' => 'Pending',
            'last_name' => 'Client',
            'email' => 'pending.client@example.ph',
            'password' => Hash::make('password123'),
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/users/{$pendingUser->id}/approve");

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'approval_status' => 'approved',
        ]);

        Mail::assertSent(AccountApprovedMail::class, function ($mail) use ($pendingUser) {
            return $mail->user->id === $pendingUser->id;
        });
    }

    public function test_admin_can_reject_pending_user_with_reason(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['approval_status' => 'approved']);
        $admin->assignRole('Admin');

        $pendingUser = User::create([
            'name' => 'Reject Client',
            'first_name' => 'Reject',
            'last_name' => 'Client',
            'email' => 'reject.client@example.ph',
            'password' => Hash::make('password123'),
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/users/{$pendingUser->id}/reject", [
                'reason' => 'The provided government ID is blurry and expired.',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'approval_status' => 'rejected',
            'rejection_reason' => 'The provided government ID is blurry and expired.',
        ]);

        Mail::assertSent(AccountRejectedMail::class, function ($mail) use ($pendingUser) {
            return $mail->user->id === $pendingUser->id && str_contains($mail->reason, 'blurry');
        });
    }
}
