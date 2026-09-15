<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alona_courier_agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('api_base_url')->nullable();
            $table->json('api_credentials')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('alona_riders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('alona_courier_agencies')->nullOnDelete();
            $table->enum('rider_kind', ['internal', 'external'])->default('internal');
            $table->string('full_name', 150);
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('license_number', 100)->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->string('plate_number', 50)->nullable();
            $table->enum('status', ['active', 'suspended', 'on_duty', 'inactive'])->default('active');
            $table->enum('application_status', ['pending_review', 'approved', 'rejected'])->default('pending_review');
            $table->text('application_rejection_reason')->nullable();
            $table->enum('document_status', ['pending', 'verified', 'rejected', 'expired'])->default('pending');
            $table->boolean('is_default_assignment')->default(false);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'agency_id']);
            $table->index('license_number');
        });

        Schema::create('alona_rider_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('alona_riders')->cascadeOnDelete();
            $table->string('document_type', 80);
            $table->text('file_path');
            $table->enum('status', ['pending', 'verified', 'rejected', 'expired'])->default('pending');
            $table->date('expires_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['rider_id', 'status']);
        });

        Schema::create('alona_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->string('province', 150)->nullable();
            $table->string('city_municipality', 150)->nullable();
            $table->json('barangays')->nullable();
            $table->foreignId('default_rider_id')->nullable()->constrained('alona_riders')->nullOnDelete();
            $table->foreignId('default_agency_id')->nullable()->constrained('alona_courier_agencies')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->json('boundary_geojson')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['province', 'city_municipality']);
            $table->index(['default_rider_id', 'default_agency_id']);
        });

        Schema::create('alona_bulk_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('manifest_number', 80)->unique();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('alona_zones')->nullOnDelete();
            $table->foreignId('rider_id')->nullable()->constrained('alona_riders')->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('alona_courier_agencies')->nullOnDelete();
            $table->enum('status', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'DISPATCHED', 'CLOSED'])->default('DRAFT');
            $table->unsignedInteger('total_parcels')->default(0);
            $table->unsignedInteger('approved_parcels')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index('seller_id');
        });

        Schema::create('alona_parcels', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number', 80)->unique();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('manifest_id')->nullable()->constrained('alona_bulk_manifests')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('alona_zones')->nullOnDelete();
            $table->foreignId('rider_id')->nullable()->constrained('alona_riders')->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('alona_courier_agencies')->nullOnDelete();
            $table->enum('status', ['AT_SORTING_CENTER', 'SORTED', 'ASSIGNED_TO_RIDER', 'OUT_FOR_DELIVERY', 'DELIVERY_FAILED', 'DELIVERED', 'RETURNED'])->default('AT_SORTING_CENTER');
            $table->string('recipient_name', 150);
            $table->string('recipient_phone', 50)->nullable();
            $table->text('recipient_address');
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->unsignedInteger('delivery_attempts')->default(0);
            $table->text('failed_reason')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['status', 'zone_id']);
            $table->index(['rider_id', 'status']);
            $table->index('seller_id');
        });

        Schema::create('alona_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->enum('action', ['CREATED', 'UPDATED', 'STATUS_CHANGED', 'ASSIGNED', 'UNASSIGNED', 'APPROVED', 'REJECTED', 'DISPATCHED', 'DELIVERED', 'FAILED', 'RETURNED', 'DOCUMENT_VERIFIED']);
            $table->string('previous_status', 80)->nullable();
            $table->string('new_status', 80)->nullable();
            $table->text('reason')->nullable();
            $table->json('changes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
        });

        Schema::create('alona_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained('alona_parcels')->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 50);
            $table->string('status', 30)->default('OPEN');
            $table->string('priority', 20)->default('NORMAL');
            $table->text('description');
            $table->text('resolution')->nullable();
            $table->timestamps();
            $table->index(['parcel_id', 'status']);
        });

        Schema::create('alona_dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('alona_disputes')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->index(['dispute_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alona_dispute_messages');
        Schema::dropIfExists('alona_disputes');
        Schema::dropIfExists('alona_audit_logs');
        Schema::dropIfExists('alona_parcels');
        Schema::dropIfExists('alona_bulk_manifests');
        Schema::dropIfExists('alona_zones');
        Schema::dropIfExists('alona_rider_documents');
        Schema::dropIfExists('alona_riders');
        Schema::dropIfExists('alona_courier_agencies');
    }
};
