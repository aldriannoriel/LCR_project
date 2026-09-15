<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/_bootstrap.php';

$api = alonaApi();
$api->runPublic(function (PDO $db) use ($api): array {
    if ($api->method() !== 'POST') throw new InvalidArgumentException('Only POST is supported.');
    $data = $api->multipartInput(['first_name', 'last_name', 'sex', 'email', 'contact_no', 'birthday', 'province', 'municipality', 'barangay', 'street_address', 'vehicle_type', 'plate_number']);
    $firstName = $api->requiredString($data, 'first_name', 100);
    $lastName = $api->requiredString($data, 'last_name', 100);
    $middleInitial = $api->optionalString($data, 'middle_initial', 1);
    $sex = strtoupper($api->requiredString($data, 'sex', 30));
    $email = strtolower($api->requiredString($data, 'email', 255));
    $contactNo = $api->requiredString($data, 'contact_no', 20);
    $birthday = $api->requiredString($data, 'birthday', 10);
    $province = $api->requiredString($data, 'province', 150);
    $municipality = $api->requiredString($data, 'municipality', 150);
    $barangay = $api->requiredString($data, 'barangay', 150);
    $streetAddress = $api->requiredString($data, 'street_address', 500);
    $vehicleType = strtoupper($api->requiredString($data, 'vehicle_type', 30));
    $plateNumber = $api->requiredString($data, 'plate_number', 30);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid email address.');
    if (!preg_match('/^09\d{9}$/', preg_replace('/[\s-]/', '', $contactNo))) throw new InvalidArgumentException('Contact number must use 09XXXXXXXXX format.');
    if (!in_array($sex, ['MALE', 'FEMALE', 'PREFER_NOT_TO_SAY'], true)) throw new InvalidArgumentException('Invalid sex selection.');
    if (!in_array($vehicleType, ['MOTORCYCLE', 'VAN', 'TRUCK', 'BICYCLE', 'MULTI_CAB'], true)) throw new InvalidArgumentException('Invalid vehicle selection.');
    $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $birthday);
    if (!$birthDate || $birthDate->format('Y-m-d') !== $birthday) throw new InvalidArgumentException('Birthday must be a valid date.');
    $today = new DateTimeImmutable('today');
    $age = $today->diff($birthDate)->y;
    if ($birthDate > $today || $age < 18) throw new InvalidArgumentException('Riders must be at least 18 years old.');

    $licenseUrl = $api->upload('drivers_license');
    $orCrUrl = $api->upload('or_cr');
    $db->beginTransaction();
    try {
        $userStatement = $db->prepare('INSERT INTO public.alona_users (first_name, last_name, middle_initial, sex, email, contact_no, birthday, age, province, municipality, barangay, street_address, role, approval_status, document_urls) VALUES (:first_name, :last_name, :middle_initial, :sex, :email, :contact_no, :birthday, :age, :province, :municipality, :barangay, :street_address, \'RIDER\', \'PENDING\', CAST(:document_urls AS jsonb)) RETURNING id, first_name, last_name, email, approval_status');
        $userStatement->execute(['first_name' => $firstName, 'last_name' => $lastName, 'middle_initial' => $middleInitial, 'sex' => $sex, 'email' => $email, 'contact_no' => $contactNo, 'birthday' => $birthday, 'age' => $age, 'province' => $province, 'municipality' => $municipality, 'barangay' => $barangay, 'street_address' => $streetAddress, 'document_urls' => json_encode(['drivers_license' => $licenseUrl, 'or_cr' => $orCrUrl], JSON_THROW_ON_ERROR)]);
        $user = $userStatement->fetch();
        $detailStatement = $db->prepare('INSERT INTO public.alona_rider_details (user_id, vehicle_type, plate_number, or_cr_url, drivers_license_url) VALUES (:user_id, :vehicle_type, :plate_number, :or_cr_url, :drivers_license_url)');
        $detailStatement->execute(['user_id' => $user['id'], 'vehicle_type' => $vehicleType, 'plate_number' => $plateNumber, 'or_cr_url' => $orCrUrl, 'drivers_license_url' => $licenseUrl]);
        $db->commit();
        return ['status' => 201, 'data' => ['success' => true, 'message' => 'Rider registration submitted. Please wait for administrator approval, which will be sent to your email.', 'data' => $user]];
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
});
