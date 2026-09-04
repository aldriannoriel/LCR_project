<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_performance', function (Blueprint $table) {
            $table->unsignedInteger('successful_deliveries')->default(0)->after('total_failed');
            $table->unsignedInteger('failed_deliveries')->default(0)->after('successful_deliveries');
            $table->unsignedInteger('successful_pickups')->default(0)->after('failed_deliveries');
            $table->unsignedInteger('total_deliveries')->default(0)->after('successful_pickups');
            $table->decimal('total_earnings', 10, 2)->default(0)->after('total_deliveries');
            $table->decimal('delivery_rate', 8, 2)->default(50)->after('total_earnings');
            $table->decimal('pickup_rate', 8, 2)->default(30)->after('delivery_rate');
        });
    }

    public function down(): void
    {
        Schema::table('rider_performance', function (Blueprint $table) {
            $table->dropColumn([
                'successful_deliveries',
                'failed_deliveries',
                'successful_pickups',
                'total_deliveries',
                'total_earnings',
                'delivery_rate',
                'pickup_rate',
            ]);
        });
    }
};
