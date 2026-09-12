<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('delivery_failure_reason')->nullable()->after('delivery_status');
            $table->text('delivery_notes')->nullable()->after('delivery_failure_reason');
            $table->decimal('delivery_fee', 8, 2)->default(50)->after('delivery_notes');
            $table->string('recipient_phone')->nullable()->after('recipient_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_failure_reason', 'delivery_notes', 'delivery_fee', 'recipient_phone']);
        });
    }
};
