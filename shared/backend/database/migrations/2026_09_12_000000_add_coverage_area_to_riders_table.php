<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->foreignId('coverage_area_id')
                ->nullable()
                ->after('hub_id')
                ->constrained('coverage_areas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropForeign(['coverage_area_id']);
            $table->dropColumn('coverage_area_id');
        });
    }
};
