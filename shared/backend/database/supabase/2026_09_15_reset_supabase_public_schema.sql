-- DESTRUCTIVE RESET
-- This removes all tables, views, materialized views, sequences, and custom types
-- from the public schema. It does not remove Supabase auth.users or storage objects.
-- Run this in the Supabase SQL Editor only when the project reset is intentional.

BEGIN;

DO $$
DECLARE
    object_record RECORD;
BEGIN
    FOR object_record IN
        SELECT schemaname, tablename
        FROM pg_tables
        WHERE schemaname = 'public'
    LOOP
        EXECUTE format('DROP TABLE IF EXISTS %I.%I CASCADE', object_record.schemaname, object_record.tablename);
    END LOOP;

    FOR object_record IN
        SELECT schemaname, viewname
        FROM pg_views
        WHERE schemaname = 'public'
    LOOP
        EXECUTE format('DROP VIEW IF EXISTS %I.%I CASCADE', object_record.schemaname, object_record.viewname);
    END LOOP;

    FOR object_record IN
        SELECT schemaname, matviewname
        FROM pg_matviews
        WHERE schemaname = 'public'
    LOOP
        EXECUTE format('DROP MATERIALIZED VIEW IF EXISTS %I.%I CASCADE', object_record.schemaname, object_record.matviewname);
    END LOOP;

    FOR object_record IN
        SELECT sequence_schema, sequence_name
        FROM information_schema.sequences
        WHERE sequence_schema = 'public'
    LOOP
        EXECUTE format('DROP SEQUENCE IF EXISTS %I.%I CASCADE', object_record.sequence_schema, object_record.sequence_name);
    END LOOP;
END $$;

DROP TYPE IF EXISTS public.alona_rider_status CASCADE;
DROP TYPE IF EXISTS public.alona_courier_type CASCADE;
DROP TYPE IF EXISTS public.alona_parcel_status CASCADE;
DROP TYPE IF EXISTS public.alona_manifest_status CASCADE;

COMMIT;

SELECT 'Supabase public schema reset complete. Auth and storage schemas were preserved.' AS result;
