<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'current_hub_id')) {
                $table->dropForeign(['current_hub_id']);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach (['weight_kg', 'length_cm', 'width_cm', 'height_cm', 'sender_address', 'flagged', 'damaged', 'current_hub_id'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('orders', 'receiver_name')) {
                $table->renameColumn('receiver_name', 'recipient_name');
            }
            if (! Schema::hasColumn('orders', 'hub_id')) {
                $table->foreignId('hub_id')->after('status')->constrained('hubs')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('orders', 'flag_reason')) $table->string('flag_reason')->nullable();
            if (! Schema::hasColumn('orders', 'scanned_at')) $table->timestamp('scanned_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'received', 'in_transit', 'delivered', 'damaged', 'flagged'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['hub_id']);
            $table->dropColumn(['hub_id', 'flag_reason', 'scanned_at']);
            $table->renameColumn('recipient_name', 'receiver_name');
        });
    }
};