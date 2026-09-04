<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('archipelago_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hub_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['available', 'on_route', 'on_leave', 'suspended'])->default('available');
            $table->string('vehicle_type')->nullable();
            $table->string('license_number')->nullable();
            $table->timestamps();   
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};