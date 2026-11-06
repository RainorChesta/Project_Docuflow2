# DokuFlow — Enterprise Archiving & Records Management

**Document type:** AI coding-agent master implementation specification and planning prompt  
**Status:** Proposed architecture; validate against the actual repository before implementation  
**Target:** Enterprise, multi-company, multi-branch, multi-work-unit DokuFlow deployments  
**Feature:** Archiving, permanent archive registration numbers, classification, retention, holds, retrieval, and disposition

---

## 0. Instructions for the AI Coding Agent

You are the lead software architect, records-management designer, security engineer, and implementation planner for **DokuFlow**. Your task is to analyze the existing codebase and produce a repository-specific, executable plan for the feature described here. This specification represents the intended **business requirements and preferred architecture**, not evidence that any particular table, framework, route, or service already exists.

### Working rules

1. **Inspect before proposing code.** Identify DokuFlow's actual framework, directory structure, data model, migrations, authentication, tenant boundaries, roles/permissions, storage adapters, queue/scheduler, test infrastructure, ONLYOFFICE integration, signing flows, QR verification, current versioning, approval workflows, and 30-day trash mechanism.
2. **Reuse existing patterns.** Integrate with DokuFlow's document, audit, notification, sharing, and approval modules rather than duplicating functionality or creating parallel permission systems.
3. **Do not assume the application uses Laravel or any named framework.** If it does, use idiomatic native capabilities for database transactions, migrations, authorization, queue jobs, and tests.
4. **Do not immediately implement.** First write a repository-specific `ARCHIVING_IMPLEMENTATION_PLAN.md` with architecture decisions, affected files, migrations, API contracts, UI changes, dependencies, testing strategy, risk assessment, delivery phases, and acceptance criteria. Identify unresolved business-policy questions. Request approval before modifying production code unless the human explicitly authorizes autonomous implementation.
5. **Make safe defaults.** Treat archive numbers as permanent; never overwrite archived file bytes; never let archived files be purged by the existing trash job; never dispose of records automatically in the initial release.
6. **Keep cross-tenant separation absolute.** Tenant/company, branch, work unit, ACL, and legal-hold rules must be enforced server-side, not merely by UI filtering.
7. **Use feature flags and backwards-compatible schema changes.** Preserve all existing documents and version histories.
8. **Treat legislation and retention schedules as policy inputs.** Do not claim that one retention duration applies to all document types or organizations. A legal/compliance owner must validate schedules before production activation.
9. **Make decisions explicit.** Where requirements leave alternatives open, choose a recommended default, explain trade-offs, and mark configurable behavior.
10. **Deliver complete tests and operational documentation** with each implementation phase.

### Required first response from the coding agent

Return, in order: (a) repository findings, (b) gaps versus this specification, (c) proposed architecture with text diagrams, (d) exact database changes, (e) API and UI change inventory, (f) ordered implementation tasks with dependencies, (g) security and migration hazards, (h) detailed tests, (i) questions requiring business approval, and (j) an MVP versus later-phase release recommendation.

---

## 1. Existing DokuFlow Context

DokuFlow is an enterprise document workflow and lifecycle management application for organizations such as healthcare and clinic networks. It supports:

- Multiple companies, branches, and work units.
- Document creation, uploading, collaborative ONLYOFFICE editing, version control, rollback, numbering, and QR verification.
- Configurable approvals, electronic signatures, corporate stamp authorization, and delegated approval.
- Individual, work-unit, link-based, and cross-branch sharing.
- Real-time notifications and a notification center.
- AI summaries, PDF preview/export, access and activity audit trails, and soft-delete trash recovery with a 30-day auto-purge.

**Integration requirement:** Archiving must operate as a first-class extension of this existing document lifecycle. It must not create a disconnected document repository, bypass a sign-off requirement, or alter the meaning of existing document numbers.

---

## 2. Product Goals, Terminology, and Scope

### 2.1 Goals

- Preserve important or finalized documents as trustworthy business records.
- Assign an **automatically generated, uniquely registered archive number** to each archived record.
- Allow staff to locate archived records reliably across authorized organizational boundaries.
- Protect content integrity, signed evidence, and historical metadata.
- Apply configurable retention and authorized disposal policies.
- Provide evidence of who archived, accessed, transferred, placed on hold, restored, or disposed of each record.
- Support enterprise-scale search, export, policy management, and auditability.

### 2.2 Distinguish key identifiers

| Concept | Purpose | Behavior |
|---|---|---|
| Document ID | Internal DokuFlow primary key | Never exposed as sole business reference |
| Document Number | Existing business/document numbering | Remains unchanged |
| Document Version | Existing version identifier | Archive points to an exact approved/finalized version |
| Archive Entry ID | Internal archive registration entity UUID/ID | Stable technical reference |
| **Archive Number** | Human-readable official archive registration | Assigned upon successful registration; permanent; never reused |
| Archive Classification Code | Taxonomy, e.g., `HR-PER` | Configurable per company; not itself a unique number |
| Storage/Box Code (optional) | Physical or hybrid custody locator | Does not replace the archive number |

### 2.3 Recommended scope

**MVP / initial release:** archive requests; archive approval (configurable); archive registration numbering; archive register/list/details; immutable original-version preservation; metadata/classification; permission enforcement; search and filters; audit; retention policy attachment; reminders; restore-as-new-working-copy or reactivation with historic snapshot intact; safe integration with the trash system.

**Phase 2:** legal-hold workflows; formal disposition review and certificates; bulk archive; advanced retention lifecycle; archive exports; full-text search where permission-safe.

**Phase 3 / optional:** physical box/shelf tracking; transfer of custody; advanced records-series hierarchy; archival package export; policy-controlled AI summaries; long-term format conversion/PDF/A; integrations with external archive or e-signature trust services.

**Out of scope unless separately approved:** mandatory government certification, legal determination of every document retention period, automatic irreversible destruction of live records, migration of every existing document into an archive, and replacement of the existing document management workflow.

---

## 3. Core Architectural Decisions

1. **Archive registry as a separate domain:** Create an `archive_entries` domain/table rather than storing only `documents.is_archived` or `documents.archive_number`. A document can have a historical series of archive registrations if later reactivated and rearchived; old entries remain intact.
2. **Immutable archival snapshot:** An archive entry references a specific finalized document version and a durable, independently retrievable immutable copy or immutable object version. Capture its content hash and metadata manifest. Do not point only to the latest mutable document file.
3. **Independent numbering:** Archive numbers use a distinct sequence service and policy from normal document numbering.
4. **Separate request and registered states:** A request may be pending or rejected without receiving an archive number. An archive number is minted only during successful final registration.
5. **Policy-driven retention:** Retention belongs to an archive classification/policy snapshot and may be extended or held. The existing trash purge must never delete archival storage or registered archives.
6. **Document history remains authoritative:** Retain existing version, approval, signature, stamp, and audit links. The archive stores references plus a point-in-time manifest for stable historical interpretation.
7. **Backend-enforced security:** Every archive action passes company, ACL, classification, and action-permission checks.
8. **Audit event model:** Material changes must create an append-only event; correction of archive metadata creates a correction event, not silent replacement of historical evidence.
9. **Asynchronous heavy operations:** Large file snapshotting, checksums, PDF renditions, exports, and notifications may run in queues, but final registration and numbering must be transactionally consistent.

