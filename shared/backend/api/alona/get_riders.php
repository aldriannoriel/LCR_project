<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$api = alonaApi();
$api->run(function (PDO $db) use ($api): array {
    if ($api->method() !== 'GET') {
        throw new InvalidArgumentException('Only GET is supported.');
    }

    $status = strtoupper($api->query('status') ?? '');
    if ($status !== '' && !in_array($status, ['PENDING', 'APPROVED', 'REJECTED', 'SUSPENDED'], true)) {
        throw new InvalidArgumentException('status must be PENDING, APPROVED, REJECTED, or SUSPENDED.');
    }

    $parameters = [];
    $where = '';
    if ($status !== '') {
        $where = 'WHERE r.status = :status';
        $parameters['status'] = $status;
    }

    $statement = $db->prepare("SELECT r.*, COALESCE(jsonb_agg(to_jsonb(l) ORDER BY l.region_name, l.province_name NULLS FIRST, l.city_municipality_name NULLS FIRST) FILTER (WHERE l.id IS NOT NULL), '[]'::jsonb) AS locations FROM public.alona_riders r LEFT JOIN public.alona_rider_locations l ON l.rider_id = r.id {$where} GROUP BY r.id ORDER BY r.created_at DESC");
    $statement->execute($parameters);
    $riders = $statement->fetchAll();
    foreach ($riders as &$rider) {
        $rider['document_urls'] = json_decode((string)$rider['document_urls'], true) ?: [];
        $rider['locations'] = json_decode((string)$rider['locations'], true) ?: [];
    }
    unset($rider);

    return ['data' => ['success' => true, 'data' => $riders]];
});
