# Phase 4: Domain Consolidation Plan

Status: planning only. No database migration or data mutation has been executed.

## Recommendation

Do not replace `Order` with `AlonaParcel` in one migration. The legacy operational model is still authoritative for bulk receiving, sorting, hub inventory, transfers, returns, reporting, pickup-created parcels, and the courier delivery app. The Alona model currently covers parcel identity, rider/agency/zone assignment, manifest approval, coarse delivery status, and audit records, but it cannot represent the full legacy workflow.

Use a projection-first migration:

1. Add crosswalks and operational companion tables.
2. Validate and backfill eligible legacy records into Alona records.
3. Keep legacy writes authoritative while writing an idempotent Alona projection.
4. Shadow-read and reconcile both representations.
5. Cut over one operational flow at a time.
6. Retire legacy writes only after reconciliation and an approved rollback window.

## Required Alona-side additions

The minimum companion schema is:

- `legacy_alona_parcel_map`: legacy order to Alona parcel identity and mapping status.
- `legacy_alona_rider_map`: legacy rider to Alona rider, preserving hub semantics.
- `legacy_alona_hub_map`: explicit hub/zone relationship; never infer a physical hub from a geographic zone.
- `legacy_alona_manifest_map`: legacy hub-transfer manifest to an Alona transport record if that record is introduced.
- `alona_migration_quarantine`: invalid or ambiguous records that require review.
- `alona_parcel_locations`: current and historical physical hub/location data.
- `alona_parcel_events`: status, actor, timestamp, hub, bin, source record, and notes.
- `alona_bin_assignments`: bin scans and capacity-related data.
- `alona_hub_transfer_manifests` and `alona_hub_transfer_items`: origin/destination transport workflow.
- `alona_parcel_returns`: return reason, queue/action status, actor, notes, and timestamps.
- `alona_pickup_links`: pickup-to-manifest/parcel lineage.
- `alona_delivery_evidence`: proof image, recipient, notes, failure reason/photo, and delivery fee/performance data.

Initially, nullable `legacy_order_id` and indexed `legacy_awb` references may be added to `alona_parcels`. Keep all crosswalks immutable after assignment.

## Flow migration order

### 1. Identity and read-only tracking

Backfill only orders with a unique AWB/tracking number, valid recipient data, an approved seller mapping or explicit quarantine decision, and a supported status. Preserve the original order ID and AWB.

Quarantine duplicate AWBs, missing sellers, missing recipient addresses, invalid statuses, unknown riders, inconsistent hubs, and return states without a return record.

### 2. Rider assignment and delivery projection

Create an explicit rider crosswalk. Preserve legacy physical hub assignment separately from Alona agency/zone assignment. Project assignment, delivery start, delivery completion, failed attempts, proof, notes, and performance into Alona companion tables.

### 3. Bulk receive and sort scan

Keep legacy writes authoritative until Alona has physical location, event history, bin assignments, route plans, and capacity checks. Project bulk receipt discrepancy, receiving hub, scan actor, route/bin decisions, and sorted events idempotently.

`AlonaZone` is geographic coverage and must not replace a physical hub or bin.

### 4. Hub transfers and manifests

Keep seller submission manifests (`AlonaBulkManifest`) separate from hub-to-hub transport manifests. Introduce a dedicated transport model/table and map legacy `TransferManifest` and `TransferRequest` into it only after origin, destination, item membership, approval, dispatch, and completion semantics are represented.

### 5. Pickup creation

Add pickup lineage before generating Alona parcels. Every generated parcel needs a seller mapping and source pickup ID. Do not infer seller ownership from free-text sender fields.

### 6. Returns and reporting

Migrate returns after parcel events exist. Preserve reattempt, RTS, disposal, reason, action actor, and notes. Move reports last because SLA and hub metrics depend on complete historical events.

## Status mapping requiring approval

The following mapping is a proposal, not an automatic rule:

```text
pending          -> AT_SORTING_CENTER
received         -> AT_SORTING_CENTER
sorted           -> SORTED
in_hub           -> SORTED
out_for_delivery -> OUT_FOR_DELIVERY
delivered        -> DELIVERED
failed delivery  -> DELIVERY_FAILED
```

These states need companion states or an approved policy before migration:

```text
in_transit, in_return_queue, rts, disposed, damaged, flagged
```

The current Alona status endpoint also needs correction before cutover: its validation does not accept every state used by its transition map.

## Dual-write and shadow-read rules

- Legacy remains user-visible and authoritative during coexistence.
- Every projection write must be idempotent using a source event key such as `source_type:source_id:source_updated_at`.
- A durable outbox is preferred over an untracked retry loop.
- Shadow reads compare counts, statuses, current hub, rider assignment, manifest membership, returns, and SLA totals.
- On disagreement, prefer legacy until that flow passes reconciliation.
- Never substitute an Alona zone for a legacy physical hub.

## Cutover and rollback

Before each cutover, validate unique AWBs, seller/rider/hub crosswalk coverage, status distributions, event ordering, representative delivery/return/transfer histories, and retry idempotency.

Rollback is flow-scoped:

1. Stop Alona projection consumption.
2. Route reads back to legacy.
3. Keep newly created Alona records as projection history; do not delete them.
4. Reconcile events written during the rollback window.
5. Resume only when the divergence queue is empty.

No destructive database migration should run without explicit approval and a tested backup/restore procedure.

## Phase 4 decision gate

Before implementation, approve:

- the seller fallback/quarantine policy;
- the status mapping and any new operational statuses;
- whether transport manifests become a new Alona model;
- the physical hub and bin schema;
- the dual-write/outbox mechanism;
- the reconciliation thresholds and rollback window.
