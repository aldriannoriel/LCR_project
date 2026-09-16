<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            if (! Schema::hasColumn('riders', 'license_number')) {
                $table->string('license_number')->nullable()->after('phone_number');
            }
            if (! Schema::hasColumn('riders', 'license_doc_path')) {
                $table->string('license_doc_path')->nullable()->after('license_number');
            }
            if (! Schema::hasColumn('riders', 'vehicle_or_cr_path')) {
                $table->string('vehicle_or_cr_path')->nullable()->after('license_doc_path');
            }
            if (! Schema::hasColumn('riders', 'application_status')) {
                $table->enum('application_status', ['pending_review', 'approved', 'rejected'])->default('approved')->after('vehicle_or_cr_path');
            }
            if (! Schema::hasColumn('riders', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('application_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('riders', 'license_number')) { $columns[] = 'license_number'; }
            if (Schema::hasColumn('riders', 'license_doc_path')) { $columns[] = 'license_doc_path'; }
            if (Schema::hasColumn('riders', 'vehicle_or_cr_path')) { $columns[] = 'vehicle_or_cr_path'; }
            if (Schema::hasColumn('riders', 'application_status')) { $columns[] = 'application_status'; }
            if (Schema::hasColumn('riders', 'rejection_reason')) { $columns[] = 'rejection_reason'; }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