### 3.1 Conceptual flow

```text
Existing Document / Version / Approvals / Signatures
                       |
                       v
              Request Archive
                       |
            Eligibility + ACL checks
                       |
               [Approval needed?]
                 /           \
              Yes             No
               |               |
        Archive approver       |
               \               /
                v             v
           Lock chosen version and prepare snapshot
                       |
             Durable immutable storage
                       |
                Checksum verified
                       |
          Atomic archive registration + number
                       |
               Archived / Registered
                       |
         Retrieve / Search / Audit / Export
                       |
           Retention monitoring / Legal hold
                       |
             Disposition review (later phase)
                       |
        Approved disposal / Permanent preservation
```

---

## 4. Roles and Authorization Matrix

These are capabilities, **not hard-coded role names**. Map them to DokuFlow's actual permissions and existing roles.

| Capability | Staff / Document Owner | Unit Head / Approver | Archivist / Records Officer | Compliance / Legal | Tenant Admin | Auditor |
|---|---:|---:|---:|---:|---:|---:|
| Request archive | Own/authorized | Authorized | Yes | Authorized | Policy-based | No |
| Approve archive | No | If assigned | If assigned | If assigned | Policy-based | No |
| Register/archive | No | No | Yes | Policy-based | Policy-based | No |
| Browse/read archives | ACL only | ACL only | Assigned scope | Authorized scope | Authorized scope | Read-only ACL |
| Edit archive content | **Never** | **Never** | **Never** | **Never** | **Never** | **Never** |
| Correct metadata | No | No | With reason + audit | With reason + audit | Policy-based | No |
| Configure numbering | No | No | Optional | No | Yes | No |
| Configure retention/classification | No | No | Propose | Approve/policy owner | Authorized | No |
| Place/release legal hold | No | No | Request | Authorized | Cannot bypass compliance control | No |
| Request reactivation/working copy | Authorized | Approve if required | Yes | Policy-based | Policy-based | No |
| Approve disposition | No | Policy-based | Propose | Required reviewer | Additional approver | No |
| View full audit | No | Scoped | Scoped | Authorized | Authorized | Authorized read-only |

**Security rules:**

- Every query and action must scope by `company_id`/tenant at the database/service boundary.
- Branch/unit access must follow the existing ACL rules, including cross-branch grants. Archiving does not automatically widen access.
- Existing public/link shares must not silently turn into permanent archive shares. Expire or re-evaluate links on registration; use separate restricted archive links only if policy permits.
- Authorization applies equally to list, count, search suggestion, export, download, QR verification, audit views, and API errors to avoid existence leakage.
- A company administrator must not be able to delete records under legal hold merely by changing configuration.
- Handle reassigned staff, departed employees, renamed branches, and moved work units without erasing historical ownership or accidentally granting access.

---

## 5. Functional Requirements

### 5.1 Archive request wizard

1. Eligible users select **Archive** on a document or initiate a request in the Archive Hub.
2. Display source title, document number, exact version, owner, company, branch, work unit, approval and signing status, and existing retention information.
3. Capture/confirm classification, archive title, description, record date, relevant effective/closure date, retention policy, sensitivity level, archive reason, and optional tags.
4. Show proposed number **format only** or an explicitly non-reserved illustration. Do not claim a live number before registration.
5. Run preflight checks: finalized/approved status under configured policy, no active ONLYOFFICE write session, version still valid, no unresolved signature or stamp action, no deleted document, permissions, classification validity, and storage availability.
6. Save request as `draft` or `pending_approval`. The request can be canceled before registration by authorized users.
7. Show actionable validation messages. Never silently archive a partially approved or concurrently edited document.

### 5.2 Approval/rejection

- Reuse existing configurable approval engine and delegation model where possible.
- Configurable policies: direct archiving by designated archivists or one/multiple approvals by unit head/records officer.
- Approval routes must follow company/branch/work-unit rules and delegation limits.
- Support approve, reject with reason, return for correction, cancel, reminder, and deadline/escalation if existing engine supports them.
- Recheck eligibility and permission at final registration, not only at request creation.
- Approval notifications integrate with DokuFlow's existing real-time hub.

### 5.3 Final archive registration

- Freeze the chosen document version and prevent further in-place edits during finalization.
- Persist an immutable copy or protected object version plus required metadata/signature evidence.
- Compute SHA-256 checksum; validate stored bytes are retrievable and match the intended version.
- Register an archive number **exactly once** within an atomic database transaction.
- Snapshot the numbering-rule version, company/branch/unit codes, classification, retention policy, version ID, signatory evidence IDs, and relevant ACL context.
- Store actor, timestamp, registration reason, and outcome in audit log.
- Update visible document lifecycle status and link to archive entry without discarding existing versions.
- Notify initiator and relevant custodians only after commit.
- If processing fails, show `registration_failed` with recoverable diagnostics; do not create a phantom archive or duplicate number.

### 5.4 Archive registry and details

Each record displays:

- Archive number (copyable), original document number, document title, classification, source version, owner, company/branch/unit, dates, sensitivity, and registration status.
- Archived by, approved by, archival timestamp, archive reason, and retention schedule/deadline.
- Source approval/signature/corporate stamp evidence and QR verification link where permitted.
- Preview/read-only original; optional derived PDF preview with clear provenance, never presented as the original signed binary if not identical.
- Download/export permitted by ACL; immutable integrity hash and integrity verification result where appropriate.
- Full version lineage, original document link, restoration/reactivation history, metadata corrections, legal holds, access audit, and eventual disposition record.
- Retrieval actions, subject to permissions: open, request access, export metadata, copy number, request reactivation, request disposal.

### 5.5 Archive search and bulk operations

- Search title, archive number, document number, tags, classification, custodian, approved metadata, date range, and permitted full text.
- Filter by company/branch/unit, document type, sensitivity, year, retention status, registrar, legal hold, and archive state.
- Server-side pagination, indexed sorting, export, saved filters (optional), and scoped counts.
- Bulk requests/exports must process each record through the same authorization and validation checks as single-item operations.
- Avoid full-text indexing of restricted content unless the index supports matching tenant/ACL isolation and deletion/reindex policies.

