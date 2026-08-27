# DokuFlow Server Deployment Guide

This document provides step-by-step instructions for deploying **Project_DokuFlow** to the production/staging server at `/var/www/dokuflow.cmhgroup.id/`.

---

## 🔑 SSH Connection Details

- **Host**: `hyu.cmhgroup.id`
- **Port**: `2022`
- **User**: `root`
- **SSH Key**: `~/.ssh/hyu_deploy_key`
- **Target Path**: `/var/www/dokuflow.cmhgroup.id`

To connect manually via SSH:
```bash
ssh -p 2022 -i ~/.ssh/hyu_deploy_key root@hyu.cmhgroup.id
```

---

## 🚀 Step 1: Local Build & Syncing Files

Run these commands on your local machine inside the project root (`/home/austin/Web Dev/Projects/dokuflow/dokuflow-project`):

### 1.1 Compile Production Assets
```bash
npm install
npm run build
```

### 1.2 Transfer Files via Rsync over SSH
```bash
rsync -avz -e "ssh -p 2022 -i ~/.ssh/hyu_deploy_key" --progress \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='node_modules' \
  --exclude='public/storage' \
  --exclude='storage/*.key' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  . root@hyu.cmhgroup.id:/var/www/dokuflow.cmhgroup.id/
```

---

## 🌐 Step 2: Nginx Web Server Configuration

Connect to the server:
```bash
ssh -p 2022 -i ~/.ssh/hyu_deploy_key root@hyu.cmhgroup.id
```

Edit `/etc/nginx/sites-available/dokuflow.cmhgroup.id`:
```bash
nano /etc/nginx/sites-available/dokuflow.cmhgroup.id
```

Ensure the configuration matches:
```nginx
server {
    listen 80;
    listen [::]:80;

    server_name dokuflow.cmhgroup.id;

    # Point to the Laravel public folder
    root /var/www/dokuflow.cmhgroup.id/public;
    index index.php index.html;

    access_log /var/log/nginx/dokuflow.access.log;
    error_log /var/log/nginx/dokuflow.error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Laravel Reverb WebSocket Reverse Proxy (Use ^~ /app/ to avoid matching /approvals)
    location ^~ /app/ {
        proxy_pass http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Test and reload Nginx:
```bash
nginx -t && systemctl reload nginx
```

---

## ⚙️ Step 3: Laravel Application Setup on Server

Run the following commands on the remote server inside `/var/www/dokuflow.cmhgroup.id`:

```bash
cd /var/www/dokuflow.cmhgroup.id

# 1. Environment Configuration
cp .env.example .env
nano .env
```

Make sure to update key environment variables in `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://dokuflow.cmhgroup.id

# Database
DB_CONNECTION=sqlite # or mysql / pgsql

# Groq AI Key (for AI PDF Summarization)
GROQ_API_KEY=your_production_groq_api_key

# Laravel Reverb (WebSockets)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=dokuflow-prod
REVERB_APP_KEY=dokuflow-key
REVERB_APP_SECRET=dokuflow-secret
REVERB_HOST=dokuflow.cmhgroup.id
REVERB_PORT=8081
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT=80
VITE_REVERB_SCHEME=http

# ONLYOFFICE Configuration
ONLYOFFICE_URL=http://dokuflow.cmhgroup.id:8884
ONLYOFFICE_INTERNAL_URL=http://127.0.0.1:8884
ONLYOFFICE_JWT_ENABLED=true
ONLYOFFICE_JWT_SECRET=de1e91347d7abd4c831649a098693b4c21fcc932f21b9b97fc7bec2dbb957f6d
```

```bash
# 2. Install PHP Dependencies & Generate Key
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link

