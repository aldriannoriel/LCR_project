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
    $action = $api->enum($data, 'action', ['APPROVE', 'REJECT']);
    $reason = $api->optionalString($data, 'rejection_reason', 2000);

    if ($action === 'REJECT' && !$reason) {
        throw new InvalidArgumentException('rejection_reason is required when rejecting an application.');
    }

    $db->beginTransaction();
    try {
        $current = $db->prepare('SELECT id, status FROM public.alona_riders WHERE id = :id FOR UPDATE');
        $current->execute(['id' => $riderId]);
        $rider = $current->fetch();
        if (!$rider) {
            throw new InvalidArgumentException('Rider application not found.');
        }
        if ($rider['status'] !== 'PENDING') {
            throw new InvalidArgumentException('Only pending rider applications can be reviewed.');
        }

        $newStatus = $action === 'APPROVE' ? 'APPROVED' : 'REJECTED';
        $statement = $db->prepare('UPDATE public.alona_riders SET status = :status, rejection_reason = :rejection_reason, approved_at = CASE WHEN :approved_status = \'APPROVED\' THEN now() ELSE NULL END, approved_by = CASE WHEN :approved_status = \'APPROVED\' THEN :approved_by ELSE NULL END WHERE id = :id RETURNING *');
        $statement->execute([
            'status' => $newStatus,
            'approved_status' => $newStatus,
            'rejection_reason' => $action === 'REJECT' ? $reason : null,
            'approved_by' => $api->authenticatedUserId(),
            'id' => $riderId,
        ]);
        $updated = $statement->fetch();
        $db->commit();

        return ['data' => ['success' => true, 'message' => sprintf('Rider application %s.', strtolower($action)), 'data' => $updated]];
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        throw $exception;
    }
});
