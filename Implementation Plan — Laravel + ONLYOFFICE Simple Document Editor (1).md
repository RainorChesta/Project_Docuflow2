# Implementation Plan: Laravel + ONLYOFFICE Simple Document Editor

## 1. Goal

Build a simple Laravel web application that provides a Microsoft Word-like document editing experience using **ONLYOFFICE Docs Community Edition**.

The application should allow authenticated users to:

1. Register and log in.
2. Create a document.
3. Open the document in an ONLYOFFICE editor.
4. Edit the document in the browser.
5. Save changes back to the Laravel application.
6. View a list of their documents.
7. Rename documents.
8. Delete documents.
9. Download the document as DOCX.

Do NOT build a custom rich-text editor. ONLYOFFICE must be responsible for document editing.

Keep the implementation simple, clean, and production-oriented.

---

# 2. Technology Stack

Use:

- Laravel 12
- PHP 8.3+
- MySQL 8+
- Laravel Blade
- Laravel Breeze for authentication
- Tailwind CSS
- JavaScript
- ONLYOFFICE Docs Community Edition
- Docker for ONLYOFFICE
- Laravel Storage
- REST API for ONLYOFFICE callbacks

Do not introduce React, Vue, Inertia, Livewire, or other frontend frameworks unless absolutely necessary.

The application should remain a simple Laravel + Blade application.

---

# 3. Architecture

Use this architecture:

```text
Browser
   │
   ▼
Laravel Application
   │
   ├── Authentication
   ├── Document Management
   ├── Authorization
   ├── Database
   ├── File Storage
   └── ONLYOFFICE Integration
           │
           ▼
     ONLYOFFICE Docs
           │
           ▼
      Laravel Callback
           │
           ▼
      Save DOCX file
```

Laravel is responsible for:

- users
- documents
- permissions
- metadata
- file storage
- document creation
- document listing
- document deletion
- document download
- ONLYOFFICE configuration

ONLYOFFICE is responsible for:

- document editing
- Word-like UI
- DOCX rendering
- formatting
- pagination
- tables
- images
- document editing
- document saving callbacks

---

# 4. Initial Project Setup

Create a new Laravel application.

Use:

```bash
composer create-project laravel/laravel word-clone
cd word-clone
```

Configure MySQL in `.env`.

Install authentication using Laravel Breeze.

Use Blade authentication.

Run:

```bash
php artisan migrate
```

The application should have:

- login
- registration
- logout
- authenticated dashboard

---

# 5. ONLYOFFICE Docker Setup

Add a `docker-compose.yml` file for ONLYOFFICE.

Use the official ONLYOFFICE Docs Community Edition Docker image.

The expected architecture is:

```text
Laravel
http://localhost:8000

ONLYOFFICE
http://localhost:8080
```

The ONLYOFFICE container must be accessible from the Laravel application.

Document this setup clearly in the README.

The application should not depend on a paid ONLYOFFICE license.

---

# 6. Environment Variables

Add these variables to `.env.example`:

```env
ONLYOFFICE_URL=http://localhost:8080
ONLYOFFICE_JWT_ENABLED=false
ONLYOFFICE_JWT_SECRET=
DOCUMENT_STORAGE_DISK=local
```

Create a configuration file:

```text
config/onlyoffice.php
```

Example configuration:

```php
return [
    'url' => env('ONLYOFFICE_URL', 'http://localhost:8080'),

    'jwt_enabled' => env('ONLYOFFICE_JWT_ENABLED', false),

    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET'),

    'storage_disk' => env('DOCUMENT_STORAGE_DISK', 'local'),
];
```

Do not hard-code ONLYOFFICE URLs in controllers or Blade templates.

---

# 7. Database Design

Create a `documents` table.

Fields:

```text
documents
---------
id
user_id
name
file_name
file_path
mime_type
file_size
created_at
updated_at
```

Use:

```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->string('name');
$table->string('file_name');
$table->string('file_path');
$table->string('mime_type')->nullable();
$table->unsignedBigInteger('file_size')->nullable();
$table->timestamps();
```

Add an index for:

