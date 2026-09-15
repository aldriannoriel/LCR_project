<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$api = alonaApi();
$api->run(function (PDO $db) use ($api): array {
    $method = $api->method();
    $statuses = ['ACTIVE', 'SUSPENDED', 'ON_DUTY', 'INACTIVE'];
    $courierTypes = ['IN_HOUSE', 'LBC', 'JNT_EXPRESS', 'FLASH_EXPRESS'];

    if ($method === 'GET') {
        $conditions = [];
        $parameters = [];
        if ($status = $api->query('status')) {
            if (!in_array(strtoupper($status), $statuses, true)) throw new InvalidArgumentException('Invalid rider status.');
            $conditions[] = 'r.status = :status';
            $parameters['status'] = strtoupper($status);
        }
        if ($courierType = $api->query('courier_type')) {
            if (!in_array(strtoupper($courierType), $courierTypes, true)) throw new InvalidArgumentException('Invalid courier type.');
            $conditions[] = 'r.courier_type = :courier_type';
            $parameters['courier_type'] = strtoupper($courierType);
        }

        $sql = 'SELECT r.* FROM public.alona_riders r';
        if ($conditions) $sql .= ' WHERE ' . implode(' AND ', $conditions);
        $sql .= ' ORDER BY r.created_at DESC';
        $statement = $db->prepare($sql);
        $statement->execute($parameters);
        return ['data' => ['success' => true, 'data' => $statement->fetchAll()]];
    }

    if ($method === 'POST') {
        $data = $api->input();
        $id = $api->uuid($data, 'id', false);
        $fields = [
            'full_name' => $api->requiredString($data, 'full_name', 150),
            'phone' => $api->requiredString($data, 'phone', 50),
            'courier_type' => $api->enum($data, 'courier_type', $courierTypes),
            'status' => $api->enum($data, 'status', $statuses, false) ?? 'ACTIVE',
            'license_number' => $api->optionalString($data, 'license_number', 100),
        ];
        $documentUrls = $data['document_urls'] ?? [];
        if (!is_array($documentUrls)) throw new InvalidArgumentException('document_urls must be a JSON array.');
        $documentJson = json_encode(array_values($documentUrls), JSON_THROW_ON_ERROR);

        if ($id) {
            $statement = $db->prepare('UPDATE public.alona_riders SET full_name = :full_name, phone = :phone, courier_type = :courier_type, status = :status, license_number = :license_number, document_urls = :document_urls WHERE id = :id RETURNING *');
            $statement->execute($fields + ['document_urls' => $documentJson, 'id' => $id]);
            $rider = $statement->fetch();
            if (!$rider) throw new InvalidArgumentException('Rider not found.');
            return ['status' => 200, 'data' => ['success' => true, 'data' => $rider]];
        }

        $statement = $db->prepare('INSERT INTO public.alona_riders (full_name, phone, courier_type, status, license_number, document_urls) VALUES (:full_name, :phone, :courier_type, :status, :license_number, :document_urls) RETURNING *');
        $statement->execute($fields + ['document_urls' => $documentJson]);
        return ['status' => 200, 'data' => ['success' => true, 'data' => $statement->fetch()]];
    }

    if ($method === 'PATCH') {
        $data = $api->input();
        $id = $api->uuid($data, 'id');
        $status = $api->enum($data, 'status', ['ACTIVE', 'SUSPENDED', 'ON_DUTY']);
        $statement = $db->prepare('UPDATE public.alona_riders SET status = :status WHERE id = :id RETURNING *');
        $statement->execute(['status' => $status, 'id' => $id]);
        $rider = $statement->fetch();
        if (!$rider) throw new InvalidArgumentException('Rider not found.');
        return ['data' => ['success' => true, 'data' => $rider]];
    }

    throw new InvalidArgumentException('Method not allowed for this endpoint.');
});