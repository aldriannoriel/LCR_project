<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/_bootstrap.php';

$api = alonaApi();
$api->run(function (PDO $db) use ($api): array {
    if ($api->method() !== 'POST') throw new InvalidArgumentException('Only POST is supported.');
    $data = $api->input();
    $userId = $api->uuid($data, 'user_id');
    $action = $api->enum($data, 'action', ['APPROVE', 'REJECT']);
    $reason = $api->optionalString($data, 'rejection_reason', 2000);
    if ($action === 'REJECT' && !$reason) throw new InvalidArgumentException('rejection_reason is required when rejecting an application.');
    $statement = $db->prepare('UPDATE public.alona_users SET approval_status = :status, rejection_reason = :rejection_reason WHERE id = :id AND approval_status = \'PENDING\' RETURNING *');
    $statement->execute(['status' => $action === 'APPROVE' ? 'APPROVED' : 'REJECTED', 'rejection_reason' => $action === 'REJECT' ? $reason : null, 'id' => $userId]);
    $user = $statement->fetch();
    if (!$user) throw new InvalidArgumentException('Pending user application not found.');
    return ['data' => ['success' => true, 'message' => 'Application ' . strtolower($action) . '.', 'data' => $user]];
});
