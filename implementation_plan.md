# Implementation Plan: Document Consolidation & Editing Application with Version Control

## 1. Executive Summary

This system is a centralized, web-based document management application designed to replace decentralized document creation. It allows divisions to create, edit, and manage documents using a lightweight in-browser editor (JoditEditor). The core value proposition is strict governance: every document is automatically categorized by division, features automatic version control with authorship tracking, requires tiered approval by the Division Head for any changes or rollbacks, and enforces granular, role-based access control (default division read-only, public toggle, and shareable links). 

The implementation will be built using **Laravel**, leveraging its robust ecosystem for authentication, authorization (Policies/Gates), task scheduling (for version retention), and secure data handling. The architecture will prioritize security, data integrity, and clear separation of concerns.

---

## 2. Requirement Traceability

| Req ID | Requirement Description | Module | Planned Implementation | DB Entities | Security / Testing |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **FR-001** | Role-based login (Owner, Editor, Viewer, Head, Admin) | Auth | Laravel Breeze/Sanctum, Custom Roles/Gates | `users`, `roles` | Auth bypass tests |
| **FR-002** | Auto-generate Doc ID `[Divisi]-[YYYYMMDD]-[Seq]` | Doc Mgmt | `DocumentService::generateId()` | `documents` | Unit test ID generation |
| **FR-003** | JoditEditor integration (basic tools) | Frontend | Blade + JoditEditor JS, HTML Purifier backend | N/A | XSS penetration test |
| **FR-004** | Auto versioning, timestamp, authorship | Versioning | `VersionService`, snapshot `author_name` | `document_versions` | Unit test version creation |
| **FR-006** | Edit/Rollback requires Head approval | Workflow | `ApprovalService`, Status enums | `document_versions` | Feature test approval flow |
| **FR-007** | Approved = Read-only for division | Access | `DocumentPolicy::view()`, Division check | `documents`, `users` | IDOR / Privilege tests |
| **FR-008** | Public toggle for cross-division read | Access | `DocumentPolicy`, `is_public` flag | `documents` | Access control tests |
| **FR-009** | Shareable links with roles (Viewer/Editor) | Access | `AccessLink` model, Token validation | `document_access_links` | Link expiration/IDOR tests |
| **FR-010** | Admin systemic roles & division structure | Admin | CRUD for Divisions/Users | `divisions`, `users` | Admin privilege tests |
| **FR-011** | Admin version retention & cronjob | System | Laravel Scheduler, `DeleteOldVersions` cmd | `document_versions` | Cron execution test |
| **FR-012** | Activity logs (who, what, when) | Audit | `AuditLog` model, Model Observers | `audit_logs` | Audit trail verification |
| **FR-013** | Notifications on edits | Notif | Laravel Notifications (DB/Mail) | `notifications` | Notification dispatch test |

---

## 3. System Architecture

The application will follow a **Service-Oriented MVC Architecture** within Laravel to keep controllers thin and business logic testable.

*   **Application Structure**: Standard Laravel directory structure. Business logic will be extracted into a `Services` directory.
*   **Separation of Concerns**: 
    *   *Controllers*: Handle HTTP requests, validation, and response formatting.
    *   *Services*: Handle complex business rules (e.g., version creation, approval workflows, ID generation).
    *   *Policies*: Handle all authorization logic.
    *   *Models*: Handle database relationships, scopes, and basic attribute casting.
*   **Editor Integration**: JoditEditor will be integrated on the frontend. The backend will **never** trust the incoming HTML; it will be sanitized using `mews/purifier` before storage to prevent XSS.
*   **Queue Architecture**: Notifications and email dispatches will be pushed to a queue (`database` or `redis` driver) to ensure the <3-second save performance requirement (NFR-003) is met.
*   **Task Scheduling**: Laravel Scheduler will run a daily command to purge expired document versions based on Admin configuration.

---

## 4. Module and Feature Breakdown

### 4.1. User & Division Management (Admin)
*   **Purpose**: Manage organizational structure and systemic roles.
*   **Features**: CRUD Divisions, CRUD Users, assign users to divisions, set systemic roles (Admin, Head, User).
*   **Security**: Strictly restricted to System Admin.