### 5.6 Corrections, restoration, and rearchiving

- Archive **bytes and registered number never change**. Metadata corrections require permission, rationale, old/new values, actor, timestamp, and audit record; number corrections are not routine metadata edits.
- Recommended default: **Reactivate as a new working copy/revision linked to the archive**, leaving the archived snapshot intact. Do not mutate the original registered artifact.
- If a copy is rearchived later, create a **new archive entry and new archive number**, linking predecessor/successor records. Do not recycle the old number.
- If organizational policy allows reactivation of the original document, preserve the old archive entry and immutable snapshot; clearly distinguish its historical registration from the current editable state.
- Never let 'restore' become a way to bypass hold, custody, retention, approvals, or document access rules.

### 5.7 Hybrid/physical archive extension (optional)

Add storage location hierarchy (building, room, rack, shelf, box, folder), condition, custodian, checked-out-to, checkout/return dates, and barcode/QR tracking. Physical location numbers are independent of archive numbers.

---

## 6. Archive Numbering Engine — Critical Feature

### 6.1 Recommended default format

```text
ARC/{COMPANY}/{BRANCH}/{UNIT}/{CLASS}/{YYYY}/{SEQ:06}

Illustrative example:
ARC/ACME/HQ/HR/PER/2026/000001
```

**Token definitions**

| Token | Meaning | Example |
|---|---|---|
| `ARC` | Static configurable prefix | `ARC` |
| `COMPANY` | Immutable organization code captured at registration | `ACME` |
| `BRANCH` | Branch code captured at registration | `HQ` |
| `UNIT` | Work-unit code captured at registration | `HR` |
| `CLASS` | Archive classification code | `PER` |
| `YYYY` | Year of actual registration in company timezone | `2026` |
| `SEQ:06` | Sequence padded to six digits | `000001` |

Alternate supported patterns might include `ARC/{COMPANY}/{YYYY}/{SEQ:06}` and `AR/{BRANCH}/{YYYY}/{SEQ:05}`. Configuration must support controlled token selection, separators, padding, period reset policy, scope, and sample preview.

### 6.2 Numbering policy configuration

- Set policies per company, with optional branch/unit-specific overrides if organizational policy requires them.
- Recommended sequence scope: **one counter per company per registration year**, even when branch, unit, and class appear in the human-readable prefix. Make other scopes explicit if supported.
- Supported period reset: annually by default; optional never reset. Monthly reset is an optional later addition.
- Sequence values start at configurable positive integer; enforce max width/overflow handling and collision validation.
- Use the **company's configured timezone** for the registration period; store the resulting year/period as a separate immutable field.
- Snapshots preserve codes and formatting if branch/unit/company labels or template configuration later change.
- When numbering configuration changes, create a new **versioned scheme**; past numbers remain unchanged.
- Do not allow an administrator to silently change an active scheme's scope, reset history, or reuse the last assigned value.
- Validate each code token against an allowed character set, escaping/reserving separators to prevent ambiguity.

### 6.3 Allocation guarantees and algorithm

**Never generate the final number from `MAX(archive_number) + 1`, a random number, an in-memory counter, or a client-side preview.**

Recommended sequence:

1. Complete approval and secure the immutable storage snapshot first.
2. Confirm the underlying document/version has not changed and hold the finalization lock.
3. Start a database transaction.
4. Lock or atomically update the counter row for `(scheme_version_id, company_id, scope_key, period_key)`; use unique constraints and retry safe insertion races.
5. Increment sequence, format archive number, and verify global uniqueness within company.
6. Insert `archive_entries`, immutable number metadata, current retention snapshot, and registration audit/outbox event; update the source document registration link/state.
7. Commit. A failed transaction must not leave a registered number or confirmed record. Notifications happen after commit.
8. On retries, use idempotency key and the existing request/registration to return the same result instead of allocating a second number.

Illustrative **pseudocode**, to adapt to the actual database/ORM:

```pseudo
function finalizeArchive(requestId, idempotencyKey):
    request = loadAuthorizedRequest(requestId)
    assert request.approved && request.snapshot_ready
    assert snapshotStoredAndVerified(request.snapshot)
    assert sourceVersionStillEligible(request)

    transaction:
        lock(request)
        if request.registered_archive_entry_id exists:
            return existing_archive_entry

        scheme = resolveActiveNumberingScheme(request.company)
        period = companyLocalDate(now()).year
        scope = scheme.resolveScope(request.company, request.branch, request.unit)
        counter = getOrCreateAndLockCounter(scheme.version, scope, period)
        nextValue = counter.last_value + 1
        number = scheme.render(request.metadataSnapshot, period, nextValue)

        insert archive_entry(
            request_id=request.id,
            company_id=request.company_id,
            archive_number=number,
            sequence=nextValue,
            number_scheme_version_id=scheme.version,
            snapshot_id=request.snapshot.id,
            status='registered',
            ...
        )
        update counter.last_value = nextValue
        mark request as registered
        mark source document as archived under configured rules
        append audit event + outbox notification
    commit
    return archive_entry
```

**Critical constraints:**

- Unique `(company_id, archive_number_normalized)` on `archive_entries`.
- Unique `(request_id)` for successful archive registration, unless explicit multi-registration semantics are modeled; use explicit cycle IDs when needed.
- Unique `(scheme_version_id, scope_key, period_key)` on counters.
- A registration must never be renumbered, including after archival withdrawal or disposal.
- Prefer **nonreuse and traceability** over a legally unverified guarantee of gapless sequences. Maintain a void/cancellation ledger where a registered number is subsequently voided. Database rollbacks should not consume a committed number, but deployment failures and formally voided records may legitimately leave gaps.
- Hard-delete of a registered archive entry is forbidden; keep at least a secured audit/tombstone record after authorized disposition.

### 6.4 Edge cases to test

- 100 simultaneous archive requests by one company in the same year.
- Different companies creating numbers simultaneously; no cross-tenant conflict.
- Two branches sharing a company-wide annual sequence.
- Midnight/year rollover in `Asia/Jakarta` or each company's timezone.
- Backdated source document date does not silently change the **registration year**.
- Branch/unit rename or move after numbering; old numbers stay identical.
- Template change during an in-flight request; pin scheme version at finalization or explicitly define scheme selection point.
- Retries, worker crashes, network timeouts, double clicks, duplicate events, and storage errors.
- Generated number exceeds padding; block cleanly or auto-expand only if policy authorizes it.
- Import from legacy archive numbers; conflict quarantine instead of overwriting.
- Readonly archive restore/rearchive creates a new registration rather than taking the old number.

---

## 7. Classification and Archive Metadata

### 7.1 Classification hierarchy