```text
user_id
```

---

# 8. Document Model

Create:

```text
app/Models/Document.php
```

Relationships:

```php
public function user()
{
    return $this->belongsTo(User::class);
}
```

The User model should have:

```php
public function documents()
{
    return $this->hasMany(Document::class);
}
```

---

# 9. Authorization

Users must only be able to access their own documents.

Create:

```text
app/Policies/DocumentPolicy.php
```

Implement:

- view
- update
- delete
- download

A user must never be able to access another user's document by changing the document ID in the URL.

Use Laravel authorization:

```php
$this->authorize('view', $document);
```

Do not rely only on frontend restrictions.

---

# 10. Document Routes

Create routes:

```text
GET     /documents
GET     /documents/create
POST    /documents
GET     /documents/{document}
GET     /documents/{document}/edit
PATCH   /documents/{document}
DELETE  /documents/{document}
GET     /documents/{document}/download

POST    /onlyoffice/documents/{document}/callback
```

All document management routes must require authentication.

The ONLYOFFICE callback route must be accessible by ONLYOFFICE.

Do not require normal browser authentication on the callback endpoint.

---

# 11. Document Controller

Create:

```text
app/Http/Controllers/DocumentController.php
```

Methods:

```text
index()
create()
store()
show()
edit()
update()
destroy()
download()
```

## index()

Display the authenticated user's documents.

Order by:

```text
updated_at DESC
```

Display:

- document name
- updated date
- file size
- actions

Actions:

- Open
- Rename
- Download
- Delete

---

# 12. Creating a Document

When the user creates a document:

1. Validate the document name.
2. Automatically create a blank DOCX file.
3. Store it under Laravel storage.
4. Create a database record.
5. Redirect the user to the editor.

Example storage structure:

```text
storage/app/documents/{user_id}/{document_id}/document.docx
```

Do not store user documents inside:

```text
public/
```

---

# 13. Creating the Initial DOCX

For the MVP, create a minimal valid DOCX document.

You may use a PHP DOCX library such as:

```text
phpoffice/phpword
```

Install:

```bash
composer require phpoffice/phpword
```

Create a service:

```text
app/Services/DocumentService.php
```

Example responsibilities:

```text
createBlankDocument()
getDocumentPath()
saveDocument()
deleteDocument()
```

When creating a new document:

```text
New Document
     ↓
PHPWord
     ↓
blank.docx
     ↓
Laravel Storage
```

---

# 14. ONLYOFFICE Editor Page

Create:

```text
resources/views/documents/edit.blade.php
```

The page should contain:

- application header
- document name
- back button
- ONLYOFFICE editor container

Load the ONLYOFFICE API script from:

```text
{ONLYOFFICE_URL}/web-apps/apps/api/documents/api.js
```

Do not hard-code the URL.

Use the configured `ONLYOFFICE_URL`.

---

# 15. ONLYOFFICE Configuration

When opening a document, generate the ONLYOFFICE configuration from Laravel.

The configuration should include:

```javascript
{
    documentType: "word",

    document: {
        title: "...",
        url: "...",
        fileType: "docx",
        key: "...",
        permissions: {
            edit: true,
            download: true,
            print: true
        }
    },

    editorConfig: {
        mode: "edit",

        callbackUrl: "..."
    }
}
```

Use the user's document ID when generating the document key.

The key must be deterministic for the current document version but should change when a new document version is created.

Do not simply use:

```text
document.id
```

as the only long-term versioning mechanism.

For the MVP, a hash based on document ID and `updated_at` is acceptable.

Example concept:

```php
$key = hash(
    'sha256',
    $document->id . '|' . $document->updated_at?->timestamp
);
```

Ensure the resulting key conforms to ONLYOFFICE requirements.

---

# 16. Document URL

ONLYOFFICE must be able to download the document from Laravel.

Create a route such as:

```text
GET /onlyoffice/documents/{document}/file
```

This endpoint should:

1. Authenticate/validate the request appropriately.
2. Locate the document.
3. Return the DOCX file.
4. Set the correct MIME type.

Important:

ONLYOFFICE runs separately from the browser.

