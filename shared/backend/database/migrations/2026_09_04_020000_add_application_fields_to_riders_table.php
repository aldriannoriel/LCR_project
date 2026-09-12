<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->string('license_number')->nullable()->after('phone_number');
            $table->string('license_doc_path')->nullable()->after('license_number');
            $table->string('vehicle_or_cr_path')->nullable()->after('license_doc_path');
            $table->enum('application_status', ['pending_review', 'approved', 'rejected'])->default('approved')->after('vehicle_or_cr_path');
            $table->text('rejection_reason')->nullable()->after('application_status');
        });
    }

    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn([
                'license_number',
                'license_doc_path',
                'vehicle_or_cr_path',
                'application_status',
                'rejection_reason',
            ]);
        });
    }
};