### 4.2. Document Management & Editor
*   **Purpose**: Create and edit documents.
*   **Features**: Create document (auto-ID), JoditEditor integration, save draft/pending.
*   **Business Rules**: Creator becomes Owner. ID format strictly enforced.
*   **Security**: HTML sanitization, IDOR prevention via Policies.

### 4.3. Version Control & Approval Workflow
*   **Purpose**: Track changes and enforce governance.
*   **Features**: Auto-versioning on save, view version history, request rollback, Head approval/rejection UI.
*   **Business Rules**: Rollbacks create *new* versions. Edits/rollbacks remain `pending` until approved. Only one `active` version per document.
*   **Security**: Audit logging for all state changes.

### 4.4. Access Control & Sharing
*   **Purpose**: Manage who can view/edit documents.
*   **Features**: Public toggle, generate shareable links (Viewer/Editor) with expiration, revoke links.
*   **Business Rules**: Default read-only for division upon approval. Links grant specific access.
*   **Security**: Token-based link validation, strict expiration checks.

### 4.5. System Configuration & Retention
*   **Purpose**: Manage system-wide settings.
*   **Features**: Set version retention period (days).
*   **Business Rules**: Cronjob deletes historical versions older than X days. **Audit logs are NEVER deleted.**

---

## 5. User Roles and Authorization

Authorization is enforced strictly on the server side using **Laravel Policies** and **Gates**. Frontend UI hiding is only for UX, not security.

| Role | Module | Action | Permission / Policy Logic |
| :--- | :--- | :--- | :--- |
| **System Admin** | All | Manage Divisions/Users, Config Retention | `isAdmin()` gate. |
| **Division Head** | Docs/Workflow | Approve/Reject edits & rollbacks in their division | `DocumentPolicy::approve()`: checks if user is Head of doc's division. |
| **Document Owner** | Docs/Access | Edit, Share, Toggle Public, Request Rollback | `DocumentPolicy::update()`: checks if user is Owner OR has Editor link. |
| **Editor (via Link)**| Docs | Edit document (creates pending version) | Validated via Link Token middleware. |
| **Viewer** | Docs | Read document | `DocumentPolicy::view()`: checks division match, `is_public`, or valid Viewer link. |

**IDOR Prevention**: Every route interacting with a `Document` or `DocumentVersion` will use Route Model Binding combined with Policy authorization (e.g., `$this->authorize('view', $document)`).

---

## 6. Database Design

### 6.1. Core Entities

**`divisions`**
*   `id` (PK)
*   `code` (string, unique, e.g., 'HRD')
*   `name` (string)

**`users`**
*   `id` (PK)
*   `name` (string)
*   `email` (string, unique)
*   `password` (string)
*   `division_id` (FK -> divisions)
*   `system_role` (enum: admin, head, user)
*   `is_active` (boolean, default true)

**`documents`**
*   `id` (PK)
*   `document_number` (string, unique, e.g., 'HRD-20260729-001')
*   `title` (string)
*   `division_id` (FK -> divisions)
*   `owner_id` (FK -> users)
*   `is_public` (boolean, default false)
*   `current_version_id` (FK -> document_versions, nullable)
*   `created_at`, `updated_at`

**`document_versions`**
*   `id` (PK)
*   `document_id` (FK -> documents)
*   `version_number` (integer)
*   `content` (longText)
*   `author_id` (FK -> users, nullable)
*   `author_name` (string) *Snapshot of name at creation*
*   `status` (enum: pending, active, rejected)
*   `reviewer_id` (FK -> users, nullable)
*   `review_notes` (text, nullable)
*   `reviewed_at` (timestamp, nullable)
*   `created_at`, `updated_at`

**`document_access_links`**
*   `id` (PK)
*   `document_id` (FK -> documents)
*   `token` (string, unique)
*   `role` (enum: viewer, editor)
*   `expires_at` (timestamp, nullable - null means forever)
*   `created_by` (FK -> users)
*   `created_at`, `updated_at`

**`audit_logs`** (Immutable)
*   `id` (PK)
*   `user_id` (FK -> users, nullable)
*   `action` (string, e.g., 'document.created', 'version.approved')
*   `target_type` (string)
*   `target_id` (unsigned bigint)
*   `metadata` (json)
*   `created_at`

