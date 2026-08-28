# Feature Specification: Template-Based Document Creation

## 1. Overview

This feature introduces a template management system that allows administrators to upload and manage reusable document templates. Regular users can then select a stored template and use it as the basis for creating a new, customized document. Once a user applies changes to a template, the system generates a new, independent document that incorporates those changes — the original template remains unchanged and reusable.

## 2. Actors

| Actor | Description |
|---|---|
| **Admin** | Has permission to upload, edit, and delete templates. Templates are stored permanently in the database. |
| **User** | Can browse available templates and use them to create new documents. Cannot create or modify templates directly. |

## 3. User Stories

### 3.1 Admin
- As an admin, I want to upload a template document so that users can reuse it.
- As an admin, I want to view, update, or delete existing templates so that the template library stays current.
- As an admin, I want templates to persist permanently in the database so they remain available across sessions.

### 3.2 User
- As a user, I want to browse a list of available templates so that I can pick one relevant to my needs.
- As a user, I want to preview a template before using it.
- As a user, I want to customize a template's content and save it as a new document, without altering the original template.

## 4. Functional Requirements

### 4.1 Template Management (Admin Only)
- FR-1: Only users with the `admin` role can create, update, or delete templates.
- FR-2: Admin can upload a template file (e.g., `.docx`, `.md`, `.html`, or a structured JSON/schema format, depending on system design).
- FR-3: Uploaded templates are parsed and stored permanently in the database (not the filesystem), ensuring durability and centralized access.
- FR-4: Each template record includes metadata: title, description, category/tags, created-by, created-at, updated-at, and version.
- FR-5: Admin can deactivate or archive a template instead of hard-deleting it, preserving referential integrity for documents already created from it.

### 4.2 Template Usage (User)
- FR-6: Users can view a list of active templates, filterable by category/tags.
- FR-7: Users can select a template to preview its structure/content before use.
- FR-8: When a user chooses to use a template, the system loads the template content into an editable document draft.
- FR-9: User edits/changes are applied only to the draft — the original template record is never modified.
- FR-10: Upon save, the system creates a **new document record**, distinct from the template, containing the user's final content.
- FR-11: The new document stores a reference to its source `template_id` for traceability (optional but recommended).

### 4.3 Document Generation
- FR-12: Each generated document is owned by the user who created it.
- FR-13: Documents are independently editable and stored separately from templates going forward.
- FR-14: Multiple users can create multiple distinct documents from the same template without conflict.

## 5. Data Model (Proposed)

### 5.1 `templates` Table
| Field | Type | Description |
|---|---|---|
| `id` | UUID / PK | Unique identifier |
| `title` | String | Template name |
| `description` | Text | Optional description |
| `content` | Text / JSON | Template body/structure |
| `category` | String | Optional classification |
| `status` | Enum (`active`, `archived`) | Availability state |
| `created_by` | FK → `users.id` | Admin who uploaded it |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

### 5.2 `documents` Table
| Field | Type | Description |
|---|---|---|
| `id` | UUID / PK | Unique identifier |
| `title` | String | Document name |
| `content` | Text / JSON | Final user-edited content |
| `template_id` | FK → `templates.id` (nullable) | Source template reference |
| `owner_id` | FK → `users.id` | User who created the document |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

## 6. Workflow

1. **Admin uploads template** → validated → stored permanently in `templates` table.
2. **User browses templates** → selects one → system loads template `content` into an editable draft.
3. **User edits draft** → makes changes (text, fields, sections, etc.).
4. **User saves** → system creates a new row in `documents` table with the edited content and a reference to `template_id`.
5. **Template remains unchanged** and available for future reuse by any user.

## 7. API Endpoints (Suggested)

### Admin
- `POST /api/templates` — Upload a new template
- `GET /api/templates` — List all templates (including archived, admin view)
- `PUT /api/templates/:id` — Update a template
- `DELETE /api/templates/:id` — Archive/delete a template

### User
- `GET /api/templates?status=active` — List available templates
- `GET /api/templates/:id` — Preview a specific template
- `POST /api/documents` — Create a new document from a template (`template_id` + edited `content`)
- `GET /api/documents` — List user's own documents
- `GET /api/documents/:id` — View a specific document

## 8. Permissions Summary

| Action | Admin | User |
|---|---|---|
| Upload template | ✅ | ❌ |
| Edit/delete template | ✅ | ❌ |
| View templates | ✅ | ✅ (active only) |
| Create document from template | ✅ | ✅ |
| Edit own document | ✅ | ✅ |

## 9. Non-Functional Requirements

- **Persistence**: Templates must be stored in the database, not temporary storage, to guarantee availability.
- **Immutability of source**: Applying a template to create a document must never mutate the original template.
- **Auditability**: Track `created_by`/`owner_id` and timestamps on both templates and documents.
- **Scalability**: Support template content as structured data (JSON) if templates include dynamic/fillable fields, rather than static text only.

## 10. Open Questions

- Should templates support **dynamic placeholders** (e.g., `{{customer_name}}`) for structured field-filling, or are they free-form starting content?
- Should document versioning be supported (multiple saves of the same document)?
- What file formats must be supported on template upload?
