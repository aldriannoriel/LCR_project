<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/_bootstrap.php';

$api = alonaApi();
$api->run(function (PDO $db) use ($api): array {
    if ($api->method() !== 'GET') throw new InvalidArgumentException('Only GET is supported.');
    $status = strtoupper($api->query('status') ?? 'PENDING');
    if (!in_array($status, ['PENDING', 'APPROVED', 'REJECTED'], true)) throw new InvalidArgumentException('Invalid approval status.');
    $statement = $db->prepare("SELECT u.*, rd.vehicle_type, rd.plate_number, rd.or_cr_url, rd.drivers_license_url, sd.id_document_url, COALESCE(jsonb_agg(to_jsonb(rl) ORDER BY rl.region_name, rl.province_name, rl.city_municipality_name) FILTER (WHERE rl.id IS NOT NULL), '[]'::jsonb) AS locations FROM public.alona_users u LEFT JOIN public.alona_rider_details rd ON rd.user_id = u.id LEFT JOIN public.alona_staff_details sd ON sd.user_id = u.id LEFT JOIN public.alona_rider_locations rl ON rl.rider_id = u.id WHERE u.approval_status = :status GROUP BY u.id, rd.id, sd.id ORDER BY u.created_at DESC");
    $statement->execute(['status' => $status]);
    $users = $statement->fetchAll();
    foreach ($users as &$user) { $user['document_urls'] = json_decode((string) $user['document_urls'], true) ?: []; $user['locations'] = json_decode((string) $user['locations'], true) ?: []; }
    return ['data' => ['success' => true, 'data' => $users]];
});
