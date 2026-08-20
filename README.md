# DokuFlow — Document Management System with ONLYOFFICE Integration

DokuFlow is an enterprise-grade document management system built with Laravel 12 and integrated with **ONLYOFFICE Docs Community Edition** for rich DOCX document editing, version tracking, multi-division approvals, digital signatures, AI document summarization, and fine-grained access sharing.

---

## Requirements

- **PHP**: 8.3+
- **Composer**: 2.x
- **Node.js**: 20+ & NPM
- **Database**: MySQL 8+ or SQLite
- **Docker & Docker Compose**: For running ONLYOFFICE Docs Community Edition

---

## Quick Start & Setup

### 1. Clone & Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Configure your `.env` settings:
```env
APP_NAME=DokuFlow
APP_URL=http://localhost:8000

# ONLYOFFICE Document Server Configuration
ONLYOFFICE_URL=http://localhost:8080
ONLYOFFICE_INTERNAL_URL=http://host.docker.internal:8000
ONLYOFFICE_JWT_ENABLED=false
ONLYOFFICE_JWT_SECRET=
DOCUMENT_STORAGE_DISK=local
```

### 3. Run Database Migrations & Build Assets

```bash
php artisan migrate
npm run build
```

---

## Running ONLYOFFICE Docs

Start the ONLYOFFICE Docs Community Edition Docker container:

```bash
docker compose up -d
```

The ONLYOFFICE Docs API will be accessible at:
```text
http://localhost:8080/web-apps/apps/api/documents/api.js
```

To verify the container is running:
```bash
docker ps
```

---

## Running the Application

Start the development server:

```bash
php artisan serve
```

Run background workers (for AI summarization & queues):
```bash
php artisan queue:listen
```

---

## ONLYOFFICE Architecture & Networking

```text
Browser
   │
   ├── 1. Opens Editor (/documents/{id}/edit) ───────► Laravel
   │                                                   │
   ├── 2. Loads ONLYOFFICE API (api.js) ◄──────────────┤ Returns ONLYOFFICE Config
   │         │
   ▼         ▼
ONLYOFFICE Docs Server (http://localhost:8080)
   │
   ├── 3. Fetches DOCX (/onlyoffice/documents/{id}/versions/{v}/file) ──► Laravel Storage
   │
   └── 4. Sends Save Callback (/onlyoffice/documents/{id}/callback) ────► Laravel VersionService
```

### Networking Tips:
- **`ONLYOFFICE_URL`**: Accessible by the client browser (`http://localhost:8080` in local dev).
- **`ONLYOFFICE_INTERNAL_URL`**: Accessible by the ONLYOFFICE Docker container to reach Laravel (`http://host.docker.internal:8000` on Windows/Mac Docker Desktop, or the host machine's IP).
- **Server Callbacks**: The callback route `/onlyoffice/documents/{document}/callback` is exempted from CSRF middleware for server-to-server communication.

---

## Running Tests

```bash
php artisan test
```
