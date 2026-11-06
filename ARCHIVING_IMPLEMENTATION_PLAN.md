# DokuFlow Archiving & Records Management — Implementation Plan & Architectural Assessment

## Phase 1: Repository Audit & Architectural Assessment

### 1.1 Verified Technology Stack & Architecture
| Layer | Verified Implementation in Repository | Integration Strategy for Archiving |
|---|---|---|
| **Framework & Runtime** | Laravel `^13.8`, PHP `^8.3`, SQLite (`:memory:`) in PHPUnit, MySQL/PostgreSQL/SQLite compatible migrations | Use standard Laravel migrations, Eloquent models, database transactions (`DB::transaction`), and row-level locking (`lockForUpdate()`). |
| **Multi-Tenancy & Org Hierarchy** | `Company` (`id`, `name`, `code`), `Branch` (`id`, `company_id`, `name`, `code`, `is_pusat`, `pic_klinik_id`, `effective_code`), `UnitKerja` (`id`, `kode_unit_kerja`, `nama_unit_kerja`, `pic_user_id`). Context managed via `CompanyContextService`. | Scope all archive records, requests, numbering counters, legal holds, and disposition workflows by `company_id` (and optionally `branch_id` / `unit_kerja_id`). Snapshot organization codes at registration time. |
| **Roles & Authorization** | `User::system_role` (`admin`, `direktur`, `pic_klinik`, `head`, `staff`), `Gate::define('admin', ...)`, `DocumentPolicy`. | Implement `ArchivePolicy` and `ArchiveRequestPolicy` enforcing tenant isolation, branch/unit scoping, classification confidentiality levels (`public`, `internal`, `confidential`, `restricted`), and role-based governance permissions. |
| **Document & Version Model** | `Document` (`SoftDeletes`, `document_number`, `current_version_id`, `visibility`, `share_token`) and `DocumentVersion` (`version_number`, `status` in `draft\|pending\|active\|inactive\|rejected\|discarded`, `file_path`, `content`). | Keep `documents.document_number` 100% separate from `archive_entries.archive_number`. Only finalized (`status = 'active'`) versions with no active ONLYOFFICE write locks or pending approvals/signatures can be registered into the archive. |
| **Storage & ONLYOFFICE** | Files stored on `Storage::disk(config('onlyoffice.storage_disk', 'local'))` under `documents/{doc_id}/v{ver}.{ext}`. Active ONLYOFFICE sessions tracked via `Cache::has('onlyoffice_active_' . $doc->id)` and `OnlyOfficeService`. | Freeze/rotate ONLYOFFICE key before snapshot creation. Copy file bytes into an independent immutable storage namespace: `archives/{company_id}/{year}/{uuid}.{ext}` (plus canonical HTML snapshot for HTML-only docs) with SHA-256 content and manifest hashes. |
| **Audit & Notifications** | `AuditService::log()` writes to `audit_logs`. Notifications use `database` + `SafeBroadcastChannel` (`NewNotification` event). | Create dedicated append-only `archive_events` table with chained SHA-256 `event_hash` AND bridge events to `AuditService::log()`. Dispatch realtime/database notifications for archive request, decision, registration, legal hold, retention due, and disposition events. |
| **Frontend UI System** | Blade templates with `x-app-layout`, Tailwind CSS + DaisyUI (`card`, `btn`, `badge`, `modal`, `glass-panel`), Alpine.js, bilingual JSON translations (`lang/id.json`, `lang/en.json`). | Build Archive Hub, Request Wizard, Archive Detail, Governance Center, QR Verification, and Admin Settings using existing DaisyUI/Tailwind patterns and navigation sidebar. |

### 1.2 Identified Risks & Architectural Safeguards
1. **Hazard: Trash Auto-Prune (`PruneTrashedDocuments`) & Manual Permanent Delete (`TrashController`, `Admin\DocumentController`)**
   - *Risk:* `documents:prune-trashed` and permanent delete actions delete all `DocumentVersion` files and force-delete the `Document` row.
   - *Mitigation:*
     - Archive snapshots are stored in a dedicated `archives/{company_id}/{year}/{uuid}.{ext}` storage path that `PruneTrashedDocuments` and `TrashController` never touch.
     - `archive_entries.document_id` and `archive_entries.document_version_id` use `nullOnDelete()` while full document/version/signature/approval lineage is preserved inside `archive_entries.metadata_snapshot` and `archive_snapshots.manifest_payload`.
     - Documents with active in-flight archive requests (`draft`, `pending_approval`, `approved`, `preparing_snapshot`, `ready_to_register`) are blocked from permanent deletion until the request is canceled or completed.