# 3. Database Migration & Optimization
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Set Directory Permissions
chown -R www-data:www-data /var/www/dokuflow.cmhgroup.id
chmod -R 775 /var/www/dokuflow.cmhgroup.id/storage /var/www/dokuflow.cmhgroup.id/bootstrap/cache
```

---

## 📄 Step 4: ONLYOFFICE Document Server Setup Guide

ONLYOFFICE Document Server provides online collaborative document editing (DOCX, XLSX, PPTX) for DokuFlow.

### 4.1 System Requirements & Server Sizing

Before deploying ONLYOFFICE, ensure your dedicated/remote server meets these requirements:

| Component | Minimum Requirement | Recommended |
| :--- | :--- | :--- |
| **RAM** | **4 GB** (strict minimum) | **8 GB+** |
| **CPU** | **2 Cores** (2.0 GHz+) | **4 Cores+** |
| **Disk Space** | **20 GB Free** | **40 GB+ Free** |
| **Docker Engine** | Version 20.10+ | Latest |
| **Docker Compose** | Version 2.0+ | Latest |

> [!WARNING]
> Do NOT attempt to run ONLYOFFICE on a server with less than 4 GB of RAM. The container initializes PostgreSQL, RabbitMQ, Node.js, and C++ conversion workers, which will trigger Linux Out-Of-Memory (OOM) crashes on low-memory servers.

---

### 4.2 Generating & Configuring JWT Security Secret

`JWT_SECRET` is a self-generated shared secret key used to secure communication between DokuFlow and ONLYOFFICE Document Server. You generate this key yourself.

#### Generating a Random 32-Byte Key

Run any of the following commands on your server terminal to generate a secure key:

```bash
# Option 1: Using OpenSSL
openssl rand -hex 32

# Option 2: Using PHP
php -r "echo bin2hex(random_bytes(32));"
```

> [!IMPORTANT]
> Save your generated key! The exact same string must be used in both DokuFlow's `.env` (`ONLYOFFICE_JWT_SECRET`) and ONLYOFFICE Docker container (`JWT_SECRET`).

---

### 4.3 Deploying via Docker (Recommended Production Approach)

Docker is the safest and recommended method because it encapsulates PostgreSQL, RabbitMQ, and C++ dependencies in an isolated container without conflicting with host web servers (cPanel, Nginx, or Apache).

#### Method 1: Single `docker run` Command (Fastest)

Run this command on your ONLYOFFICE server (replace `<YOUR_GENERATED_JWT_SECRET>` with the key generated above):

```bash
docker run -d -p 8884:80 \
  --name dokuflow-onlyoffice \
  --restart=always \
  -e JWT_ENABLED=true \
  -e JWT_SECRET=<YOUR_GENERATED_JWT_SECRET> \
  -e JWT_HEADER=Authorization \
  -v /app/onlyoffice/DocumentServer/logs:/var/log/onlyoffice \
  -v /app/onlyoffice/DocumentServer/data:/var/www/onlyoffice/Data \
  -v /app/onlyoffice/DocumentServer/lib:/var/lib/onlyoffice \
  -v /app/onlyoffice/DocumentServer/db:/var/lib/postgresql \
  onlyoffice/documentserver:latest
```

#### Method 2: Docker Compose (`docker-compose.yml`)

Create `/opt/onlyoffice/docker-compose.yml` on the ONLYOFFICE server:

```yaml
version: '3.8'

services:
  onlyoffice:
    image: onlyoffice/documentserver:latest
    container_name: dokuflow-onlyoffice
    restart: always
    ports:
      - "8884:80"
    environment:
      - JWT_ENABLED=true
      - JWT_SECRET=<YOUR_GENERATED_JWT_SECRET>
      - JWT_HEADER=Authorization
      - JWT_IN_BODY=true
    volumes:
      - /app/onlyoffice/logs:/var/log/onlyoffice
      - /app/onlyoffice/data:/var/www/onlyoffice/Data
      - /app/onlyoffice/lib:/var/lib/onlyoffice
      - /app/onlyoffice/db:/var/lib/postgresql
