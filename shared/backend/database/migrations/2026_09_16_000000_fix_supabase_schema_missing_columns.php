<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hubs', function (Blueprint $table) {
            if (! Schema::hasColumn('hubs', 'current_stock')) {
                $table->integer('current_stock')->default(0);
            }

            if (! Schema::hasColumn('hubs', 'hub_type')) {
                $table->string('hub_type')->nullable();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'current_hub_id')) {
                $table->foreignId('current_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'hub_scanned_at')) {
                $table->timestamp('hub_scanned_at')->nullable();
            }
        });

        if (! Schema::hasTable('transfer_requests')) {
            Schema::create('transfer_requests', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number')->unique();
                $table->foreignId('from_hub_id')->constrained('hubs')->cascadeOnDelete();
                $table->foreignId('to_hub_id')->constrained('hubs')->cascadeOnDelete();
                $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['pending', 'approved', 'rejected', 'in_transit', 'completed'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('transfer_request_items')) {
            Schema::create('transfer_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->unique(['transfer_request_id', 'order_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transfer_request_items')) {
            Schema::dropIfExists('transfer_request_items');
        }

        if (Schema::hasTable('transfer_requests')) {
            Schema::dropIfExists('transfer_requests');
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'current_hub_id')) {
                $table->dropConstrainedForeignId('current_hub_id');
            }

            if (Schema::hasColumn('orders', 'hub_scanned_at')) {
                $table->dropColumn('hub_scanned_at');
            }
        });

        Schema::table('hubs', function (Blueprint $table) {
            if (Schema::hasColumn('hubs', 'current_stock')) {
                $table->dropColumn('current_stock');
            }

            if (Schema::hasColumn('hubs', 'hub_type')) {
                $table->dropColumn('hub_type');
            }
        });
    }
};
