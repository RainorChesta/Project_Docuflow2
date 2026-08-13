# Refactor Document Sharing System (Google Docs Model)

Implements the sharing refactor described in [implementasiplan.md](file:///c:/Users/ASUS/Downloads/implementasiplan.md) across 7 sequential tasks. The existing `DocumentAccessLink`-based sharing remains functional throughout — each task is additive until TASK 6 (frontend) wires everything together.

## Proposed Changes

### TASK 1 — Database Migrations

Create two new tables and add three columns to `documents`.

#### [NEW] `2026_08_12_100001_create_document_shares_table.php`
- Columns: `id`, `document_id` (FK → documents), `user_id` (FK → users), `role` (enum: owner/editor/viewer), `invited_by` (FK → users), `created_at`
- Unique index on `(document_id, user_id)` to prevent duplicate personal shares

#### [NEW] `2026_08_12_100002_create_document_division_shares_table.php`
- Columns: `id`, `document_id` (FK → documents), `division_id` (FK → divisions), `role` (enum: editor/viewer), `invited_by` (FK → users), `created_at`
- Unique index on `(document_id, division_id)`

#### [NEW] `2026_08_12_100003_add_general_access_columns_to_documents_table.php`
- Adds `general_access` (string, default `'restricted'`), `link_role` (string, nullable), `share_token` (string, unique, nullable)
- Backfill: generates a `share_token` for every existing document

---

### TASK 2 — Models (`DocumentShare`, `DocumentDivisionShare`) & Relationships

#### [NEW] `app/Models/DocumentShare.php`
- `fillable`: `document_id`, `user_id`, `role`, `invited_by`
- Relationships: `document()`, `user()`, `invitedBy()`

#### [NEW] `app/Models/DocumentDivisionShare.php`
- `fillable`: `document_id`, `division_id`, `role`, `invited_by`
- Relationships: `document()`, `division()`, `invitedBy()`

#### [MODIFY] [Document.php](file:///c:/Users/ASUS/Project_DokuFlow/app/Models/Document.php)
- Add `general_access`, `link_role`, `share_token` to `$fillable`
- Add `shares()` → HasMany to `DocumentShare`
- Add `divisionShares()` → HasMany to `DocumentDivisionShare`
- Extend `scopeVisibleTo()` to also include documents where user has a personal share or a division share

#### [MODIFY] [User.php](file:///c:/Users/ASUS/Project_DokuFlow/app/Models/User.php)
- Add `documentShares()` → HasMany to `DocumentShare`

#### [MODIFY] [Division.php](file:///c:/Users/ASUS/Project_DokuFlow/app/Models/Division.php)
- Add `documentShares()` → HasMany to `DocumentDivisionShare`

---

### TASK 3 — Effective Role Resolution Service

#### [NEW] `app/Services/DocumentShareService.php`
Core service containing:
- `resolveEffectiveRole(Document, User): ?string` — the weight-based resolution logic from §5.1
  1. Get personal role from `document_shares`
  2. Get max division role from `document_division_shares` for all user's divisions
  3. Consider `link_role` if `general_access === 'anyone_with_link'`
  4. Return the highest-weighted role, or `null` if no access
- `ROLE_WEIGHTS` constant: `['owner' => 3, 'editor' => 2, 'viewer' => 1]`
- `addUserShare(Document, User, string $role, User $invitedBy): DocumentShare`
- `updateUserShareRole(DocumentShare, string $newRole): void`
- `removeUserShare(DocumentShare): void`
- `addDivisionShare(Document, Division, string $role, User $invitedBy): DocumentDivisionShare`
- `updateDivisionShareRole(DocumentDivisionShare, string $newRole): void`
- `removeDivisionShare(DocumentDivisionShare): void`
- `updateGeneralAccess(Document, string $access, ?string $linkRole): void`
- `regenerateShareToken(Document): string`

---

### TASK 4 — Authorization Middleware / Policy Update

#### [MODIFY] [DocumentPolicy.php](file:///c:/Users/ASUS/Project_DokuFlow/app/Policies/DocumentPolicy.php)
- `view()`: add check via `DocumentShareService::resolveEffectiveRole()` — any non-null role grants view
- `update()`: effective role of `editor` or `owner` allows update (in addition to existing owner/admin check)
- `delete()`: remains owner/admin only (unchanged by spec — owner role is singular)
- `manageAccess()`: new gate — only owner or admin can add/remove shares

---

### TASK 5 — Backend Endpoints (Controller)

#### [NEW] `app/Http/Controllers/DocumentShareController.php`
Endpoints for managing shares:
- `POST /documents/{document}/shares` — add user or division share (dispatches to correct table based on `type` param: `user` or `division`)
- `PATCH /documents/{document}/shares/{share}` — update role of a user share
- `DELETE /documents/{document}/shares/{share}` — remove user share
- `PATCH /documents/{document}/division-shares/{divisionShare}` — update role of a division share
- `DELETE /documents/{document}/division-shares/{divisionShare}` — remove division share
- `PATCH /documents/{document}/general-access` — update `general_access` and `link_role`
- `POST /documents/{document}/regenerate-token` — regenerate `share_token`
- `GET /documents/{document}/share-data` — JSON endpoint returning all current shares, division shares, general access settings (for the frontend modal)
- `GET /documents/{document}/search-sharees` — search users/divisions for the invite autocomplete

#### [MODIFY] [web.php](file:///c:/Users/ASUS/Project_DokuFlow/routes/web.php)
- Register all new routes under the auth middleware group

---

### TASK 6 — Frontend: "Bagikan" Modal (Google Docs Style)

#### [MODIFY] [show.blade.php](file:///c:/Users/ASUS/Project_DokuFlow/resources/views/documents/show.blade.php)
- Replace the "Share Link" button with a "Bagikan" button that opens the new sharing modal
- Keep the old `link-form` dialog intact as a fallback until the new modal works
- New modal includes:
  - Search input to invite users or divisions (autocomplete via AJAX)
  - Role selector (Editor/Viewer) for each invitee
  - List of "People with access" showing personal shares and division shares with role badges + remove buttons
  - "General access" section: radio toggle between Restricted / Anyone with link, with link_role selector
  - Copy link button (uses `share_token`)

---

### TASK 7 — Tests for Effective Role Resolution

#### [NEW] `tests/Feature/EffectiveRoleTest.php`
Test cases matching §5.1 and §7 of the plan:
- Personal viewer only → viewer
- Personal editor only → editor
- Division viewer only → viewer
- Division editor + personal viewer → editor (higher wins)
- Division viewer + personal editor → editor (higher wins)
- Multiple divisions with different roles → highest wins
- No share at all, restricted access → null
- No share, `anyone_with_link` → `link_role`
- Personal share higher than link_role → personal wins
- Owner is always owner (weight 3)
- Effective role changes after share is removed

> [!IMPORTANT]
> The plan specifies access resolution must be tested before completion. This is the critical deliverable the user explicitly asked for.

---

## Verification Plan

### Automated Tests
```bash
php artisan test --filter=EffectiveRoleTest
```
Run full suite to confirm nothing is broken:
```bash
php artisan test
```

### Manual Verification
- Run migrations successfully (`php artisan migrate`)
- The existing share link feature continues to work (old `DocumentAccessLink` routes untouched)
- New sharing modal visible on document show page
- Invite user/division and verify access works

## Open Questions

> [!NOTE]
> **Division `users()` relationship**: The `Division` model currently has `users(): HasMany` (one-to-many via `division_id` FK on users), but users also belong to multiple divisions via the `division_user` pivot. The `DocumentDivisionShare` will check membership using `User::allDivisionIds()` which already covers both — no changes needed here.

> [!NOTE]
> **Existing `DocumentAccessLink` system**: The plan says to keep the current link-sharing working until the new one is ready. The old routes/controller won't be removed. The new `share_token` on `documents` table is separate from the per-link tokens in `document_access_links`.