Support tenant-configurable hierarchical record categories such as:

```text
Corporate Records
├── Corporate Governance
│   ├── Board Resolutions
│   └── Policies and Procedures
├── Human Resources
│   ├── Personnel Administration
│   └── Recruitment
├── Finance
│   ├── Invoices and Payments
│   └── Contracts
├── Operations
│   ├── SOPs and Quality Assurance
│   └── Branch Operations
└── Healthcare / Clinic Operations (only where allowed)
    ├── Clinic Administrative Records
    └── Sensitive Clinical Records (restricted; distinct schedules)
```

### 7.2 Mandatory archive metadata

- `company_id`, source `document_id`, source `document_version_id`.
- `archive_entry_id`, immutable `archive_number`, registration timestamp/timezone.
- Title, original document number, document type, archive classification code/ID.
- Originating branch/unit IDs **and** their registration-time label/code snapshots.
- Creator/custodian, archivist/registrar, request/approval references.
- Business/record date and event/closure date if retention depends on them.
- File storage object version/key, original filename/MIME/size, SHA-256 hash, immutable manifest.
- Confidentiality/sensitivity category, retention policy ID/version and computed trigger/due date.
- Archive status, legal-hold flag derived from hold records, audit history.

Optional: keywords, case/project references, description, language, external archival references, physical location, related archive entries, authorized checksum attestations, metadata correction notes.

Avoid duplicating unnecessary personal data in metadata or notifications. AI-generated summaries must remain optional, classified, and subject to the same access controls.

---

## 8. Suggested Data Model

**Model/table names are recommendations. Map to the repository's existing naming conventions.**

| Entity | Core fields | Essential constraints |
|---|---|---|
| `archive_classifications` | id, company_id, parent_id, code, label, description, confidentiality_default, enabled | unique code per company/parent or defined taxonomy scope |
| `archive_numbering_schemes` | id, company_id, version, template, sequence_scope, reset_period, padding, start_value, timezone, active_from, active_until | only one effective scheme per configured scope/time |
| `archive_number_counters` | scheme_version_id, company_id, scope_key, period_key, last_value | unique counter scope; row-locked/atomic |
| `archive_requests` | id, company_id, document_id, requested_version_id, requested_by, metadata, state, approval_workflow_id, idempotency_key, snapshot_job_state, error_code | request state and idempotency uniqueness scoped by company |
| `archive_entries` | id, company_id, document_id, document_version_id, request_id, archive_number, normalized_number, scheme_version_id, sequence, registration_period, registered_at, classification_id, status, metadata_snapshot | unique `(company_id, normalized_number)`; immutable identifier fields |
| `archive_snapshots` | id, archive_entry_id/request_id, object_key, object_version, content_hash_sha256, manifest_hash, size, MIME, copied_at, validated_at, immutability_mode | snapshot cannot change once registered |
| `archive_retention_policies` | id, company_id, name, classification_id, version, start_event, active_period, inactive_period, final_action, approval_requirements, enabled | versioned; no untracked retroactive edits |
| `archive_retention_assignments` | id, archive_entry_id, policy_version_id, trigger_date, eligible_from, calculation_snapshot, status | history of recalculations/change reasons |
| `archive_legal_holds` | id, company_id, archive_entry_id or defined scope, case_reference, reason, applied_by, applied_at, released_by, released_at, status | no silent bypass; audit |
| `archive_events` | id, company_id, archive_entry_id, event_type, actor_id, occurred_at, request_id, before/after safe metadata, hash/trace | append-only; indexed by archive/time |
| `archive_disposition_requests` | id, archive_entry_id, requested_by, due_date, justification, approval_workflow_id, decision, status, certificate_ref | no disposal while held or not eligible |
| `archive_relationships` | id, from_entry_id, to_entry_id, relation_type | trace reactivation, supersession, migration |
| `archive_void_records` | id, company_id, archive_number, archive_entry_id, void_reason, voided_by, voided_at | preserve number history, never reuse |

**Additional design checks:**

- Prefer foreign keys into existing company/branch/work-unit/user/doc/version tables.
- Include company IDs in security-sensitive joins and validate both sides have the same company.
- Index `(company_id, registered_at)`, `(company_id, status, classification_id)`, `(company_id, retention_due_at)`, `(company_id, document_id)`, and normalized archive number.
- Separate mutable display/cache fields from registration-time immutable snapshots.
- Avoid an unconstrained writable `archived` boolean that can drift away from archive registry state.
- Define precise behavior when user or branch records are deactivated: preserve historical snapshot, not dangling evidence.
- Do not store secrets, raw signature private keys, or unrestricted signed file URLs in archive metadata.
- Define on-delete restrictions/cascades explicitly; never cascade-delete registered archives when a document, user, or unit is removed.

---

## 9. State Machines and Lifecycle Rules

### 9.1 Archive request states

```text
draft -> pending_approval -> approved -> preparing_snapshot
                         \-> rejected
                         \-> returned_for_correction -> pending_approval
approved/preparing_snapshot -> registration_failed -> retry_preparation
preparing_snapshot -> ready_to_register -> registered
unregistered requests -> canceled (if authorized)
```

Treat `registered` as terminal for the request; use new commands/records for subsequent operations. All transitions require explicit authorization and invariant validation.

### 9.2 Archive entry states

- `registered`: immutable archive exists; searchable and retrievable under policy.
- `under_review`: formal legal/retention/disposition review (optional status or separate workflow).
- `eligible_for_disposition`: derived based on retention, holds, and policies; **not** equivalent to approval for deletion.
- `disposition_pending`: authorized multi-step review; archive remains immutable.
- `disposed`: disposal completed and storage deletion verified; metadata tombstone and evidence retained under applicable policy.
- `permanent`: final retention is preservation; not eligible for automatic disposal.
- `voided`: registration administratively invalidated, with permanent number ledger/audit; no number reuse.

**Do not model legal hold only as a lifecycle state.** A record can be registered *and* under one or more overlapping legal holds simultaneously.

### 9.3 Transition invariant examples

- No final registration until archive bytes are durably stored and verified.
- No in-place editing, version rollback, document replacement, or overwrite of a registered snapshot.
- No disposal while any active hold exists or a blocking statutory/business requirement applies.
- No silent movement from `disposed` back to `registered`.
- No automatic purge of registered entries through document trash cleanup.
- Every state mutation emits a structured audit event with actor, reason, and request correlation.

---

## 10. Retention, Legal Hold, and Disposition

### 10.1 Retention policy engine