---

## 7. Laravel Models and Relationships

*   **`Document`**: 
    *   `belongsTo(Division)`, `belongsTo(User, 'owner_id')`, `hasMany(DocumentVersion)`, `belongsTo(DocumentVersion, 'current_version_id')`, `hasMany(DocumentAccessLink)`.
    *   *Scope*: `scopeActive()`, `scopePending()`.
*   **`DocumentVersion`**:
    *   `belongsTo(Document)`, `belongsTo(User, 'author_id')`, `belongsTo(User, 'reviewer_id')`.
    *   *Cast*: `status` to Enum, `reviewed_at` to datetime.
*   **`DocumentAccessLink`**:
    *   `belongsTo(Document)`, `belongsTo(User, 'created_by')`.
    *   *Accessor*: `is_expired` (checks `expires_at`).

---

## 8. Backend Implementation

### 8.1. Services
*   **`DocumentService`**: Handles document creation, ID generation (locking mechanism to prevent race conditions on sequence numbers), and ownership transfer logic.
*   **`VersionService`**: Handles saving new versions (sets status to `pending`), rolling back (copies content to new pending version), and activating versions.
*   **`AccessLinkService`**: Generates secure cryptographic tokens for sharing, handles expiration logic.

### 8.2. Commands & Jobs
*   **`DeleteExpiredVersionsCommand`**: Scheduled daily. Queries `document_versions` where `status != 'active'` and `created_at < retention_date`. Deletes them. *Crucially, it does NOT touch `audit_logs`.*
*   **`SendApprovalNotificationJob`**: Queued job to notify Division Heads of pending approvals.

### 8.3. Policies
*   **`DocumentPolicy`**: 
    *   `view`: User is in same division OR doc is public OR user has valid link.
    *   `update`: User is Owner OR user has valid Editor link.
    *   `approve`: User is Head of the document's division.

---

## 9. API Design

The system will use standard RESTful web routes (Blade + AJAX/Fetch) rather than a pure JSON API, but the backend logic will be structured API-ready.

*   `POST /documents` - Create document.
*   `PUT /documents/{document}/save` - Save edit (creates pending version).
*   `POST /documents/{document}/versions/{version}/rollback` - Request rollback.
*   `POST /documents/{document}/versions/{version}/approve` - Approve version (Head only).
*   `POST /documents/{document}/links` - Generate share link.
*   `GET /share/{token}` - Access document via link.

---

## 10. Frontend Integration

*   **Editor**: JoditEditor initialized via CDN or NPM. Configured to only show basic tools (bold, italic, heading, list, image, link).
*   **UI States**: 
    *   *Pending Banner*: Clear visual indicator when a document has a pending version.
    *   *Read-only Mode*: Editor is disabled if user only has Viewer access.
*   **Validation**: Client-side validation for form inputs, but **never** relied upon for security.

---

## 11. Security Implementation Plan

### 11.1. Input Security (XSS Prevention)
*   **Risk**: JoditEditor outputs HTML. Storing raw HTML allows Stored XSS.
*   **Mitigation**: Use `mews/purifier` to sanitize all `content` fields in `DocumentVersion` before saving to the database. Strip all tags except allowed formatting tags.

### 11.2. Authorization & IDOR
*   **Risk**: Users accessing documents outside their division or altering other divisions' docs.
*   **Mitigation**: Strict use of Laravel Policies. Route model binding will automatically trigger policy checks. Division ID is never taken from the client request; it is derived from the authenticated user's profile.

### 11.3. Link Sharing Security
*   **Risk**: Link guessing, expired links being used.
*   **Mitigation**: Tokens generated using `Str::random(64)`. Middleware checks `expires_at` on every request. Links can be manually revoked (deleted).

### 11.4. Audit & Data Integrity
*   **Risk**: Loss of audit trail when versions are deleted.
*   **Mitigation**: `audit_logs` table is completely decoupled from `document_versions`. The cronjob only deletes version content/metadata, never audit records.

---

## 12. Validation and Business Rules

Implemented via **Laravel Form Requests**.