2. **Hazard: Expired Version Purge (`DeleteExpiredVersions` / `versions:purge`)**
   - *Risk:* Purges `DocumentVersion` rows where `status != 'active'` older than retention days. If an archived version later becomes `inactive` when superseded by a newer working version, `versions:purge` would delete the `DocumentVersion` row and file.
   - *Mitigation:* Update `DeleteExpiredVersions` to explicitly exclude any `DocumentVersion` referenced in `archive_entries` or active `archive_requests`, in addition to keeping independent snapshot copies in `archives/...`.
3. **Hazard: Company Deletion Cascade (`Company::booted()`)**
   - *Risk:* Deleting a company nulls out `company_id` on documents.
   - *Mitigation:* Prevent company deletion if active registered archives or active legal holds exist, and snapshot `company_code_snapshot`, `branch_code_snapshot`, and `unit_code_snapshot` on `archive_entries`.
4. **Hazard: Concurrent Archive Number Allocation**
   - *Risk:* Duplicate or skipped numbers under concurrent requests or failed snapshots.
   - *Mitigation:* Separate `previewNumber()` (read-only, non-locking, clearly labeled indicative preview) from `allocateNumber()`. Perform snapshot copy and SHA-256 verification *before* opening the numbering allocation transaction, then lock the counter row via `lockForUpdate()` inside `DB::transaction`, increment counter, insert `ArchiveEntry`, and commit atomically.

---

## Phase 2: Repository-Specific Implementation Checklist

### 2.1 Database Schema (`database/migrations/2026_11_06_000001_create_archiving_tables.php`)
- [x] `archive_classifications` — Hierarchical classification catalog with default retention binding, confidentiality level, and approval flag.
- [x] `archive_retention_policies` — Versioned retention schedules (JRA) with trigger events, active/inactive years, total retention days, and final disposition (`review`, `destroy`, `permanent`).
- [x] `archive_numbering_schemes` — Configurable numbering templates (`ARC/{COMPANY}/{BRANCH}/{UNIT}/{CLASS}/{YYYY}/{SEQ:06}`), sequence scopes, reset periods, padding, and timezone.
- [x] `archive_number_counters` — Atomic sequence counter rows per `(scheme_id, company_id, scope_key, period_key)`.
- [x] `archive_requests` — Pre-registration intake, eligibility, approval workflow, and idempotency tracking.
- [x] `archive_entries` — Authoritative immutable archive registry with unique `archive_number`, organization snapshots, retention dates, legal hold status, QR token, and JSON metadata snapshot.
- [x] `archive_snapshots` — Immutable storage reference (`archives/...`), SHA-256 content hash, SHA-256 manifest hash, file metadata, and integrity status.
- [x] `archive_retention_assignments` — Traceable retention calculation history and extensions per archive entry.
- [x] `archive_legal_holds` — Multiple independent legal/audit holds per archive entry (`active` / `released`).
- [x] `archive_disposition_requests` — Controlled disposition review/approval workflow and destruction/transfer certificates (`CERT-DISP-...`).
- [x] `archive_relationships` — Lineage graph (`supersedes`, `reactivated_from`, `related`).
- [x] `archive_void_records` — Permanent registry of voided archive numbers so numbers are never reused.
- [x] `archive_events` — Append-only tamper-evident audit log with chained SHA-256 `event_hash`.

### 2.2 Backend Models, Policies & Services
- [x] Models in `app/Models/`:
  - `ArchiveClassification`, `ArchiveRetentionPolicy`, `ArchiveNumberingScheme`, `ArchiveNumberCounter`, `ArchiveRequest`, `ArchiveEntry`, `ArchiveSnapshot`, `ArchiveRetentionAssignment`, `ArchiveLegalHold`, `ArchiveDispositionRequest`, `ArchiveRelationship`, `ArchiveVoidRecord`, `ArchiveEvent`.