- Each company configures its own **Jadwal Retensi Arsip (JRA) / retention schedule** according to legal and organizational policy, potentially varying by document classification.
- Support retention triggers: archival registration date, document issue date, effective date, closure date, employment separation date, contract end date, or manually confirmed business event.
- Support active + inactive retention periods where required; final outcomes: review, destroy, permanent preservation, or transfer.
- Store policy version and calculated deadline as a historical snapshot. Policy updates require documented recalculation and approval rules.
- Gracefully handle unknown triggers, missing dates, retrospective corrections, time zones, and business event changes.
- Schedule notifications for upcoming reviews and generate audit-ready retention status reports.
- **Do not hardcode a universal retention duration.** In healthcare contexts, clinical records and corporate administrative documents may have different legal obligations and sensitivities.

### 10.2 Legal holds

- Authorized compliance personnel can place scoped holds on single records or defined groups/cases, with case reference and reason.
- Active holds override routine disposal eligibility and block destruction or tampering.
- Support multiple simultaneous holds; releasing one does not release the others.
- Hold creation/release requires audit, notification, and permission checks; consider dual authorization for release.
- Re-evaluate holds inside the final disposal execution transaction, not only when disposal was requested.

### 10.3 Disposition (not in MVP unless approved)

1. Detect eligibility based on effective retention policy and available event date.
2. Create a **review queue**, not an automatic deletion job.
3. Require records officer/compliance approver(s) and a reason; retain decision evidence.
4. Perform a final recheck of legal holds, unresolved litigation/investigation flags, and data subject obligations before executing deletion.
5. Delete or render inaccessible authorized stored objects using a verified storage process; document replicas, exports, backups, and caches according to the applicable retention and erasure program.
6. Only mark `disposed` once the operation is confirmed; keep protected tombstone metadata, identifier, minimal hash/evidence, and disposition certificate as law/policy permits.
7. If storage deletion fails, mark retryable failure and alert; **do not misreport success**.

### 10.4 Interaction with 30-day trash

- The existing 30-day trash is for recoverable **ordinary soft-deleted working documents**, not registered archives.
- Modify cleanup queries/services so archival snapshots and registered archive entries are categorically excluded.
- If a source working document is trashed or purged, the registered archive remains retrievable with its independent immutable snapshot and retained references/snapshots.
- Restrict deletion of currently archived documents through explicit business rules; avoid deleting source rows whose FKs are needed for archive integrity.
- Test trash scheduler, manual delete endpoints, bulk delete, storage cleanup, and database cascade paths.

---

## 11. Security, Privacy, Integrity, and Compliance

- **Tenant isolation:** strictly scope reads, writes, attachments, search indices, exports, and scheduled jobs by company/tenant.
- **Fine-grained ACLs:** integrate with existing sharing, role permissions, sensitivity labels, unit hierarchy, and cross-branch authorization.
- **Least privilege:** default archived content to read-only and restricted; avoid automatic broad access from legacy sharing links.
- **Encrypted transport and storage:** follow DokuFlow's actual KMS/key-management and encrypted-at-rest approach; never embed credentials in QR URLs.
- **Integrity:** SHA-256 hash original bytes; preserve file-size/MIME/hash metadata; optionally run scheduled integrity checks; protect object versions against overwrite/deletion by app credentials.
- **Evidence:** preserve signature/stamp records and verification metadata; do not claim that a derived PDF automatically retains a source digital signature's cryptographic validity.
- **Audit:** log registration, read/download/export where policy requires, permission denial, corrections, holds, reactivation, disposition, and admin policy changes. Apply tamper-resistant log controls and appropriate retention.
- **QR verification:** provide only a minimal public validation result where permitted; require authentication for sensitive details, metadata, content, and audit history. Use signed, expiring token links if applicable, not predictable access URLs.
- **AI:** if existing summarization is reused, never send restricted archival content to third-party AI providers unless explicit company policy, access, contractual controls, and processing basis permit it.
- **Data minimization:** mask sensitive metadata in notifications, search snippets, logs, exports, and previews. Consider health information sensitive by default.
- **Operations:** backups, recovery testing, corruption detection, key rotation, storage access logs, and incident response must include the archive repository.
- **Compliance review:** map records requirements to the appropriate laws, regulations, and company schedules. Implementation of technical controls is **not by itself a certification or legal opinion**.

---

## 12. UI/UX Information Architecture

### 12.1 Navigation proposal

```text
Documents
  All Documents
  My Documents
  Approvals
  Trash

Archives  [NEW]
  Archive Register
  My Archive Requests
  Pending Archive Approvals
  Retention & Review (permission-based)
  Legal Holds (phase 2; permission-based)
  Disposition Queue (phase 2; permission-based)
  Reports & Exports

Settings
  Archive Classification
  Archive Numbering Rules
  Retention Policies
  Archiving Permissions
  Archive Storage / Location (optional)
```

### 12.2 Screen inventory

**A. Archive Register** — count cards and searchable table with columns: archive number, title, original doc number, classification, company/branch/unit, registration date, registrar, retention due date, security label, status, quick actions. Include scoped filtering, sorting, pagination, selection, and CSV/PDF export subject to permissions.

**B. Archive Request Wizard** — source/version review, classification + metadata, retention preview, approval route, safe number *format preview*, review/submit. Display clear read-only/irreversibility notice.

**C. Archive Detail** — prominent archive number, registration certificate metadata, read-only preview, original document lineage, sign-off evidence, activity timeline, access panel, retention timeline, holds, integrity check, export, and request reactivation actions.

**D. Numbering Rule Settings** — token builder/template editor, sample rendering, counter scope, reset policy, timezone, effective date, version history, syntax validator, collision checks, confirmation for scheme changes, **no editable existing archive numbers**.

**E. Classification and Retention Settings** — tree editor, categories, sensitivity defaults, policy assignment, triggers, retention spans, dispositions, policy history, preview of deadline calculations.

**F. Request/Approval Inbox** — reused approval interface with archive-specific details, decision reason, and notifications.

**G. Retention/Disposition Dashboard** — upcoming reviews, overdue records, held records, pending approvals, preservation exceptions, certificates, and exportable reports; phase according to release scope.

### 12.3 Experience requirements

- Reuse DokuFlow design system, layout, typography, icons, tables, forms, modal conventions, toasts, and permissions-aware navigation.
- Responsive desktop/tablet usability; mobile-safe read-only views where applicable.
- Search state/deep links, empty states, loading skeletons, retries, validation, keyboard accessibility, and confirmations for consequential actions.
- Support user-facing English/Indonesian translations if the app already supports localization.
- Avoid displaying a final archive number until database registration succeeds.

---

## 13. API and Service Contracts (Illustrative)

Use repository conventions (REST/GraphQL/actions) and existing authentication; **these are proposed operations, not known current endpoints**.

