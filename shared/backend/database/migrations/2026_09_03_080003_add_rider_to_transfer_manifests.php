<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_manifests', function (Blueprint $table) {
            $table->foreignId('rider_id')->nullable()->after('destination_hub_id')->constrained('riders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transfer_manifests', function (Blueprint $table) {
            $table->dropForeign(['rider_id']);
            $table->dropColumn('rider_id');
        });
    }
};