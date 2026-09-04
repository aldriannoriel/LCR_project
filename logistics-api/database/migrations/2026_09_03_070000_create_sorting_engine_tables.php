<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coverage_areas', function (Blueprint $table) {
            $table->id();
            $table->string('province');
            $table->string('city_municipality');
            $table->foreignId('hub_id')->constrained('hubs')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['province', 'city_municipality']);
        });

        Schema::create('order_route_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('national_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('gateway_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('regional_hub_id')->constrained('hubs')->cascadeOnDelete();
            $table->json('route_path_json');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('bins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained('hubs')->cascadeOnDelete();
            $table->string('bin_code')->unique();
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('current_count')->default(0);
            $table->foreignId('target_hub_id')->constrained('hubs')->cascadeOnDelete();
            $table->enum('status', ['active', 'full', 'maintenance'])->default('active');
            $table->timestamps();
            $table->index(['hub_id', 'target_hub_id', 'status']);
        });

        Schema::create('bin_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bin_id')->constrained('bins')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('scanned_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->unique(['bin_id', 'order_id']);
        });

        Schema::create('transfer_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('manifest_number')->unique();
            $table->foreignId('origin_hub_id')->constrained('hubs')->cascadeOnDelete();
            $table->foreignId('destination_hub_id')->constrained('hubs')->cascadeOnDelete();
            $table->enum('status', ['draft', 'dispatched', 'received'])->default('draft');
            $table->unsignedInteger('total_orders')->default(0);
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transfer_manifest_order', function (Blueprint $table) {
            $table->foreignId('transfer_manifest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->primary(['transfer_manifest_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_manifest_order');
        Schema::dropIfExists('transfer_manifests');
        Schema::dropIfExists('bin_assignments');
        Schema::dropIfExists('bins');
        Schema::dropIfExists('order_route_plans');
        Schema::dropIfExists('coverage_areas');
    }
};