| Operation | Suggested endpoint/command | Notes |
|---|---|---|
| List archives | `GET /api/archives` | Server-side ACL, filtering, pagination |
| Get archive detail | `GET /api/archives/{id}` | Tenant scope, restricted metadata |
| Create request | `POST /api/archive-requests` | Document + version + metadata; idempotent |
| View request | `GET /api/archive-requests/{id}` | Scoped |
| Submit request | `POST /api/archive-requests/{id}/submit` | Preflight validation |
| Approve/reject | `POST /api/archive-requests/{id}/decision` | Integrate existing approvals |
| Finalize registration | `POST /api/archive-requests/{id}/finalize` | Privileged server-side action; async orchestration |
| Preview number format | `POST /api/archive-numbering/preview` | Preview is **not reserved** |
| Read scheme/config | `GET /api/archive-numbering/schemes` | Authorized admin |
| Add new scheme version | `POST /api/archive-numbering/schemes` | No silent mutation of used schemes |
| Retrieve archived file | `GET /api/archives/{id}/content` | Short-lived authorized access; audit |
| Export metadata | `GET /api/archives/export` | Tenant-safe; large exports async |
| Request reactivation | `POST /api/archives/{id}/reactivation-requests` | Preserves registered snapshot |
| Legal hold | `POST /api/archives/{id}/holds` | Phase 2; compliance only |
| Request disposition | `POST /api/archives/{id}/disposition-requests` | Phase 2; reviewed, no auto-delete |
| Events | `GET /api/archives/{id}/events` | Scoped audit |

### 13.1 Contract standards

- Define request/response JSON schema, field validations, allowed states, status codes, permission errors, and error codes.
- Use a stable `Idempotency-Key` for archive creation/finalization and bulk operations.
- Return `409 Conflict` or equivalent on version/state race, rather than continuing with a stale snapshot.
- Sensitive `403`/`404` behavior should follow an explicit non-disclosure policy; avoid leaking existence across tenants.
- Support pagination/filter parameters with whitelisted sorting; never concatenate unsafe SQL.
- Include tracing/correlation IDs without logging document contents or private fields.
- Use server-generated URLs and short-lived authorization for object storage; never expose permanent public file paths.

---

## 14. Service/Component Architecture

Recommended logical components; align with the actual stack:

1. `ArchiveEligibilityService` — determines eligible documents, approvals, version validity, and editing locks.
2. `ArchiveRequestService` — request state changes and source/document associations.
3. `ArchiveApprovalAdapter` — integrates existing workflow/delegation mechanisms.
4. `ArchiveSnapshotService` — finalizes ONLYOFFICE session, persists immutable original, validates hash, builds manifest.
5. `ArchiveNumberingService` — config/version selection, scope calculations, transaction-safe sequence generation.
6. `ArchiveRegistryService` — atomic registration and lifecycle linking.
7. `ArchiveAuthorizationPolicy` — centralized tenant, ACL, role, legal-hold, and security-label checks.
8. `ArchiveSearchService` — permission-filtered query and index management.
9. `ArchiveRetentionService` — policy snapshots, deadlines, monitoring, and notices.
10. `ArchiveHoldService` / `ArchiveDispositionService` — controlled phase-2 activities.
11. `ArchiveAuditService` — structured, append-only event publishing with transactional outbox.
12. `ArchiveNotificationAdapter` — reuses DokuFlow's existing toast/bell/notification center.
13. `ArchiveIntegrityJob` — periodic storage/hash verification where affordable.

### 14.1 Robust external-storage choreography

Do **not** assume a relational database transaction also covers object-storage writes. Recommended orchestration:

1. Authorization and eligibility checks.
2. Freeze the selected source version and coordinate/finish any live ONLYOFFICE edit session.
3. Write a uniquely keyed, durable archive candidate to protected storage **before** final DB registration.
4. Verify stored object retrieval, size/hash, and manifest; ensure the object cannot be silently overwritten after registration.
5. Recheck version/eligibility and atomically register number + archive entry + document state in DB.
6. Emit outbox event and notifications after commit.
7. Track unregistered storage candidates as orphans; reconcile/retry/clean them safely without ever deleting registered snapshot objects.
8. On failure, keep request retryable and visible to authorized operators; do not falsely show archive as completed.

Use process locks, row locks, state versions/optimistic locking, idempotent jobs, retry backoff, and dead-letter alerting according to existing infrastructure.

---

## 15. Integrations with Existing DokuFlow Features

| Existing capability | Required archiving behavior |
|---|---|
| ONLYOFFICE collaboration | Stop or finalize edit session; archive an exact version; archived original opens read-only |
| Document versioning | Archive points to a fixed version and preserves predecessor/successor lineage |
| Existing document numbering | Never overwrite it; display it next to the archive number |
| Multi-tier approvals | Reuse routing, approvers, delegation, evidence, and notification rules |
| Digital signatures + stamps | Retain original signature artifacts and verification metadata; do not silently flatten evidence |
| QR verification | Differentiate document verification from archive-registration verification; minimize public disclosure |
| Granular sharing | Restrict archive access to authorized individuals/units/branches; reassess link sharing |
| Cross-branch distribution | Respect document distribution rights; archiving does not imply new distribution grants |
| Notifications | Emit submitted, awaiting approval, approved, rejected, registered, registration failed, retention due, legal hold, and disposition updates |
| AI summaries | Only if company policy and ACL permit; mark generated summaries non-authoritative |
| PDF preview/export | Reuse where possible; preserve original format and identify derived PDFs |
| Audit log | Extend common audit stream and structured archive event records |
| Trash + 30-day purge | Entire archive repository is excluded; guarantee no cascade data loss |

---

## 16. Reporting and Operational Monitoring

### 16.1 Dashboard metrics

- Total registered archives per company, branch, unit, classification, and year.
- Registration throughput, failure/retry counts, approval turnaround.
- Records due for retention review, on hold, overdue, pending disposition.
- Storage consumed per tenant/classification and projected growth.
- Integrity check failures, denied access spikes, missing storage objects.
- Numbering collision attempts, counter inconsistencies, unusual registration volume.

### 16.2 Alerts and runbooks

- Stuck archive requests and failed finalizations.
- Unable to create unique archive number or lock sequence row.
- Snapshot object missing/hash mismatch.
- Trash cleanup attempted to target a registered archive.
- Retention and disposition backlog; legal-hold conflicts.
- Backup/restore validation failures.

Runbooks must include safe retries, orphan reconciliation, hash mismatch escalation, access revocation, incident handling, and storage recovery tests.

---

## 17. Migration, Rollout, and Backwards Compatibility

