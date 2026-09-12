<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'receiver_address') && ! Schema::hasColumn('orders', 'recipient_address')) {
            Schema::table('orders', fn (Blueprint $table) => $table->renameColumn('receiver_address', 'recipient_address'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'recipient_address')) {
            Schema::table('orders', fn (Blueprint $table) => $table->renameColumn('recipient_address', 'receiver_address'));
        }
    }
};