*   **`StoreDocumentRequest`**: `title` (required, string, max:255).
*   **`SaveVersionRequest`**: `content` (required, string). *Sanitization happens in Service, not just validation.*
*   **`GenerateLinkRequest`**: `role` (required, in:viewer,editor), `expires_at` (nullable, date, after:today).
*   **Business Rule (ID Generation)**: Handled in `DocumentService` using `DB::transaction` and `lockForUpdate()` on a sequence table or using atomic increments to prevent duplicate IDs under concurrent loads.

---

## 13. Business Process Implementation

### 13.1. Edit & Approval Workflow
1.  **Trigger**: User clicks "Save" in editor.
2.  **Actor**: Owner or Editor.
3.  **Process**: 
    *   `VersionService` creates new `DocumentVersion` (status: `pending`).
    *   `Document.current_version_id` remains unchanged (old version stays active).
    *   Audit log created.
    *   Notification dispatched to Division Head.
4.  **Approval**: Head clicks "Approve".
    *   Old active version status remains (or implicitly becomes historical).
    *   New version status -> `active`.
    *   `Document.current_version_id` updated to new version.
    *   Audit log created.

### 13.2. Rollback Workflow
1.  **Trigger**: Owner selects old version and clicks "Rollback".
2.  **Process**: 
    *   System reads content of selected version.
    *   Creates *new* `DocumentVersion` with that content (status: `pending`).
    *   Follows exact same Approval Workflow.

---

## 14. Error Handling

*   **Validation Errors**: Return 422 with structured JSON/Blade error messages.
*   **Authorization Errors**: Throw `AuthorizationException`, return 403.
*   **Not Found**: Throw `ModelNotFoundException`, return 404.
*   **Business Rule Violations** (e.g., trying to edit a rejected version): Throw custom `BusinessLogicException`, return 422 with specific message.
*   **Global Handler**: `app/Exceptions/Handler.php` will log all 500 errors to Laravel Log/Telemetry and return a generic "Server Error" message to the user to prevent stack trace leakage.

---

## 15. Testing Strategy

*   **Unit Tests**: `DocumentService` (ID generation logic), `VersionService` (status transitions).
*   **Feature Tests**: 
    *   User can only see documents in their division.
    *   Head can approve/reject versions.
    *   Share link expires correctly.
    *   Cronjob deletes old versions but keeps active ones and audit logs.
