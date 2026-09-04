<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropForeign(['archipelago_id']);
            $table->dropColumn(['archipelago_id', 'license_number']);
            $table->string('vehicle_type')->default('motorcycle')->change();
            $table->string('plate_number')->nullable()->after('vehicle_type');
            $table->string('phone_number')->nullable()->after('plate_number');
        });
        Schema::table('riders', function (Blueprint $table) {
            $table->enum('status', ['available', 'on_delivery', 'off_duty', 'suspended'])->default('available')->change();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('rider_id')->nullable()->after('hub_id')->constrained('riders')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('delivery_status')->nullable();
        });
        Schema::create('rider_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->unique()->constrained('riders')->cascadeOnDelete();
            $table->unsignedInteger('total_assigned')->default(0);
            $table->unsignedInteger('total_completed')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            $table->decimal('on_time_rate', 5, 2)->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_performance');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['rider_id']);
            $table->dropColumn(['rider_id', 'assigned_at', 'dispatched_at', 'delivered_at', 'delivery_status']);
        });
    }
};