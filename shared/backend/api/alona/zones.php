<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$api = alonaApi();
$api->run(function (PDO $db) use ($api): array {
    if ($api->method() === 'GET') {
        $statement = $db->query('SELECT z.*, r.id AS rider_id, r.full_name AS rider_name, r.phone AS rider_phone, r.courier_type AS rider_courier_type, r.status AS rider_status FROM public.alona_zones z LEFT JOIN public.alona_riders r ON r.id = z.default_rider_id ORDER BY z.zone_name ASC');
        return ['data' => ['success' => true, 'data' => $statement->fetchAll()]];
    }

    if ($api->method() === 'POST') {
        $data = $api->input();
        $zoneId = $api->uuid($data, 'id');
        $riderId = $api->uuid($data, 'default_rider_id', false);
        $statement = $db->prepare('UPDATE public.alona_zones SET default_rider_id = :default_rider_id WHERE id = :id RETURNING *');
        $statement->execute(['default_rider_id' => $riderId, 'id' => $zoneId]);
        $zone = $statement->fetch();
        if (!$zone) throw new InvalidArgumentException('Zone not found.');
        return ['data' => ['success' => true, 'data' => $zone]];
    }

    throw new InvalidArgumentException('Method not allowed for this endpoint.');
});