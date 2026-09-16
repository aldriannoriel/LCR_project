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

        Schema::table('riders', function (Blueprint $table) {
            if (! Schema::hasColumn('riders', 'archipelago_id')) {
                $table->foreignId('archipelago_id')->nullable()->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('riders', 'vehicle_type')) {
                $table->string('vehicle_type')->nullable();
            }

            if (! Schema::hasColumn('riders', 'plate_number')) {
                $table->string('plate_number')->nullable();
            }

            if (! Schema::hasColumn('riders', 'phone_number')) {
                $table->string('phone_number')->nullable();
            }

            if (! Schema::hasColumn('riders', 'license_number')) {
                $table->string('license_number')->nullable();
            }

            if (! Schema::hasColumn('riders', 'license_doc_path')) {
                $table->string('license_doc_path')->nullable();
            }

            if (! Schema::hasColumn('riders', 'vehicle_or_cr_path')) {
                $table->string('vehicle_or_cr_path')->nullable();
            }

            if (! Schema::hasColumn('riders', 'application_status')) {
                $table->enum('application_status', ['pending_review', 'approved', 'rejected'])->default('approved');
            }

            if (! Schema::hasColumn('riders', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }

            if (! Schema::hasColumn('riders', 'coverage_area_id')) {
                $table->foreignId('coverage_area_id')->nullable()->constrained('coverage_areas')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('rider_performance')) {
            Schema::create('rider_performance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rider_id')->unique()->constrained('riders')->cascadeOnDelete();
                $table->unsignedInteger('total_assigned')->default(0);
                $table->unsignedInteger('total_completed')->default(0);
                $table->unsignedInteger('total_failed')->default(0);
                $table->unsignedInteger('successful_deliveries')->default(0);
                $table->unsignedInteger('failed_deliveries')->default(0);
                $table->unsignedInteger('successful_pickups')->default(0);
                $table->unsignedInteger('total_deliveries')->default(0);
                $table->decimal('total_earnings', 10, 2)->default(0);
                $table->decimal('delivery_rate', 8, 2)->default(50);
                $table->decimal('pickup_rate', 8, 2)->default(30);
                $table->decimal('on_time_rate', 5, 2)->default(0);
                $table->decimal('rating', 3, 2)->default(0);
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'rider_id')) {
                $table->foreignId('rider_id')->nullable()->constrained('riders')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }

            if (! Schema::hasColumn('orders', 'dispatched_at')) {
                $table->timestamp('dispatched_at')->nullable();
            }

            if (! Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }

            if (! Schema::hasColumn('orders', 'delivery_status')) {
                $table->string('delivery_status')->nullable();
            }

            if (! Schema::hasColumn('orders', 'delivery_failure_reason')) {
                $table->text('delivery_failure_reason')->nullable();
            }

            if (! Schema::hasColumn('orders', 'delivery_notes')) {
                $table->text('delivery_notes')->nullable();
            }

            if (! Schema::hasColumn('orders', 'delivery_fee')) {
                $table->decimal('delivery_fee', 8, 2)->default(50);
            }

            if (! Schema::hasColumn('orders', 'recipient_phone')) {
                $table->string('recipient_phone')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'rider_id')) {
                $table->dropConstrainedForeignId('rider_id');
            }

            foreach (['assigned_at', 'dispatched_at', 'delivered_at', 'delivery_status', 'delivery_failure_reason', 'delivery_notes', 'delivery_fee', 'recipient_phone'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('riders', function (Blueprint $table) {
            if (Schema::hasColumn('riders', 'coverage_area_id')) {
                $table->dropConstrainedForeignId('coverage_area_id');
            }

            foreach (['archipelago_id', 'vehicle_type', 'plate_number', 'phone_number', 'license_number', 'license_doc_path', 'vehicle_or_cr_path', 'application_status', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('riders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('rider_performance');
    }
};