- [x] Policies in `app/Policies/`:
  - `ArchivePolicy` and `ArchiveRequestPolicy` registered in `AppServiceProvider`.
- [x] Services in `app/Services/`:
  - `ArchiveAuditService` — Chained SHA-256 append-only event logging + bridge to `AuditService`.
  - `ArchiveNumberingService` — Default seeding, token validation, non-locking `previewNumber()`, and atomic `allocateNumber()` with `lockForUpdate()`.
  - `ArchiveEligibilityService` — Validates document/version status, ONLYOFFICE lock state, pending approvals/signatures, and duplicate request guards.
  - `ArchiveSnapshotService` — Freezes ONLYOFFICE session, copies version file/HTML into `archives/{company_id}/{year}/{uuid}.{ext}`, computes SHA-256 content & manifest hashes, and performs on-demand integrity verification.
  - `ArchiveRetentionService` — Computes retention schedule dates, assigns policies, evaluates expiration, and handles retention extensions.
  - `ArchiveHoldService` — Places and releases legal holds, synchronizes `is_under_legal_hold` flag on `ArchiveEntry`, and logs governance events.
  - `ArchiveDispositionService` — Manages disposition requests, enforces dual-check on legal holds and retention dates, executes disposition (purging snapshot binary while preserving registry tombstone and generating disposition certificate).
  - `ArchiveRegistryService` — Coordinates request submission, approval/rejection/return, idempotent atomic registration, controlled metadata correction, reactivation into a new working copy, and administrative voiding.

### 2.3 Controllers, Routes, Views & Integrations
- [x] `ArchiveController` — Archive Hub (`index`), Archive Detail (`show`), Snapshot Preview (`preview`), Snapshot Download (`download`), Integrity Verification (`verifyIntegrity`), Export CSV/Print (`export`), Metadata Correction (`correctMetadata`), Reactivate as Working Copy (`reactivate`), Void Entry (`void`), Public QR Verification (`verifyByToken`).
- [x] `ArchiveRequestController` — Request Queue (`index`), Create Request Wizard (`create`, `store`), Request Detail (`show`), Submit (`submit`), Approve (`approve`), Reject (`reject`), Return for Correction (`returnForCorrection`), Cancel (`cancel`), Finalize Registration (`finalize`), Format Preview (`previewNumber`).
- [x] `ArchiveGovernanceController` — Governance Center (`index`), Legal Holds (`storeHold`, `releaseHold`), Retention Extension (`extendRetention`), Disposition Workflow (`storeDisposition`, `approveDisposition`, `rejectDisposition`, `executeDisposition`, `certificate`).
- [x] `Admin\ArchiveSettingController` — Admin Configuration (`index`), Classifications CRUD, Numbering Schemes CRUD + Live Token Preview, Retention Policies (JRA) CRUD.
- [x] Protection updates in `PruneTrashedDocuments`, `DeleteExpiredVersions`, `TrashController`, `Admin\DocumentController`, and `Document` / `DocumentVersion` models.
- [x] UI Integration in `navigation.blade.php`, `documents/show.blade.php`, and bilingual translations in `lang/id.json` & `lang/en.json`.

### 2.4 Automated Test Suite (`tests/Feature/Archiving/`)
- [x] `ArchiveNumberingConcurrencyTest` — Sequential allocation, 50-request batch stress test, cross-tenant counter isolation, period rollover, preview non-reservation, and voided number non-reuse.
- [x] `ArchiveWorkflowAndSnapshotTest` — Eligibility enforcement (drafts/ONLYOFFICE locks/pending signatures blocked), request -> approval -> snapshot SHA-256 creation -> atomic registration, and idempotent retries.
- [x] `ArchiveSecurityAndIsolationTest` — Cross-company isolation on list/detail/download/export, sensitivity level access control, immutability of archived bytes, and audited metadata correction.
- [x] `ArchiveTrashAndVersionIsolationTest` — Source document trash & `documents:prune-trashed` and `versions:purge` leave archive entries and snapshot files completely intact.
- [x] `ArchiveRetentionHoldAndDispositionTest` — Retention calculation, overlapping legal holds blocking disposition, partial hold release still blocking, full release + approved disposition generating certificate & tombstone, and reactivation into a new working document with lineage.

