<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE alona_riders MODIFY status ENUM('active', 'suspended', 'on_duty', 'inactive') NOT NULL DEFAULT 'active'");
        }
        Schema::table('alona_riders', function (Blueprint $table) {
            $table->enum('application_status', ['pending_review', 'approved', 'rejected'])->default('pending_review')->after('status');
            $table->text('application_rejection_reason')->nullable()->after('application_status');
        });
    }

    public function down(): void
    {
        Schema::table('alona_riders', function (Blueprint $table) {
            $table->dropColumn(['application_status', 'application_rejection_reason']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE alona_riders SET status = 'active' WHERE status = 'inactive'");
            DB::statement("ALTER TABLE alona_riders MODIFY status ENUM('active', 'suspended', 'on_duty') NOT NULL DEFAULT 'active'");
        }
    }
};
