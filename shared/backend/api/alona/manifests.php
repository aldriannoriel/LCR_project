<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$api = alonaApi();
$api->run(function (PDO $db) use ($api): array {
    if ($api->method() === 'GET') {
        $statement = $db->query('SELECT m.*, COUNT(p.id)::integer AS parcel_count FROM public.alona_bulk_manifests m LEFT JOIN public.alona_parcels p ON p.manifest_id = m.id GROUP BY m.id ORDER BY m.created_at DESC');
        return ['data' => ['success' => true, 'data' => $statement->fetchAll()]];
    }

    if ($api->method() === 'POST') {
        $data = $api->input();
        $manifestId = $api->uuid($data, 'id');
        $db->beginTransaction();
        try {
            $manifest = $db->prepare("UPDATE public.alona_bulk_manifests SET status = 'APPROVED' WHERE id = :id AND status IN ('DRAFT', 'SUBMITTED') RETURNING *");
            $manifest->execute(['id' => $manifestId]);
            $approvedManifest = $manifest->fetch();
            if (!$approvedManifest) throw new InvalidArgumentException('Manifest not found or cannot be approved.');

            $assign = $db->prepare('UPDATE public.alona_parcels p SET assigned_rider_id = z.default_rider_id FROM public.alona_zones z WHERE p.zone_id = z.id AND p.manifest_id = :manifest_id AND z.default_rider_id IS NOT NULL');
            $assign->execute(['manifest_id' => $manifestId]);
            $db->commit();
            return ['data' => ['success' => true, 'data' => $approvedManifest, 'message' => 'Manifest approved and eligible parcels assigned to default zone riders.']];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) $db->rollBack();
            throw $exception;
        }
    }

    throw new InvalidArgumentException('Method not allowed for this endpoint.');
});