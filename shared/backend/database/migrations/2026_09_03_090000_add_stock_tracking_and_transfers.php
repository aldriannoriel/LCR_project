<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hubs', function (Blueprint $table) {
            $table->integer('current_stock')->default(0)->after('capacity');
            $table->enum('hub_type', ['national', 'gateway', 'regional'])->nullable()->after('type');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('current_hub_id')->nullable()->after('hub_id')->constrained('hubs')->nullOnDelete();
            $table->timestamp('hub_scanned_at')->nullable()->after('scanned_at');
        });
        Schema::table('orders', fn (Blueprint $table) => $table->enum('status', ['pending', 'received', 'in_transit', 'in_hub', 'out_for_delivery', 'delivered', 'damaged', 'flagged'])->default('pending')->change());

        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('from_hub_id')->constrained('hubs')->cascadeOnDelete();
            $table->foreignId('to_hub_id')->constrained('hubs')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_transit', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('transfer_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unique(['transfer_request_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_request_items'); Schema::dropIfExists('transfer_requests');
        Schema::table('orders', function (Blueprint $table) { $table->dropForeign(['current_hub_id']); $table->dropColumn(['current_hub_id', 'hub_scanned_at']); });
        Schema::table('hubs', function (Blueprint $table) { $table->dropColumn(['current_stock', 'hub_type']); });
    }
};