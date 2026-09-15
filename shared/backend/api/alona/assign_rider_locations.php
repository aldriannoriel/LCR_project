<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$api = alonaApi();
$api->run(function (PDO $db) use ($api): array {
    if ($api->method() !== 'POST') {
        throw new InvalidArgumentException('Only POST is supported.');
    }

    $data = $api->input();
    $riderId = $api->uuid($data, 'rider_id');
    $locations = $data['locations'] ?? null;
    if (!is_array($locations)) {
        throw new InvalidArgumentException('locations must be an array.');
    }
    if (count($locations) > 5000) {
        throw new InvalidArgumentException('A maximum of 5000 location assignments is allowed per request.');
    }

    $db->beginTransaction();
    try {
        $rider = $db->prepare('SELECT id, status FROM public.alona_riders WHERE id = :id FOR UPDATE');
        $rider->execute(['id' => $riderId]);
        $riderRecord = $rider->fetch();
        if (!$riderRecord) throw new InvalidArgumentException('Rider not found.');
        if ($riderRecord['status'] !== 'APPROVED') throw new InvalidArgumentException('Locations can only be assigned to an approved rider.');

        $delete = $db->prepare('DELETE FROM public.alona_rider_locations WHERE rider_id = :rider_id');
        $delete->execute(['rider_id' => $riderId]);

        $insert = $db->prepare('INSERT INTO public.alona_rider_locations (rider_id, region_code, region_name, province_code, province_name, city_municipality_code, city_municipality_name, barangay_code, barangay_name, psgc_code) VALUES (:rider_id, :region_code, :region_name, :province_code, :province_name, :city_code, :city_name, :barangay_code, :barangay_name, :psgc_code) ON CONFLICT (rider_id, psgc_code) DO NOTHING');
        foreach ($locations as $location) {
            if (!is_array($location)) throw new InvalidArgumentException('Each location must be an object.');
            $regionCode = $api->requiredString($location, 'region_code', 32);
            $regionName = $api->requiredString($location, 'region_name', 150);
            $psgcCode = $api->requiredString($location, 'psgc_code', 32);
            $provinceCode = $api->optionalString($location, 'province_code', 32);
            $provinceName = $api->optionalString($location, 'province_name', 150);
            $cityCode = $api->optionalString($location, 'city_municipality_code', 32);
            $cityName = $api->optionalString($location, 'city_municipality_name', 150);
            $barangayCode = $api->optionalString($location, 'barangay_code', 32);
            $barangayName = $api->optionalString($location, 'barangay_name', 150);
            if (($cityCode === null) !== ($cityName === null)) throw new InvalidArgumentException('City code and city name must be provided together.');
            if (($barangayCode === null) !== ($barangayName === null) || ($barangayCode !== null && $cityCode === null)) throw new InvalidArgumentException('Barangay requires a matching city assignment.');

            $insert->execute([
                'rider_id' => $riderId,
                'region_code' => $regionCode,
                'region_name' => $regionName,
                'province_code' => $provinceCode,
                'province_name' => $provinceName,
                'city_code' => $cityCode,
                'city_name' => $cityName,
                'barangay_code' => $barangayCode,
                'barangay_name' => $barangayName,
                'psgc_code' => $psgcCode,
            ]);
        }

        $result = $db->prepare('SELECT * FROM public.alona_rider_locations WHERE rider_id = :rider_id ORDER BY region_name, province_name NULLS FIRST, city_municipality_name NULLS FIRST');
        $result->execute(['rider_id' => $riderId]);
        $db->commit();

        return ['data' => ['success' => true, 'message' => 'Rider coverage locations saved.', 'data' => $result->fetchAll()]];
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        throw $exception;
    }
});