1. Inventory existing schema, documents, versions, numbering services, permissions, active integrations, and cleanup jobs.
2. Choose additive migrations first: archive tables, indexes, permissions, optional linkage fields; avoid destructive changes.
3. Implement architecture behind feature flags, ideally per company/tenant.
4. Seed tenant defaults: classifications, numbering template, archive roles, and **unapproved retention-policy templates that must be reviewed before activation**.
5. Make source document archive linkage null-safe and backwards compatible.
6. Test the 30-day trash cleanup against registered archive objects before production rollout.
7. For existing finalized documents, expose **archive as an explicit user action**; do not auto-mint numbers for millions of historical records.
8. Offer controlled import/backfill for legacy archives. Preserve existing external numbers as separate legacy references; never collide with the new number series.
9. Pilot one company and a few branches; measure performance, usability, and audit correctness.
10. Deploy with backwards-compatible code and staged schema changes; verify background jobs, permissions, cache/index consistency, and storage backup policies.
11. Maintain rollback plan for feature availability, not destructive rollback of already registered archive records.
12. Expand rollout only after testing number uniqueness, immutable storage retrieval, and legal/records policy sign-off.

---

## 18. Testing Strategy and Acceptance Criteria

### 18.1 Unit tests

- Archive eligibility, state transitions, approval paths, role checks, retention calculations, and policy inheritance.
- Number token renderer, per-company/year scope, padding, timezone rollover, template validation, and immutability.
- Idempotency key semantics and detection of stale document versions.
- Hash calculation and verification, metadata snapshot correctness, legal-hold eligibility checks.

### 18.2 Integration tests

- Document/version/ONLYOFFICE integration, approval workflow delegation, signing/stamp evidence.
- Register successful archive and query by archive number; access only via existing ACL.
- Register two distinct archives; uniqueness of numbers and no duplicate registrations.
- Confirm 30-day trash deletion leaves archive snapshot and register intact.
- Cross-branch sharing, revocation, and company isolation across search/list/download/export.
- Notification outbox and retry behavior with DB rollback, storage failure, and queue redelivery.
- Read-only archive preview plus source/reactivation lineage.

### 18.3 Concurrency, failure, and security tests

- 100 parallel registrations, one company and multiple branches, all numbers unique and correctly scoped.
- Simultaneous registrations at year boundary using company timezone.
- Two workers finalizing same request: exactly one registration.
- DB transaction failure after counter update attempt: no duplicate or half-registered archive.
- Storage upload fails/returns corrupted object: no successful archive registration.
- Worker crash before/after commit; retries return same registration and do not duplicate notifications.
- Source version changes during snapshot staging: archive rejected/restarted safely.
- Unauthorized user changes `company_id`, `branch_id`, `document_id`, version IDs, or storage keys in request: denied.
- Guessable archive numbers/QR URLs do not reveal sensitive metadata or content.
- Archived object cannot be changed by direct API, editor, admin panel, or background job.
- Legal hold blocks disposition even when approval was granted earlier.
- Deleting original source or deactivating branch/user does not remove archive evidence.

### 18.4 User acceptance scenarios

**Scenario A — Standard registration**

```gherkin
Given an approved and finalized document in Company A
And an authorized archivist has selected its exact version
When its archival snapshot is stored and validated
And the archive registration succeeds
Then the document receives a unique permanent archive number
And the original document number is unchanged
And the archive can be found in the permitted archive register
And its source version and archived bytes are read-only
And registration metadata and audit evidence are preserved
```

**Scenario B — Pending request**

```gherkin
Given a document is awaiting archive approval
When the user views the request
Then no final archive number is assigned yet
And the displayed number example is labeled as a preview
```

**Scenario C — Multi-tenant isolation**

```gherkin
Given an archive belongs to Company A
When a user with access only to Company B searches, guesses its URL,
     requests its file, or tries to export it
Then no Company A content or sensitive metadata is returned
```

**Scenario D — Trash independence**

```gherkin
Given a registered archive is linked to an old source document
When the ordinary document trash cleanup runs after 30 days
Then the archive entry and immutable storage object remain retrievable
```

**Scenario E — Retention and legal hold**

```gherkin
Given the record has reached its retention review date
And it has an active legal hold
When disposal is requested or its job is attempted
Then deletion is blocked and the attempted action is audited
```

**Scenario F — Reactivation and rearchiving**

```gherkin
Given a registered immutable archive
When an authorized user creates a new working copy and later archives it
Then the original archive number and snapshot remain unchanged
And a new unique archive number is assigned to the new entry
And both records are linked in the archival history
```

### 18.5 Release gates

Release only if all are true:

- [ ] Existing active-document workflows still work without regression.
- [ ] Archive number uniqueness verified under parallel load and retries.
- [ ] No archive number is shown as final before registration.
- [ ] Archive content and metadata snapshots are immutable and hash-verifiable.
- [ ] ACL/tenant isolation proven for lists, search, details, content, and exports.
- [ ] Archived records survive source document trash/deletion paths.
- [ ] Recovery, retry, and orphan cleanup can be performed without duplication.
- [ ] Approval, numbering, registration, correction, and download events are audited as required.
- [ ] A real archivist/compliance owner approves classification and retention configuration.
- [ ] Operational documentation, monitoring, and backup recovery tests are complete.

---

## 19. Implementation Roadmap and Detailed Task Breakdown

### Phase 0 — Repository discovery and design approval

- [ ] Audit code structure, application stack, auth/tenant model, schema, approvals, ONLYOFFICE, storage, notifications, audit, and trash jobs.
- [ ] Draw actual document lifecycle and data-flow diagrams.
- [ ] Map reusable services and identify technical debt/blockers.
- [ ] Produce database ERD, sequence diagrams, permissions matrix, API contracts, and page wireframes.
- [ ] Confirm archive numbering scope and tenant timezone, restoration policy, approval rules, and initial retention behavior with stakeholders.
- [ ] Create phased change inventory with **exact affected repository files** and rollback strategy.

**Gate:** written plan approved; unresolved legal retention obligations documented.

### Phase 1 — Foundations and archive numbering

- [ ] Add classification, numbering schemes, counter, requests, entries, snapshots, and audit/event schema.
- [ ] Build tenant-scoped archive policies and permission checks.
- [ ] Build versioned template configuration and preview UI/API.
- [ ] Implement transaction-safe sequence allocation with unique indexes and idempotency.
- [ ] Write unit and concurrency tests.

**Gate:** no duplicate/reused archive numbers under fault/concurrency testing.

### Phase 2 — Request-to-registration workflow

- [ ] Add archive request wizard and eligibility validation.
- [ ] Integrate existing approval routes, status updates, delegates, and notifications.
- [ ] Implement source-version locking and ONLYOFFICE finalization safeguards.
- [ ] Build durable immutable snapshot, hash and manifest generation, failure recovery.
- [ ] Implement atomic final registration, audit events, and source-document linkage.
- [ ] Add read-only enforcement and 30-day trash isolation.

