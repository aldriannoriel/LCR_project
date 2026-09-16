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

        Schema::table('order_status_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('order_status_histories', 'location_hub_id')) {
                $table->foreignId('location_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            }

            if (! Schema::hasColumn('order_status_histories', 'performed_by_user_id')) {
                $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('order_status_histories', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        DB::statement("UPDATE order_status_histories SET location_hub_id = hub_id WHERE location_hub_id IS NULL AND hub_id IS NOT NULL");
        DB::statement("UPDATE order_status_histories SET performed_by_user_id = user_id WHERE performed_by_user_id IS NULL AND user_id IS NOT NULL");
        DB::statement("UPDATE order_status_histories SET notes = remarks WHERE notes IS NULL AND remarks IS NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('order_status_histories', function (Blueprint $table) {
            if (Schema::hasColumn('order_status_histories', 'location_hub_id')) {
                $table->dropConstrainedForeignId('location_hub_id');
            }

            if (Schema::hasColumn('order_status_histories', 'performed_by_user_id')) {
                $table->dropConstrainedForeignId('performed_by_user_id');
            }

            if (Schema::hasColumn('order_status_histories', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
