<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/_bootstrap.php';

$api = alonaApi();
$api->run(function (PDO $db) use ($api): array {
    if ($api->method() !== 'POST') throw new InvalidArgumentException('Only POST is supported.');
    $data = $api->input();
    $riderId = $api->uuid($data, 'rider_id');
    $locations = $data['locations'] ?? null;
    if (!is_array($locations) || count($locations) > 5000) throw new InvalidArgumentException('locations must be an array of no more than 5000 items.');
    $approved = $db->prepare("SELECT id FROM public.alona_users WHERE id = :id AND role = 'RIDER' AND approval_status = 'APPROVED'");
    $approved->execute(['id' => $riderId]);
    if (!$approved->fetch()) throw new InvalidArgumentException('Only an approved rider can receive locations.');
    $db->beginTransaction();
    try {
        $delete = $db->prepare('DELETE FROM public.alona_rider_locations WHERE rider_id = :rider_id');
        $delete->execute(['rider_id' => $riderId]);
        $insert = $db->prepare('INSERT INTO public.alona_rider_locations (rider_id, region_name, province_name, city_municipality_name, psgc_code) VALUES (:rider_id, :region_name, :province_name, :city_name, :psgc_code) ON CONFLICT (rider_id, psgc_code) DO NOTHING');
        foreach ($locations as $location) {
            if (!is_array($location)) throw new InvalidArgumentException('Each location must be an object.');
            $insert->execute(['rider_id' => $riderId, 'region_name' => $api->requiredString($location, 'region_name', 150), 'province_name' => $api->optionalString($location, 'province_name', 150), 'city_name' => $api->optionalString($location, 'city_municipality_name', 150), 'psgc_code' => $api->requiredString($location, 'psgc_code', 32)]);
        }
        $result = $db->prepare('SELECT * FROM public.alona_rider_locations WHERE rider_id = :rider_id ORDER BY region_name, province_name, city_municipality_name');
        $result->execute(['rider_id' => $riderId]);
        $db->commit();
        return ['data' => ['success' => true, 'message' => 'Rider locations saved.', 'data' => $result->fetchAll()]];
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
});