```

Launch the service:
```bash
docker compose up -d
```

---

### 4.3 Setting up Nginx Reverse Proxy with SSL (Optional / Production Domain)

If hosting ONLYOFFICE on a subdomain (e.g. `office.cmhgroup.id`) with HTTPS:

Create Nginx site config `/etc/nginx/sites-available/onlyoffice.conf`:

```nginx
server {
    listen 80;
    server_name office.cmhgroup.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name office.cmhgroup.id;

    ssl_certificate /etc/letsencrypt/live/office.cmhgroup.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/office.cmhgroup.id/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:8884;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Enable site & reload Nginx:
```bash
ln -s /etc/nginx/sites-available/onlyoffice.conf /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

### 4.4 Configuring DokuFlow `.env` Integration

On your DokuFlow application server (`/var/www/dokuflow.cmhgroup.id/.env`):

```env
# ONLYOFFICE Configuration
# ONLYOFFICE_URL: Public URL of ONLYOFFICE Docker server (accessed by user browser)
ONLYOFFICE_URL=http://<ONLYOFFICE_VPS_IP>:8884

# ONLYOFFICE_INTERNAL_URL: Public URL/IP of DokuFlow (accessed by ONLYOFFICE to download DOCX & post callbacks)
ONLYOFFICE_INTERNAL_URL=http://dokuflow.cmhgroup.id

ONLYOFFICE_JWT_ENABLED=true
ONLYOFFICE_JWT_SECRET=<YOUR_GENERATED_JWT_SECRET>
DOCUMENT_STORAGE_DISK=local
ONLYOFFICE_AUTOSAVE=false
ONLYOFFICE_FORCESAVE=false
```

After updating `.env`, clear Laravel configuration cache:
```bash
php artisan config:cache
```

---

### 4.5 Health Verification & Troubleshooting

1. **Fix "The file cannot be accessed right now" Error (`allowPrivateIPAddress`):**
   By default, ONLYOFFICE blocks requests to private/internal IP addresses. Enable `allowPrivateIPAddress` inside the container by running this on your ONLYOFFICE VPS:
   ```bash
   docker exec -it dokuflow-onlyoffice sed -i 's/"allowPrivateIPAddress": false/"allowPrivateIPAddress": true/g' /etc/onlyoffice/documentserver/default.json
   docker exec -it dokuflow-onlyoffice supervisorctl restart all
   ```

2. **Verify Health Check Endpoint:**
   Run from your DokuFlow application server:
   ```bash
   curl -i http://<ONLYOFFICE_SERVER_IP>:8884/healthcheck
   ```
   *Expected Response:* `true` (Status `200 OK`)

3. **Verify Welcome Page:**
   ```bash
   curl -I http://<ONLYOFFICE_SERVER_IP>:8884/welcome/
   ```

4. **Check Container Logs (if editor fails to load):**
   ```bash
   docker logs -f dokuflow-onlyoffice
   ```

5. **Common Issues & Solutions:**
   - **Download Failed / Cannot Load Document ("The file cannot be accessed right now"):**
     1. Ensure `ONLYOFFICE_INTERNAL_URL` in `.env` points to DokuFlow's URL (`http://dokuflow.cmhgroup.id`), NOT to ONLYOFFICE.
     2. Ensure `allowPrivateIPAddress` is set to `true` inside the ONLYOFFICE container (Step 1 above).
   - **JWT Token Invalid:** Ensure `ONLYOFFICE_JWT_SECRET` in DokuFlow `.env` matches `JWT_SECRET` in ONLYOFFICE Docker container environment.
   - **Mixed Content Error (HTTPS vs HTTP):** If DokuFlow runs on `https://`, ONLYOFFICE **must** also be served via HTTPS (`https://office.cmhgroup.id`).

---

## 🔄 Step 5: Supervisor Setup (Queue Worker & Reverb WebSocket)

Install Supervisor (if not already installed):
```bash
apt-get update && apt-get install -y supervisor
```

Create `/etc/supervisor/conf.d/dokuflow.conf`:
```ini
[program:dokuflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dokuflow.cmhgroup.id/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/dokuflow.cmhgroup.id/storage/logs/worker.log

[program:dokuflow-reverb]
command=php /var/www/dokuflow.cmhgroup.id/artisan reverb:start --port=8081
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/dokuflow.cmhgroup.id/storage/logs/reverb.log
```

Update Supervisor and start processes:
```bash
supervisorctl reread
supervisorctl update
supervisorctl start dokuflow-worker:*
supervisorctl start dokuflow-reverb
```

---

## 🤖 Automated Deployment Script (`deploy.sh`)

You can use the local script named `deploy.sh` in the project root to automate deployments:

```bash
#!/usr/bin/env bash
set -e

SSH_CMD="ssh -p 2022 -i ~/.ssh/hyu_deploy_key"
REMOTE="root@hyu.cmhgroup.id"
REMOTE_DIR="/var/www/dokuflow.cmhgroup.id"

echo "📦 Building local assets..."
npm run build

echo "🚀 Syncing files to server..."
rsync -avz -e "${SSH_CMD}" --progress \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='node_modules' \
  --exclude='public/storage' \
  --exclude='storage/*.key' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  . ${REMOTE}:${REMOTE_DIR}/

echo "⚡ Running post-deployment commands on server..."
${SSH_CMD} ${REMOTE} << EOF
  cd ${REMOTE_DIR}
  composer install --no-dev --optimize-autoloader
  rm -f public/storage
  php artisan storage:link
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan queue:restart
  chown -R www-data:www-data ${REMOTE_DIR}
  chmod -R 775 ${REMOTE_DIR}/storage ${REMOTE_DIR}/bootstrap/cache ${REMOTE_DIR}/storage/app/public
  supervisorctl restart dokuflow-reverb
EOF

echo "✅ Deployment successful!"
```

To run:
```bash
chmod +x deploy.sh
./deploy.sh
```

---

## 🛠️ Troubleshooting & Production Gotchas

### Gotcha 1: Nginx WebSocket Route Conflict (`/app` vs `/approvals`)
* **Symptom:** Opening `https://dokuflow.cmhgroup.id/approvals` returns a plain text `404 Not found.`.
* **Root Cause:** Using `location /app` in Nginx matches **ANY** URL starting with `/app` (including `/approvals`), which forwards web requests to Laravel Reverb (Port 8081).
* **Fix:** Ensure the Nginx location directive uses `location ^~ /app/` with trailing slash and `^~` modifier:
  ```nginx
  location ^~ /app/ {
      proxy_pass http://127.0.0.1:8081;
      proxy_http_version 1.1;
      proxy_set_header Upgrade $http_upgrade;
      proxy_set_header Connection "Upgrade";
      proxy_set_header Host $host;
      proxy_set_header X-Real-IP $remote_addr;
      proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
      proxy_set_header X-Forwarded-Proto $scheme;
  }
  ```

### Gotcha 2: Broken `/storage` Symlink & 403 Forbidden Images
* **Symptom:** Assets in `/storage/` (e.g., `https://dokuflow.cmhgroup.id/storage/logo.png`) return `403 Forbidden` or `404 Not Found`.
* **Root Cause:** Running `rsync` from local machine uploads the local machine symlink (`/home/austin/Web Dev/.../storage/app/public`) to the server, pointing to a non-existent local directory path on the server.
* **Fix:** Re-link storage directly on the production server and reset directory permissions:
  ```bash
  ssh -p 2022 -i ~/.ssh/hyu_deploy_key root@hyu.cmhgroup.id "rm -f /var/www/dokuflow.cmhgroup.id/public/storage && cd /var/www/dokuflow.cmhgroup.id && php artisan storage:link && chown -R www-data:www-data /var/www/dokuflow.cmhgroup.id/storage /var/www/dokuflow.cmhgroup.id/public/storage && chmod -R 775 /var/www/dokuflow.cmhgroup.id/storage /var/www/dokuflow.cmhgroup.id/storage/app/public"
  ```

### Gotcha 3: HTTPS & Let's Encrypt SSL Setup
* **Symptom:** Browser shows "Not Secure" or defaults to another domain's SSL certificate (`advert.cmhgroup.id`).
* **Fix:** Issue a dedicated SSL certificate for `dokuflow.cmhgroup.id`:
  ```bash
  certbot --nginx -d dokuflow.cmhgroup.id --non-interactive --agree-tos --email admin@cmhgroup.id --redirect
  ```
  Ensure `.env` has:
  ```env
  APP_URL=https://dokuflow.cmhgroup.id
  ```
  Then clear/re-cache config:
  ```bash
  php artisan config:cache && php artisan route:cache
  ```

### Gotcha 4: Mandatory Digital Signature Requirement (`EnsureUserHasSignature`)
* **Symptom:** Logging in as Admin or a new user immediately redirects to `/profile?must_sign=1`.
* **Root Cause:** DokuFlow's security policy requires every user to have a digital signature (`hasSignature() == true`) before accessing documents or approvals.
* **Fix:** Ensure users draw their signature on `/profile` or seed default Admin signature via `php artisan db:seed`.

