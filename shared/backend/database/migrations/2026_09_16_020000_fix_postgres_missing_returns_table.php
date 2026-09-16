<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (! Schema::hasTable('returns')) {
            Schema::create('returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
                $table->enum('return_reason', ['failed_delivery_3x', 'customer_refused', 'incorrect_address', 'damaged_goods', 'expired_holding'])->nullable();
                $table->enum('status', ['pending_intake', 'in_reverse_queue', 'reattempt_scheduled', 'rts_in_transit', 'rts_completed', 'disposed'])->default('pending_intake');
                $table->foreignId('action_taken_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('action_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Schema::dropIfExists('returns');
    }
};
