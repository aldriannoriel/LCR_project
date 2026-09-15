-- Alona rider onboarding demo data
-- Run the onboarding schema migration before this seed script.

BEGIN;

INSERT INTO public.alona_riders (
    id,
    full_name,
    email,
    phone,
    license_number,
    courier_type,
    status,
    rejection_reason,
    document_urls,
    approved_at,
    approved_by
)
SELECT
    '11111111-1111-4111-8111-111111111111'::uuid,
    'Maria Santos',
    'maria.santos@alona.test',
    '09171234567',
    'N01-23-456789',
    'IN_HOUSE'::public.alona_courier_type,
    'APPROVED'::public.alona_rider_status,
    NULL,
    '{"drivers_license":"https://placehold.co/1200x800/png?text=Maria+Santos+Drivers+License","vehicle_or_cr":"https://placehold.co/1200x800/png?text=Maria+Santos+Vehicle+OR+CR"}'::jsonb,
    now(),
    NULL
WHERE NOT EXISTS (
    SELECT 1 FROM public.alona_riders WHERE lower(email) = lower('maria.santos@alona.test')
);

INSERT INTO public.alona_riders (
    id,
    full_name,
    email,
    phone,
    license_number,
    courier_type,
    status,
    rejection_reason,
    document_urls
)
SELECT
    '22222222-2222-4222-8222-222222222222'::uuid,
    'Juan Dela Cruz',
    'juan.delacruz@alona.test',
    '09181234567',
    'N02-34-567890',
    'LBC'::public.alona_courier_type,
    'PENDING'::public.alona_rider_status,
    NULL,
    '{"drivers_license":"https://placehold.co/1200x800/png?text=Juan+Dela+Cruz+Drivers+License","vehicle_or_cr":"https://placehold.co/1200x800/png?text=Juan+Dela+Cruz+Vehicle+OR+CR"}'::jsonb
WHERE NOT EXISTS (
    SELECT 1 FROM public.alona_riders WHERE lower(email) = lower('juan.delacruz@alona.test')
);

INSERT INTO public.alona_riders (
    id,
    full_name,
    email,
    phone,
    license_number,
    courier_type,
    status,
    rejection_reason,
    document_urls
)
SELECT
    '33333333-3333-4333-8333-333333333333'::uuid,
    'Rafael Mendoza',
    'rafael.mendoza@alona.test',
    '09192345678',
    'N03-45-678901',
    'JNT_EXPRESS'::public.alona_courier_type,
    'PENDING'::public.alona_rider_status,
    NULL,
    '{"drivers_license":"https://placehold.co/1200x800/png?text=Rafael+Mendoza+Drivers+License","vehicle_or_cr":"https://placehold.co/1200x800/png?text=Rafael+Mendoza+Vehicle+OR+CR"}'::jsonb
WHERE NOT EXISTS (
    SELECT 1 FROM public.alona_riders WHERE lower(email) = lower('rafael.mendoza@alona.test')
);

INSERT INTO public.alona_riders (
    id,
    full_name,
    email,
    phone,
    license_number,
    courier_type,
    status,
    rejection_reason,
    document_urls
)
SELECT
    '44444444-4444-4444-8444-444444444444'::uuid,
    'Liza Navarro',
    'liza.navarro@alona.test',
    '09201234567',
    'N04-56-789012',
    'FLASH_EXPRESS'::public.alona_courier_type,
    'SUSPENDED'::public.alona_rider_status,
    NULL,
    '{"drivers_license":"https://placehold.co/1200x800/png?text=Liza+Navarro+Drivers+License","vehicle_or_cr":"https://placehold.co/1200x800/png?text=Liza+Navarro+Vehicle+OR+CR"}'::jsonb
WHERE NOT EXISTS (
    SELECT 1 FROM public.alona_riders WHERE lower(email) = lower('liza.navarro@alona.test')
);

INSERT INTO public.alona_rider_locations (
    rider_id,
    region_code,
    region_name,
    province_code,
    province_name,
    city_municipality_code,
    city_municipality_name,
    barangay_code,
    barangay_name,
    psgc_code
)
SELECT
    '11111111-1111-4111-8111-111111111111'::uuid,
    '040000000',
    'CALABARZON',
    '043400000',
    'Laguna',
    '043424000',
    'Santa Cruz',
    NULL,
    NULL,
    '043424000'
WHERE EXISTS (SELECT 1 FROM public.alona_riders WHERE id = '11111111-1111-4111-8111-111111111111'::uuid)
  AND NOT EXISTS (SELECT 1 FROM public.alona_rider_locations WHERE rider_id = '11111111-1111-4111-8111-111111111111'::uuid AND psgc_code = '043424000');

INSERT INTO public.alona_rider_locations (
    rider_id,
    region_code,
    region_name,
    province_code,
    province_name,
    city_municipality_code,
    city_municipality_name,
    barangay_code,
    barangay_name,
    psgc_code
)
SELECT
    '11111111-1111-4111-8111-111111111111'::uuid,
    '040000000',
    'CALABARZON',
    '043400000',
    'Laguna',
    '043426000',
    'Pagsanjan',
    NULL,
    NULL,
    '043426000'
WHERE EXISTS (SELECT 1 FROM public.alona_riders WHERE id = '11111111-1111-4111-8111-111111111111'::uuid)
  AND NOT EXISTS (SELECT 1 FROM public.alona_rider_locations WHERE rider_id = '11111111-1111-4111-8111-111111111111'::uuid AND psgc_code = '043426000');

INSERT INTO public.alona_rider_locations (
    rider_id,
    region_code,
    region_name,
    province_code,
    province_name,
    city_municipality_code,
    city_municipality_name,
    barangay_code,
    barangay_name,
    psgc_code
)
SELECT
    '11111111-1111-4111-8111-111111111111'::uuid,
    '040000000',
    'CALABARZON',
    '043400000',
    'Laguna',
    '043415000',
    'Los Baños',
    NULL,
    NULL,
    '043415000'
WHERE EXISTS (SELECT 1 FROM public.alona_riders WHERE id = '11111111-1111-4111-8111-111111111111'::uuid)
  AND NOT EXISTS (SELECT 1 FROM public.alona_rider_locations WHERE rider_id = '11111111-1111-4111-8111-111111111111'::uuid AND psgc_code = '043415000');

INSERT INTO public.alona_rider_locations (
    rider_id,
    region_code,
    region_name,
    province_code,
    province_name,
    city_municipality_code,
    city_municipality_name,
    barangay_code,
    barangay_name,
    psgc_code
)
SELECT
    '11111111-1111-4111-8111-111111111111'::uuid,
    '040000000',
    'CALABARZON',
    '043400000',
    'Laguna',
    NULL,
    NULL,
    NULL,
    NULL,
    '043400000'
WHERE EXISTS (SELECT 1 FROM public.alona_riders WHERE id = '11111111-1111-4111-8111-111111111111'::uuid)
  AND NOT EXISTS (SELECT 1 FROM public.alona_rider_locations WHERE rider_id = '11111111-1111-4111-8111-111111111111'::uuid AND psgc_code = '043400000');

COMMIT;

SELECT
    r.full_name,
    r.email,
    r.status,
    r.courier_type,
    COUNT(l.id)::integer AS assigned_locations
FROM public.alona_riders r
LEFT JOIN public.alona_rider_locations l ON l.rider_id = r.id
WHERE r.email LIKE '%@alona.test'
GROUP BY r.id
ORDER BY r.created_at;
