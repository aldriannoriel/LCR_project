CREATE EXTENSION IF NOT EXISTS pgcrypto;

DO $$ BEGIN
    CREATE TYPE public.alona_rider_status AS ENUM ('PENDING', 'APPROVED', 'REJECTED', 'SUSPENDED');
EXCEPTION WHEN duplicate_object THEN NULL;
END $$;

DO $$ BEGIN
    CREATE TYPE public.alona_courier_type AS ENUM ('IN_HOUSE', 'LBC', 'JNT_EXPRESS', 'FLASH_EXPRESS');
EXCEPTION WHEN duplicate_object THEN NULL;
END $$;

CREATE TABLE IF NOT EXISTS public.alona_riders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    full_name TEXT NOT NULL CHECK (length(trim(full_name)) > 0),
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    license_number TEXT NOT NULL,
    courier_type public.alona_courier_type NOT NULL DEFAULT 'IN_HOUSE',
    status public.alona_rider_status NOT NULL DEFAULT 'PENDING',
    rejection_reason TEXT,
    document_urls JSONB NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(document_urls) = 'object'),
    approved_at TIMESTAMPTZ,
    approved_by UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.alona_rider_locations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    rider_id UUID NOT NULL REFERENCES public.alona_riders(id) ON DELETE CASCADE,
    region_code VARCHAR(32) NOT NULL,
    region_name TEXT NOT NULL,
    province_code VARCHAR(32),
    province_name TEXT,
    city_municipality_code VARCHAR(32),
    city_municipality_name TEXT,
    barangay_code VARCHAR(32),
    barangay_name TEXT,
    psgc_code VARCHAR(32) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT alona_rider_locations_scope_check CHECK (
        (city_municipality_code IS NULL AND city_municipality_name IS NULL)
        OR (city_municipality_code IS NOT NULL AND city_municipality_name IS NOT NULL)
    ),
    CONSTRAINT alona_rider_locations_barangay_check CHECK (
        (barangay_code IS NULL AND barangay_name IS NULL)
        OR (barangay_code IS NOT NULL AND barangay_name IS NOT NULL AND city_municipality_code IS NOT NULL)
    ),
    CONSTRAINT alona_rider_locations_unique_scope UNIQUE (rider_id, psgc_code)
);

CREATE INDEX IF NOT EXISTS alona_riders_status_idx ON public.alona_riders(status);
CREATE INDEX IF NOT EXISTS alona_riders_email_idx ON public.alona_riders(lower(email));
CREATE INDEX IF NOT EXISTS alona_rider_locations_rider_idx ON public.alona_rider_locations(rider_id);
CREATE INDEX IF NOT EXISTS alona_rider_locations_province_idx ON public.alona_rider_locations(province_code);
CREATE INDEX IF NOT EXISTS alona_rider_locations_city_idx ON public.alona_rider_locations(city_municipality_code);
CREATE INDEX IF NOT EXISTS alona_rider_locations_psgc_idx ON public.alona_rider_locations(psgc_code);
CREATE UNIQUE INDEX IF NOT EXISTS alona_riders_active_email_unique ON public.alona_riders(lower(email)) WHERE status IN ('PENDING', 'APPROVED', 'SUSPENDED');

ALTER TABLE public.alona_riders ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.alona_rider_locations ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS alona_riders_admin_all ON public.alona_riders;
CREATE POLICY alona_riders_admin_all ON public.alona_riders
    FOR ALL TO authenticated
    USING ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin')
    WITH CHECK ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin');

DROP POLICY IF EXISTS alona_rider_locations_admin_all ON public.alona_rider_locations;
CREATE POLICY alona_rider_locations_admin_all ON public.alona_rider_locations
    FOR ALL TO authenticated
    USING ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin')
    WITH CHECK ((auth.jwt() -> 'app_metadata' ->> 'role') = 'admin');