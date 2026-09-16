<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            return;
        }

        Schema::table('orders', fn (Blueprint $table) => $table->enum('status', ['pending', 'received', 'in_transit', 'out_for_delivery', 'delivered', 'damaged', 'flagged'])->default('pending')->change());
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            return;
        }

        Schema::table('orders', fn (Blueprint $table) => $table->enum('status', ['pending', 'received', 'in_transit', 'delivered', 'damaged', 'flagged'])->default('pending')->change());
    }
};