Therefore, `localhost` URLs can become problematic when Laravel and ONLYOFFICE are running in different containers or machines.

The implementation must document this clearly.

For Docker development, configure the services so that ONLYOFFICE can reach Laravel through a network-accessible hostname.

Do not assume that:

```text
http://localhost:8000
```

inside the ONLYOFFICE container refers to the Laravel host.

---

# 17. ONLYOFFICE Callback

Create:

```text
app/Http/Controllers/OnlyOfficeController.php
```

Implement:

```text
callback()
```

ONLYOFFICE will send a callback to Laravel when the document needs to be saved.

Handle ONLYOFFICE callback statuses correctly.

At minimum handle:

```text
status = 2
status = 6
```

Where appropriate, download the updated document from the URL supplied by ONLYOFFICE and replace the stored DOCX file.

Do not blindly treat every callback as a save operation.

Log unknown callback statuses.

---

# 18. Saving the Document

When ONLYOFFICE reports that a document should be saved:

```text
ONLYOFFICE
     │
     │ callback
     ▼
Laravel
     │
     │ download updated DOCX
     ▼
Storage
     │
     ▼
document.docx
```

Use Laravel's HTTP client:

```php
Http::get($url)
```

Do not use insecure shell commands such as:

```bash
wget ...
curl ...
```

from PHP.

Validate the downloaded response before replacing the existing file.

Use a temporary file and atomic replacement where practical:

```text
download temporary file
        ↓
validate
        ↓
replace original
```

Update:

```text
documents.updated_at
documents.file_size
```

---

# 19. File Download

Create:

```text
GET /documents/{document}/download
```

The user should receive the original DOCX file.

Use Laravel:

```php
return Storage::disk(...)->download(...);
```

Set the filename based on the document name:

```text
My Document.docx
```

Do not allow arbitrary filesystem paths from user input.

---

# 20. Rename Document

Allow the user to rename a document without modifying its actual stored filename.

Example:

```text
Display name:
Project Proposal

Stored file:
documents/1/15/document.docx
```

This prevents filesystem problems caused by arbitrary document names.

---

# 21. Delete Document

When deleting:

1. Authorize the document.
2. Delete the physical file.
3. Delete the database record.

Use:

```php
Storage::disk(...)->delete($document->file_path);
```

Handle missing files gracefully.

---

# 22. Blade UI

Create a clean minimal UI.

Dashboard:

```text
┌─────────────────────────────────────────────┐
│ Word Clone                         Austin ▼ │
├─────────────────────────────────────────────┤
│                                             │
│ My Documents                  + New Document│
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ 📄 Project Proposal                     │ │
│ │ Updated 5 minutes ago                   │ │
│ │                         Open  •••       │ │
│ └─────────────────────────────────────────┘ │
│                                             │
└─────────────────────────────────────────────┘
```

Do not spend excessive time on visual design.

Prioritize functionality.

---

# 23. Editor UI

The editor page should look approximately like:

```text
┌──────────────────────────────────────────────┐
│ ← Documents   Project Proposal               │
├──────────────────────────────────────────────┤
│                                              │
│                                              │
│            ONLYOFFICE EDITOR                 │
│                                              │
│                                              │
│                                              │
└──────────────────────────────────────────────┘
```

ONLYOFFICE should occupy most of the viewport.

Use:

```css
height: calc(100vh - header-height);
```

Avoid unnecessary custom toolbar implementations because ONLYOFFICE already provides the Word-like toolbar.

---

# 24. Security

Implement the following:

### Authorization

Every document access must verify ownership.

### File paths

Never allow the user to submit an arbitrary file path.

### Uploads

For this MVP, users do not upload arbitrary DOCX files.

Only generate blank documents.

### Callback

The ONLYOFFICE callback endpoint must not blindly trust arbitrary requests.

If JWT is enabled, configure and validate ONLYOFFICE JWT properly.

For local development, JWT may initially be disabled.

For production, JWT should be enabled.

### XSS

Do not render document HTML directly in Blade.

The DOCX is handled by ONLYOFFICE.

### CSRF

