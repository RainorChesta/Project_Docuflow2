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

    # Laravel Reverb WebSocket Reverse Proxy
    location /app {
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
ONLYOFFICE_URL=http://dokuflow.cmhgroup.id:8080
ONLYOFFICE_INTERNAL_URL=http://127.0.0.1:8080
ONLYOFFICE_JWT_ENABLED=true
ONLYOFFICE_JWT_SECRET=dokuflow_onlyoffice_secret_key_2026
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

## 📄 Step 4: ONLYOFFICE Document Server Setup

Choose **Option A** (Docker Container) or **Option B** (Native Bare-Metal without Docker):

---

### Option A: Docker Setup (Recommended & Quickest)

Start the ONLYOFFICE DocumentServer container using Docker Compose:

```bash
cd /var/www/dokuflow.cmhgroup.id
docker compose up -d
```

Verify that ONLYOFFICE is running on port 8080:
```bash
docker ps
curl -I http://localhost:8080
```

---

### Option B: Native Setup without Docker (Bare-Metal on Ubuntu/Debian)

If Docker is not installed or preferred, install `onlyoffice-documentserver` directly via APT:

#### B.1 Install Prerequisites (PostgreSQL & RabbitMQ)

```bash
sudo apt-get update
sudo apt-get install -y postgresql rabbitmq-server

# Create PostgreSQL Database and User for ONLYOFFICE
sudo -i -u postgres psql -c "CREATE DATABASE onlyoffice;"
sudo -i -u postgres psql -c "CREATE USER onlyoffice WITH password 'onlyoffice';"
sudo -i -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE onlyoffice TO onlyoffice;"
```

#### B.2 Add ONLYOFFICE Official APT Repository

```bash
mkdir -p ~/.gnupg
chmod 700 ~/.gnupg
gpg --no-default-keyring --keyring gnupg-ring:/tmp/onlyoffice.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys CB2DE8E5
chmod 644 /tmp/onlyoffice.gpg
sudo chown root:root /tmp/onlyoffice.gpg
sudo mv /tmp/onlyoffice.gpg /usr/share/keyrings/onlyoffice.gpg
echo "deb [signed-by=/usr/share/keyrings/onlyoffice.gpg] https://download.onlyoffice.com/repo/debian squeeze main" | sudo tee /etc/apt/sources.list.d/onlyoffice.list
```

#### B.3 Set Port 8080 for ONLYOFFICE (to avoid conflict with main Nginx)

```bash
echo onlyoffice-documentserver onlyoffice/ds-port select 8080 | sudo debconf-set-selections
```

#### B.4 Install ONLYOFFICE DocumentServer

```bash
sudo apt-get update
sudo apt-get install -y onlyoffice-documentserver
```
*(When prompted for the PostgreSQL password during installation, enter `onlyoffice`).*

#### B.5 Configure JWT Security Secret

Edit `/etc/onlyoffice/documentserver/local.json`:

```bash
sudo nano /etc/onlyoffice/documentserver/local.json
```

Update the `secret` and `token` sections to match your `.env` secret (`dokuflow_onlyoffice_secret_key_2026`):

```json
{
  "services": {
    "CoAuthoring": {
      "secret": {
        "inbox": { "string": "dokuflow_onlyoffice_secret_key_2026" },
        "outbox": { "string": "dokuflow_onlyoffice_secret_key_2026" },
        "session": { "string": "dokuflow_onlyoffice_secret_key_2026" }
      },
      "token": {
        "enable": {
          "request": {
            "inbox": true,
            "outbox": true
          },
          "browser": true
        }
      }
    }
  }
}
```

Restart ONLYOFFICE DocumentServer services:

```bash
sudo supervisorctl restart all
```

Verify service availability:

```bash
curl -I http://localhost:8080
```

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
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan queue:restart
  chown -R www-data:www-data ${REMOTE_DIR}
  chmod -R 775 ${REMOTE_DIR}/storage ${REMOTE_DIR}/bootstrap/cache
  supervisorctl restart dokuflow-reverb
EOF

echo "✅ Deployment successful!"
```

To run:
```bash
chmod +x deploy.sh
./deploy.sh
```
