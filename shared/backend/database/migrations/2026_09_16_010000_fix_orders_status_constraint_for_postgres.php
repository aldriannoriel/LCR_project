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

        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'received'::character varying, 'in_transit'::character varying, 'in_hub'::character varying, 'out_for_delivery'::character varying, 'delivered'::character varying, 'damaged'::character varying, 'flagged'::character varying, 'failed_delivery'::character varying, 'in_return_queue'::character varying, 'rts'::character varying, 'disposed'::character varying]))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'received'::character varying, 'in_transit'::character varying, 'delivered'::character varying, 'damaged'::character varying, 'flagged'::character varying]))");
    }
};