*   **Security Tests**: 
    *   Attempt to save HTML with `<script>` tags (must be sanitized).
    *   Attempt to access document via IDOR (User A accessing User B's private doc).
    *   Attempt to approve document as a non-Head user.

---

## 16. Implementation Phases

1.  **Phase 1: Foundation**: Auth, Divisions, Users, Roles, basic layout.
2.  **Phase 2: Core Document & Editor**: Doc creation, ID generation, JoditEditor, HTML sanitization.
3.  **Phase 3: Versioning & Workflow**: Version creation, approval flow, rollback.
4.  **Phase 4: Access Control**: Division defaults, Public toggle, Shareable links.
5.  **Phase 5: Admin & System**: Retention config, Cronjob, Audit logs, Notifications.

---

## 17. Task Breakdown

| Task ID | Task Name | Description | Dependencies |
| :--- | :--- | :--- | :--- |
| T-01 | DB Migrations & Models | Create all tables, relationships, and enums. | None |
| T-02 | Auth & User Management | Login, Admin CRUD for users/divisions. | T-01 |
| T-03 | Document Creation & ID Gen | Implement `DocumentService` and atomic ID generation. | T-01, T-02 |
| T-04 | JoditEditor & Sanitization | Integrate frontend editor, implement backend HTML purifier. | T-03 |
| T-05 | Version Control Logic | Implement save as pending, activate on approval. | T-04 |
| T-06 | Approval Workflow UI/Backend | Head dashboard, approve/reject actions, notifications. | T-05 |
| T-07 | Access Control Policies | Implement `DocumentPolicy`, division checks, public toggle. | T-05 |
| T-08 | Shareable Links | Link generation, token middleware, expiration logic. | T-07 |
| T-09 | Audit Logging | Implement observer/service to log all critical actions. | T-05 |
| T-10 | Retention Cronjob | Implement command and schedule to delete old versions. | T-05, T-09 |
| T-11 | Testing & Security Review | Write feature tests, perform XSS/IDOR pen-testing. | All |

---

## 18. Migration and Deployment Strategy

*   **Migrations**: Run in standard order. Ensure foreign keys have `onDelete('cascade')` or `restrict` as appropriate (e.g., restrict deleting a division if it has documents).
*   **Environment**: 
    *   `QUEUE_CONNECTION=redis` (or database) for notifications.
    *   `FILESYSTEM_DISK=local` (or s3 if configured).
*   **Scheduler**: Add `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1` to server crontab.
*   **Storage**: Ensure `storage/app/public` is linked for any uploaded images from JoditEditor.

---

## 19. Performance and Scalability

*   **Database Indexing**: Add composite indexes on `document_versions` (`document_id`, `status`) and `documents` (`division_id`, `is_public`).
*   **N+1 Prevention**: Use eager loading (`with('currentVersion', 'owner', 'division')`) in document listing controllers.
*   **Caching**: Cache the `System Settings` (like retention days) to avoid DB hits on every request.
*   **Large Content**: `content` column is `longText`. Ensure database is optimized for large text retrieval. Do not load all versions in the history UI; paginate or load via AJAX.

---

## 20. Risks, Open Questions, and Assumptions

### 20.1. Critical Open Questions (Requires Client Clarification)
1.  **Document ID Format Contradiction**: 
    *   *Assumption 5* states format is `[Kode Divisi]-[Tgl YYYYMMDD]-[Nomor Urut]` (e.g., HRD-20260729-001). 
    *   *FR-002 & Acceptance Criteria* state format is `[Divisi]-[NomorUrut]` (e.g., HRD-001). 
    *   *Impact*: Massive. I have proceeded with **Assumption 5** as it is standard practice to include dates to prevent massive sequence numbers and collisions. Please confirm.
2.  **Audit Log Deletion Risk**: 
    *   In Q3, the answer to "Are approval logs kept after version deletion?" was "Ya tetap dihapus" (Yes, still deleted). 
    *   *Impact*: **Critical Security/Compliance Risk**. Deleting audit logs violates basic audit principles. I have designed the system to **permanently retain audit logs** even when document versions are deleted. If the client strictly requires deleting audit logs, the system will lose its ability to trace historical approvals. Please clarify.
3.  **Ownership Transfer Trigger**: 
    *   "Ownership transfers if Owner resigns and someone else edits." 
    *   *Impact*: How does the system know the Owner "resigned"? I have assumed this means the Owner's `is_active` flag is set to `false`. Furthermore, if a temporary "Editor via link" edits the doc, do they become the Owner? I have assumed ownership only transfers to a **registered user in the same division** who edits the doc after the original Owner is deactivated. Please confirm.

### 20.2. Technical Assumptions
1.  **HTML Sanitization**: JoditEditor allows image uploads. I assume images will be stored in Laravel's public storage and referenced via URLs. All HTML will be strictly sanitized.
2.  **Pending Document Visibility**: Documents with only `pending` versions (never approved) are only visible to the Owner and the Division Head. They do not appear in the division's general read-only list until approved.
3.  **Active Version Constraint**: A document can only have exactly ONE `active` version at any time.

### 20.3. Risks
1.  **Race Conditions in ID Generation**: Concurrent document creation in the same division on the same day could result in duplicate IDs. *Mitigation*: Implemented atomic DB transactions with row locking in `DocumentService`.
2.  **XSS via Editor**: JoditEditor is a rich text editor. *Mitigation*: Strict backend sanitization using HTMLPurifier.

---

## 21. Final Implementation Order

1.  **Database & Models**: Setup schema, migrations, and Eloquent relationships.
2.  **Auth & Admin**: User management, division structure, role gates.
3.  **Core Document Engine**: Document creation, atomic ID generation, JoditEditor integration, HTML sanitization.
4.  **Versioning & Workflow**: Version creation, pending/active states, approval UI, rollback logic.
5.  **Access Control**: Policies, division defaults, public toggle, shareable links with expiration.
6.  **System Maintenance**: Audit logging, retention cronjob, notifications.
7.  **Hardening & Testing**: Security review (XSS/IDOR), feature testing, performance optimization.