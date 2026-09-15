-- ALONA EMPTY SUPABASE DATABASE SETUP
-- Paste this entire file into Supabase Dashboard -> SQL Editor -> Run.
-- Run once. This creates the registration, approval, and rider territory tables.

CREATE EXTENSION IF NOT EXISTS pgcrypto;

DO $$ BEGIN
    CREATE TYPE public.alona_user_role AS ENUM ('RIDER', 'STAFF', 'ADMIN');
EXCEPTION WHEN duplicate_object THEN NULL;
END $$;

DO $$ BEGIN
    CREATE TYPE public.alona_approval_status AS ENUM ('PENDING', 'APPROVED', 'REJECTED');
EXCEPTION WHEN duplicate_object THEN NULL;
END $$;

DO $$ BEGIN
    CREATE TYPE public.alona_rider_status AS ENUM ('PENDING', 'APPROVED', 'REJECTED', 'SUSPENDED');
EXCEPTION WHEN duplicate_object THEN NULL;
END $$;

DO $$ BEGIN
    CREATE TYPE public.alona_courier_type AS ENUM ('IN_HOUSE', 'LBC', 'JNT_EXPRESS', 'FLASH_EXPRESS');
EXCEPTION WHEN duplicate_object THEN NULL;
END $$;

CREATE TABLE IF NOT EXISTS public.alona_users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    middle_initial VARCHAR(1),
    sex TEXT NOT NULL CHECK (sex IN ('MALE', 'FEMALE', 'PREFER_NOT_TO_SAY')),
    email TEXT NOT NULL,
    contact_no VARCHAR(20) NOT NULL,
    birthday DATE NOT NULL,
    age INTEGER NOT NULL CHECK (age >= 18 AND age <= 120),
    province TEXT NOT NULL,
    municipality TEXT NOT NULL,
    barangay TEXT NOT NULL,
    street_address TEXT NOT NULL,
    role public.alona_user_role NOT NULL,
    approval_status public.alona_approval_status NOT NULL DEFAULT 'PENDING',
    rejection_reason TEXT,
    document_urls JSONB NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(document_urls) = 'object'),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.alona_rider_details (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL UNIQUE REFERENCES public.alona_users(id) ON DELETE CASCADE,
    vehicle_type TEXT NOT NULL CHECK (vehicle_type IN ('MOTORCYCLE', 'VAN', 'TRUCK', 'BICYCLE', 'MULTI_CAB')),
    plate_number TEXT NOT NULL,
    or_cr_url TEXT NOT NULL,
    drivers_license_url TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS public.alona_staff_details (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL UNIQUE REFERENCES public.alona_users(id) ON DELETE CASCADE,
    id_document_url TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS public.alona_rider_locations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    rider_id UUID NOT NULL REFERENCES public.alona_users(id) ON DELETE CASCADE,
    region_name TEXT NOT NULL,
    province_name TEXT,
    city_municipality_name TEXT,
    psgc_code VARCHAR(32) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT alona_rider_locations_unique UNIQUE (rider_id, psgc_code)
);

CREATE INDEX IF NOT EXISTS alona_users_status_idx ON public.alona_users(approval_status);
CREATE INDEX IF NOT EXISTS alona_users_role_idx ON public.alona_users(role);
CREATE INDEX IF NOT EXISTS alona_users_created_idx ON public.alona_users(created_at DESC);
CREATE UNIQUE INDEX IF NOT EXISTS alona_users_active_email_idx
    ON public.alona_users(lower(email))
    WHERE approval_status IN ('PENDING', 'APPROVED');
CREATE INDEX IF NOT EXISTS alona_rider_locations_rider_idx ON public.alona_rider_locations(rider_id);
CREATE INDEX IF NOT EXISTS alona_rider_locations_psgc_idx ON public.alona_rider_locations(psgc_code);

ALTER TABLE public.alona_users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.alona_rider_details ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.alona_staff_details ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.alona_rider_locations ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS alona_users_admin_all ON public.alona_users;
CREATE POLICY alona_users_admin_all ON public.alona_users
    FOR ALL TO authenticated
    USING ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin')
    WITH CHECK ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin');

DROP POLICY IF EXISTS alona_rider_details_admin_all ON public.alona_rider_details;
CREATE POLICY alona_rider_details_admin_all ON public.alona_rider_details
    FOR ALL TO authenticated
    USING ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin')
    WITH CHECK ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin');

DROP POLICY IF EXISTS alona_staff_details_admin_all ON public.alona_staff_details;
CREATE POLICY alona_staff_details_admin_all ON public.alona_staff_details
    FOR ALL TO authenticated
    USING ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin')
    WITH CHECK ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin');

DROP POLICY IF EXISTS alona_rider_locations_admin_all ON public.alona_rider_locations;
CREATE POLICY alona_rider_locations_admin_all ON public.alona_rider_locations
    FOR ALL TO authenticated
    USING ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin')
    WITH CHECK ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin');

SELECT 'Alona Supabase schema is ready.' AS result;
