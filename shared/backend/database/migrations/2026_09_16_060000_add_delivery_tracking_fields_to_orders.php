<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'delivery_attempts')) {
                $table->unsignedInteger('delivery_attempts')->default(0);
            }

            if (! Schema::hasColumn('orders', 'proof_image_path')) {
                $table->string('proof_image_path')->nullable();
            }

            if (! Schema::hasColumn('orders', 'delivery_failure_photo_path')) {
                $table->string('delivery_failure_photo_path')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['delivery_attempts', 'proof_image_path', 'delivery_failure_photo_path'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
