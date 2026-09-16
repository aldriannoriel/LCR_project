<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_performance')) {
            return;
        }

        Schema::table('rider_performance', function (Blueprint $table) {
            if (! Schema::hasColumn('rider_performance', 'successful_deliveries')) {
                $table->unsignedInteger('successful_deliveries')->default(0)->after('total_failed');
            }
            if (! Schema::hasColumn('rider_performance', 'failed_deliveries')) {
                $table->unsignedInteger('failed_deliveries')->default(0)->after('successful_deliveries');
            }
            if (! Schema::hasColumn('rider_performance', 'successful_pickups')) {
                $table->unsignedInteger('successful_pickups')->default(0)->after('failed_deliveries');
            }
            if (! Schema::hasColumn('rider_performance', 'total_deliveries')) {
                $table->unsignedInteger('total_deliveries')->default(0)->after('successful_pickups');
            }
            if (! Schema::hasColumn('rider_performance', 'total_earnings')) {
                $table->decimal('total_earnings', 10, 2)->default(0)->after('total_deliveries');
            }
            if (! Schema::hasColumn('rider_performance', 'delivery_rate')) {
                $table->decimal('delivery_rate', 8, 2)->default(50)->after('total_earnings');
            }
            if (! Schema::hasColumn('rider_performance', 'pickup_rate')) {
                $table->decimal('pickup_rate', 8, 2)->default(30)->after('delivery_rate');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_performance')) {
            return;
        }

        Schema::table('rider_performance', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('rider_performance', 'successful_deliveries')) { $columns[] = 'successful_deliveries'; }
            if (Schema::hasColumn('rider_performance', 'failed_deliveries')) { $columns[] = 'failed_deliveries'; }
            if (Schema::hasColumn('rider_performance', 'successful_pickups')) { $columns[] = 'successful_pickups'; }
            if (Schema::hasColumn('rider_performance', 'total_deliveries')) { $columns[] = 'total_deliveries'; }
            if (Schema::hasColumn('rider_performance', 'total_earnings')) { $columns[] = 'total_earnings'; }
            if (Schema::hasColumn('rider_performance', 'delivery_rate')) { $columns[] = 'delivery_rate'; }
            if (Schema::hasColumn('rider_performance', 'pickup_rate')) { $columns[] = 'pickup_rate'; }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
