<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            if (Schema::hasColumn('order_status_histories', 'hub_id')) $table->renameColumn('hub_id', 'location_hub_id');
            if (Schema::hasColumn('order_status_histories', 'user_id')) $table->renameColumn('user_id', 'performed_by_user_id');
            if (Schema::hasColumn('order_status_histories', 'remarks')) $table->renameColumn('remarks', 'notes');
        });
        Schema::table('orders', fn (Blueprint $table) => $table->enum('status', ['pending', 'received', 'in_transit', 'in_hub', 'out_for_delivery', 'delivered', 'damaged', 'flagged', 'failed_delivery', 'in_return_queue', 'rts', 'disposed'])->default('pending')->change());
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->enum('return_reason', ['failed_delivery_3x', 'customer_refused', 'incorrect_address', 'damaged_goods', 'expired_holding']);
            $table->enum('status', ['pending_intake', 'in_reverse_queue', 'reattempt_scheduled', 'rts_in_transit', 'rts_completed', 'disposed'])->default('pending_intake');
            $table->foreignId('action_taken_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('action_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
        Schema::table('orders', fn (Blueprint $table) => $table->enum('status', ['pending', 'received', 'in_transit', 'in_hub', 'out_for_delivery', 'delivered', 'damaged', 'flagged', 'failed_delivery', 'in_return_queue', 'rts', 'disposed'])->default('pending')->change());
    }
};