**Gate:** approved source documents can be archived end to end without losing versions/signatures.

### Phase 3 — Archive Hub and retrieval

- [ ] Add navigation, archive register, advanced search/filtering, archive detail.
- [ ] Add authorized preview/download/export, original-vs-derived indicators, QR integration.
- [ ] Implement safe reactivation/new working copy with immutable historical archive.
- [ ] Add auditing, empty/error states, responsive design, and localization if applicable.

**Gate:** authorized staff can retrieve archive evidence; unauthorized access cannot bypass ACL.

### Phase 4 — Retention and governance

- [ ] Add policy manager, trigger dates, versioned retention assignments, and review reminders.
- [ ] Implement legal holds, release controls, and conflict checks.
- [ ] Add disposition queue, multi-party approval, verified erasure, and certificates only after legal review.
- [ ] Add reporting and lifecycle monitoring dashboards.

**Gate:** disposal is impossible without eligibility, appropriate approvals, and final hold checks.

### Phase 5 — Enterprise hardening and release

- [ ] Run performance, security, multi-tenant, high-volume, backup/restore, and concurrency tests.
- [ ] Pilot with one company and selected branches.
- [ ] Complete operating runbooks and compliance sign-off.
- [ ] Roll out behind tenant feature flags and monitor incidents/metrics.

**Gate:** production-readiness checklist satisfied and supported in operations.

---

## 20. Definition of Done per Pull Request

Every code-delivery PR must include:

- Business requirement and linked acceptance criterion.
- Scoped implementation using existing architecture and naming conventions.
- Reviewed schema migration(s), indexes, constraints, and backfill/rollback notes.
- Backend authorization and company scope tests.
- Error handling, retries/idempotency, and auditable state changes where relevant.
- API request/response documentation and frontend permission states.
- Automated tests and instructions to reproduce locally.
- Performance considerations and relevant observability signals.
- Security/privacy review for anything touching archive storage, search, signatures, exports, or disposal.
- No unreviewed hardcoded retention durations and no new bypass of existing workflow controls.

---

## 21. Decisions to Confirm with Product Owner

Make these explicit in the repo-specific plan; use the following **recommended defaults** until stakeholders choose otherwise.

| Decision | Recommended default |
|---|---|
| What may be archived? | Final/approved versions; policy-controlled exceptions for signed external/legacy imports |
| Who may register? | Dedicated archivist/records capability; normal staff can request |
| Is archive approval required? | Configurable per company/classification; default required for normal staff |
| When is number assigned? | After snapshot durability/integrity verified, at committed registration |
| Sequence scope | Company-wide, reset every registration year in company timezone |
| Template | `ARC/{COMPANY}/{BRANCH}/{UNIT}/{CLASS}/{YYYY}/{SEQ:06}` |
| Are old numbers editable? | Never; use permanent void record if formally invalidated |
| What if source document is revised? | Existing archival snapshot stays unchanged; new working copy and separate future archive registration |
| Can archived files be downloaded? | Only when original ACL and archival classification allow |
| Can archived files be deleted with trash? | Never |
| Retention duration | Per-company/classification/event policy; none assumed globally |
| Disposal at MVP? | No; review-only until legal policy and multi-party approval designed |
| Public QR? | Minimal verification only; no sensitive details without authorization |
| Physical storage? | Later optional enhancement |
| Which timezone? | Explicit tenant timezone; safe fallback configured and documented |

---

## 22. Final Deliverables Expected from the Coding Agent

The AI should produce and maintain:

1. `ARCHIVING_IMPLEMENTATION_PLAN.md` — repository-specific design, risks, file-by-file tasks, sequence/ERD diagrams.
2. `ARCHIVING_SCHEMA.md` — entity diagram, migrations, indexes, field definitions, uniqueness and state invariants.
3. `ARCHIVING_API_SPEC.md` — endpoints or commands, schemas, permission contracts, errors and idempotency.
4. `ARCHIVING_SECURITY_AND_COMPLIANCE.md` — ACL/tenant matrix, immutability, storage, privacy, QR, disposal and retention policy validation.
5. `ARCHIVING_TEST_PLAN.md` — unit, integration, concurrency, E2E and security scenarios.
6. `ARCHIVING_ROLLOUT.md` — phased release, tenant flags, backfill policy, alerts, recovery and rollback.
7. Architecture diagrams/screens or wireframes as supported by the existing repo/toolchain.
8. Implementation PRs with tests **only after planning approval**.

### Required final AI planning response format

```markdown
# DokuFlow Archiving Feature — Repository-Specific Implementation Plan
## Existing Repository Findings
## Gaps and Compatibility Risks
## Confirmed Business Rules and Open Decisions
## Architecture and State Diagrams
## Database Schema and Migration Plan
## Archive Numbering and Concurrency Design
## Workflow and Snapshot Integration
## Roles, Access, and Tenant Boundaries
## Retention, Holds, and Disposal Governance
## UI/UX Screens and Navigation
## API Contracts and Backend Modules
## Background Jobs and Recovery
## Security / Privacy / Compliance Considerations
## Step-by-Step Implementation Tasks (exact files, dependencies)
## Testing Matrix and Acceptance Criteria
## Deployment, Monitoring, Rollback
## MVP vs Phase 2/3 Priorities
## Decisions Requiring Product Owner Approval
```

---

## 23. Reference Materials for Policy Review

These references guide **records-management principles and Indonesian policy review**; they do not by themselves establish that DokuFlow is certified or that a particular retention period applies to every organization.

- [ISO 15489-1:2016 — Records management, concepts and principles](https://www.iso.org/standard/62542.html) — record capture, metadata, controls, integrity, and governance principles.
- [Indonesia Law No. 43 of 2009 on Archiving (UU Kearsipan)](https://peraturan.bpk.go.id/Details/38788/uu-no-43-tahun-2009) — national archiving framework; check current applicability with records counsel.
- [Government Regulation No. 28 of 2012 (PP 28/2012)](https://peraturan.bpk.go.id/Details/5240/pp-no-28-tahun-2012) — implementation of Indonesian archiving law.
- [Indonesia Law No. 27 of 2022 on Personal Data Protection](https://peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022) — relevant to sensitive personal data, processing, retention, erasure, and access.

**Important:** Verify each applicable sector-specific rule and organization-specific JRA with authorized compliance/records staff before configuring legal retention and destruction. Do not reuse another organization's JRA automatically.

---

## Success Statement

**DokuFlow Archiving is complete when an authorized user can register an approved document version as an immutable, reliably retrievable record with a unique permanent archive number, preserve its original document identity and sign-off evidence, enforce company/branch/unit access, apply auditable retention governance, and prevent accidental alteration, numbering duplication, or deletion by ordinary document trash workflows.**