Normal Laravel forms must use CSRF protection.

The ONLYOFFICE callback should be excluded from normal browser CSRF middleware because it is a server-to-server callback.

---

# 25. Services

Keep business logic out of controllers.

Create:

```text
app/Services/DocumentService.php
app/Services/OnlyOfficeService.php
```

## DocumentService

Responsible for:

```text
createBlankDocument()
getFile()
saveFile()
deleteFile()
```

## OnlyOfficeService

Responsible for:

```text
generateDocumentKey()
generateEditorConfig()
getDocumentUrl()
getCallbackUrl()
```

Controllers should remain thin.

---

# 26. Error Handling

Implement graceful errors for:

- document not found
- unauthorized document access
- missing physical file
- ONLYOFFICE unavailable
- ONLYOFFICE callback failure
- failed document download
- failed document save
- invalid document name

Log server-side failures using Laravel logging.

Do not expose internal exceptions to users.

---

# 27. Docker Development Environment

Provide:

```text
docker-compose.yml
```

containing ONLYOFFICE.

Laravel itself can initially run using:

```bash
php artisan serve
```

and MySQL can be local or Docker-based.

The README must explain how to run:

```bash
docker compose up -d
php artisan migrate
php artisan serve
```

---

# 28. README

Create a detailed but concise README covering:

## Requirements

- PHP
- Composer
- Node/NPM
- MySQL
- Docker

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

## Start ONLYOFFICE

```bash
docker compose up -d
```

## Start Laravel

```bash
php artisan serve
```

## Configuration

Explain:

```env
ONLYOFFICE_URL=
ONLYOFFICE_JWT_ENABLED=
ONLYOFFICE_JWT_SECRET=
```

## Networking

Explain that ONLYOFFICE must be able to reach Laravel's document file endpoint and Laravel callback must be reachable from ONLYOFFICE.

Include examples for:

```text
localhost
Docker network
LAN
Production domain
```

---

# 29. Testing

Create feature tests for:

### Authentication

Authenticated users can access documents.

### Authorization

User A cannot access User B's document.

Test:

```text
GET /documents/{other_user_document}
```

must return:

```text
403
```

or an appropriate authorization response.

### Document creation

Creating a document should:

- create DB record
- create physical DOCX
- associate it with current user

### Document deletion

Deleting should remove:

- database record
- physical file

### Download

Authenticated owner can download their document.

### Rename

Owner can rename document.

### Callback

Test the ONLYOFFICE callback handling for supported save statuses.

---

# 30. Important ONLYOFFICE Networking Requirement

Do not consider the implementation complete until this flow works:

```text
Browser
   │
   │ opens editor
   ▼
Laravel
   │
   │ generates ONLYOFFICE config
   ▼
Browser
   │
   │ loads ONLYOFFICE
   ▼
ONLYOFFICE
   │
   │ GET document URL
   ▼
Laravel
   │
   │ returns DOCX
   ▼
ONLYOFFICE
   │
   │ user edits
   ▼
ONLYOFFICE
   │
   │ callback
   ▼
Laravel
   │
   │ downloads updated DOCX
   ▼
Storage
```

Test every part of this flow.

---

# 31. Definition of Done

The implementation is complete only when all of these work:

- [ ] User can register.
- [ ] User can log in.
- [ ] User sees their documents.
- [ ] User can create a blank DOCX.
- [ ] User can open the document.
- [ ] ONLYOFFICE editor loads successfully.
- [ ] User can type text.
- [ ] User can format text.
- [ ] User can create multiple pages.
- [ ] User can create tables.
- [ ] User can insert images using ONLYOFFICE.
- [ ] Changes are saved.
- [ ] Closing and reopening the document preserves changes.
- [ ] User can rename a document.
- [ ] User can download the DOCX.
- [ ] User can delete the document.
- [ ] User cannot access another user's documents.
- [ ] ONLYOFFICE callback works.
- [ ] No document files are stored under `public/`.
- [ ] README explains the complete setup.
- [ ] Feature tests pass.

---

# 32. Important Implementation Rules

