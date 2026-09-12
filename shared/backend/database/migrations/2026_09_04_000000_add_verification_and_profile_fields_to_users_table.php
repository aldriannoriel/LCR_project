<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('middle_initial', 10)->nullable()->after('last_name');
            $table->enum('sex', ['male', 'female', 'other'])->nullable()->after('middle_initial');
            $table->string('phone_number')->nullable()->after('email');
            $table->date('birthdate')->nullable()->after('phone_number');
            $table->unsignedSmallInteger('age')->nullable()->after('birthdate');

            $table->string('province')->nullable()->after('age');
            $table->string('city_municipality')->nullable()->after('province');
            $table->string('barangay')->nullable()->after('city_municipality');
            $table->text('street_address')->nullable()->after('barangay');

            $table->string('business_name')->nullable()->after('street_address');
            $table->string('id_document_path')->nullable()->after('business_name');
            $table->string('business_permit_path')->nullable()->after('id_document_path');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('business_permit_path');
            $table->text('rejection_reason')->nullable()->after('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'middle_initial',
                'sex',
                'phone_number',
                'birthdate',
                'age',
                'province',
                'city_municipality',
                'barangay',
                'street_address',
                'business_name',
                'id_document_path',
                'business_permit_path',
                'approval_status',
                'rejection_reason',
            ]);
        });
    }
};

