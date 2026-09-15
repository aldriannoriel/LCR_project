<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$api = alonaApi();
$api->runPublic(function (PDO $db) use ($api): array {
    if ($api->method() !== 'POST') {
        throw new InvalidArgumentException('Only POST is supported.');
    }

    $data = $api->input();
    $fullName = $api->requiredString($data, 'full_name', 150);
    $email = strtolower($api->requiredString($data, 'email', 255));
    $phone = $api->requiredString($data, 'phone', 50);
    $licenseNumber = $api->requiredString($data, 'license_number', 100);
    $courierType = $api->enum($data, 'courier_type', ['IN_HOUSE', 'LBC', 'JNT_EXPRESS', 'FLASH_EXPRESS'], false) ?? 'IN_HOUSE';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('email must be a valid email address.');
    }

    $documents = $data['document_urls'] ?? [];
    if (!is_array($documents) || json_encode($documents, JSON_THROW_ON_ERROR) === false) {
        throw new InvalidArgumentException('document_urls must be a valid JSON object.');
    }
    foreach ($documents as $documentType => $documentUrl) {
        if (!is_string($documentType) || !is_string($documentUrl) || !filter_var($documentUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Every document URL must be a valid URL.');
        }
    }

    $existing = $db->prepare('SELECT id, status FROM public.alona_riders WHERE lower(email) = lower(:email) AND status IN (\'PENDING\', \'APPROVED\', \'SUSPENDED\') LIMIT 1');
    $existing->execute(['email' => $email]);
    if ($existing->fetch()) {
        throw new InvalidArgumentException('An active rider application already exists for this email.');
    }

    $statement = $db->prepare('INSERT INTO public.alona_riders (full_name, email, phone, license_number, courier_type, status, document_urls) VALUES (:full_name, :email, :phone, :license_number, :courier_type, \'PENDING\', CAST(:document_urls AS jsonb)) RETURNING id, full_name, email, phone, license_number, courier_type, status, document_urls, created_at');
    $statement->execute([
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'license_number' => $licenseNumber,
        'courier_type' => $courierType,
        'document_urls' => json_encode($documents, JSON_THROW_ON_ERROR),
    ]);

    return ['status' => 201, 'data' => ['success' => true, 'message' => 'Rider application submitted for review.', 'data' => $statement->fetch()]];
});