1. Do not build a custom Word editor.
2. Do not use Jodit.
3. Do not use CKEditor.
4. Do not use Tiptap for the MVP.
5. Use ONLYOFFICE as the document editing engine.
6. Keep Laravel responsible for application logic and document management.
7. Keep controllers thin.
8. Put document logic in services.
9. Use Laravel Policies for authorization.
10. Never trust document IDs from the browser without authorization.
11. Never expose filesystem paths to users.
12. Never store documents in `public/`.
13. Do not hard-code URLs.
14. Make ONLYOFFICE URL configurable.
15. Make JWT configuration configurable.
16. Use environment variables for deployment-specific settings.
17. Keep the initial implementation simple.
18. Do not add collaboration, comments, version history, sharing, or approval workflows yet.
19. Build the MVP first.
20. After the MVP works end-to-end, provide a short list of recommended next improvements.

---

# 33. Expected Final Project Structure

Aim for approximately:

```text
word-clone/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DocumentController.php
│   │   │   └── OnlyOfficeController.php
│   │   └── Policies/
│   │       └── DocumentPolicy.php
│   │
│   ├── Models/
│   │   └── Document.php
│   │
│   └── Services/
│       ├── DocumentService.php
│       └── OnlyOfficeService.php
│
├── config/
│   └── onlyoffice.php
│
├── database/
│   └── migrations/
│       └── xxxx_create_documents_table.php
│
├── resources/
│   └── views/
│       ├── documents/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       └── layouts/
│
├── routes/
│   └── web.php
│
├── storage/
│   └── app/
│       └── documents/
│
├── docker-compose.yml
├── .env.example
└── README.md
```

---

# 34. Development Order

Implement in this exact order:

### Phase 1 — Laravel

1. Create Laravel project.
2. Configure MySQL.
3. Install Breeze.
4. Configure authentication.
5. Create documents migration.
6. Create Document model.
7. Create DocumentPolicy.

### Phase 2 — Document Management

8. Create DocumentService.
9. Create document CRUD.
10. Generate blank DOCX.
11. Store DOCX.
12. Implement download.
13. Implement delete.
14. Implement rename.

### Phase 3 — ONLYOFFICE

15. Add ONLYOFFICE Docker container.
16. Configure ONLYOFFICE URL.
17. Create OnlyOfficeService.
18. Generate ONLYOFFICE editor configuration.
19. Create editor Blade page.
20. Load ONLYOFFICE API.
21. Open DOCX inside ONLYOFFICE.

### Phase 4 — Saving

22. Create document file endpoint.
23. Create ONLYOFFICE callback endpoint.
24. Handle save callbacks.
25. Download updated DOCX from ONLYOFFICE.
26. Replace stored document.
27. Test persistence.

### Phase 5 — Security

28. Verify document ownership.
29. Verify download authorization.
30. Secure callback.
31. Configure JWT support.
32. Validate all inputs.

### Phase 6 — Testing

33. Write feature tests.
34. Test document lifecycle.
35. Test authorization.
36. Test ONLYOFFICE save flow.

### Phase 7 — Documentation

37. Complete README.
38. Document Docker networking.
39. Document local development.
40. Document production deployment considerations.

---

# 35. Agent Execution Instructions

You are an autonomous coding agent.

Implement the application, not just the plan.

Before modifying files:

1. Inspect the existing repository.
2. Determine whether Laravel already exists.
3. Preserve existing working functionality if present.
4. Do not overwrite unrelated files.

After implementation:

1. Run migrations.
2. Run tests.
3. Run Laravel lint/static checks if available.
4. Verify the application boots.
5. Verify ONLYOFFICE configuration.
6. Verify the document creation flow.
7. Verify the editor integration.
8. Verify the callback/save flow.

If a dependency or infrastructure limitation prevents a full end-to-end test, clearly identify it.

Do not claim a feature works unless it has been verified.

At the end, provide:

```text
## Implemented

- ...
- ...
- ...

## Commands to Run

...

## Environment Variables

...

## Known Limitations

...

## Next Recommended Steps

...
```

Do not add unnecessary features beyond this MVP.