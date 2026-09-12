<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('assigned_rider_id')->nullable()->constrained('riders')->nullOnDelete();

            $table->string('contact_person');
            $table->string('contact_number');
            $table->string('province');
            $table->string('city_municipality');
            $table->string('barangay');
            $table->text('pickup_address');

            $table->date('scheduled_date');
            $table->string('time_slot')->default('morning'); // morning, afternoon, evening
            $table->unsignedInteger('estimated_parcels')->default(1);
            $table->string('package_type')->default('parcels'); // parcels, bulky, document
            $table->text('special_instructions')->nullable();

            $table->enum('status', [
                'pending',
                'verified',
                'assigned',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('pending');

            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('actual_parcels_collected')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};
