<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE riders DROP CONSTRAINT IF EXISTS riders_status_check");
        DB::statement("ALTER TABLE riders ADD CONSTRAINT riders_status_check CHECK (status::text = ANY (ARRAY['available'::character varying, 'on_route'::character varying, 'on_leave'::character varying, 'suspended'::character varying, 'on_delivery'::character varying, 'off_duty'::character varying]))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE riders DROP CONSTRAINT IF EXISTS riders_status_check");
        DB::statement("ALTER TABLE riders ADD CONSTRAINT riders_status_check CHECK (status::text = ANY (ARRAY['available'::character varying, 'on_route'::character varying, 'on_leave'::character varying, 'suspended'::character varying]))");
    }
};
