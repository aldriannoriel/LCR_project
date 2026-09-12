<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['national_sorting', 'gateway', 'regional']);
            $table->foreignId('archipelago_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->integer('capacity')->default(0);
            $table->integer('current_utilization')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hubs');
    }
};