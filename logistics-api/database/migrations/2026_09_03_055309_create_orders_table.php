<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('awb_number')->unique();
            $table->enum('status', [
                'pending', 'received', 'in_transit', 'delivered', 'damaged', 'flagged'
            ])->default('pending');
            $table->foreignId('hub_id')->constrained('hubs')->cascadeOnDelete();
            $table->string('sender_name');
            $table->string('recipient_name');
            $table->text('recipient_address');
            $table->string('flag_reason')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};