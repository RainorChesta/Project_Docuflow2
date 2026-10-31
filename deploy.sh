#!/bin/bash
# ==============================================================================
# Deployment Script untuk dokuflow.cmhgroup.id (dokuflow-project)
# ==============================================================================

set -e

# Konfigurasi Server (Sesuai panduan DEPLOYMENT.md)
SERVER_USER="${DEPLOY_USER:-root}"
SERVER_HOST="${DEPLOY_HOST:-hyu.cmhgroup.id}"
REMOTE_PATH="${DEPLOY_PATH:-/var/www/dokuflow.cmhgroup.id}"
SSH_PORT="${DEPLOY_PORT:-2022}"
SSH_KEY="${DEPLOY_KEY:-$HOME/.ssh/hyu_deploy_key}"

# Parse flags: --migrate (-m), --composer (-c), --skip-composer, --reverb (-r), --skip-build
RUN_MIGRATE=false
RUN_COMPOSER=true
RESTART_REVERB=false
SKIP_BUILD=false

for arg in "$@"; do
    case $arg in
        --migrate|-m)
            RUN_MIGRATE=true
            ;;
        --composer|-c)
            RUN_COMPOSER=true
            ;;
        --skip-composer)
            RUN_COMPOSER=false
            ;;
        --reverb|-r)
            RESTART_REVERB=true
            ;;
        --skip-build)
            SKIP_BUILD=true
            ;;
        *)
            ;;
    esac
done

SSH_CMD="ssh -p $SSH_PORT -o StrictHostKeyChecking=no"
if [ -f "$SSH_KEY" ]; then
    SSH_CMD="$SSH_CMD -i $SSH_KEY"
fi

echo "=================================================="
echo "🚀 Memulai Deployment ke https://dokuflow.cmhgroup.id"
echo "Target: $SERVER_USER@$SERVER_HOST:$REMOTE_PATH (Port $SSH_PORT)"
echo "=================================================="

# 1. Build Frontend Assets
if [ "$SKIP_BUILD" = false ]; then
    echo ""
    echo "📦 1. Melakukan build asset frontend..."
    npm run build
else
    echo ""
    echo "⏩ 1. Melewati build asset frontend (--skip-build)..."
fi

# 2. Sync Files ke Server (Rsync)
echo ""
echo "📤 2. Mengunggah file ke server..."
rsync -avz -e "$SSH_CMD" \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='.git' \
    --exclude='public/hot' \
    --exclude='public/storage' \
    --exclude='bootstrap/cache/*' \
    --exclude='storage/*.key' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/logs/*' \
    ./ "$SERVER_USER@$SERVER_HOST:$REMOTE_PATH/"

# 3. Jalankan Composer (default: true, lewati dengan --skip-composer)
if [ "$RUN_COMPOSER" = true ]; then
    echo ""
    echo "🎼 3. Menjalankan composer install di server..."
    $SSH_CMD "$SERVER_USER@$SERVER_HOST" "cd $REMOTE_PATH && composer install --no-dev --optimize-autoloader --no-interaction"
else
    echo ""
    echo "⏩ 3. Melewati composer install (--skip-composer)..."
fi

# 4. Jalankan Migrasi Database jika diminta
if [ "$RUN_MIGRATE" = true ]; then
    echo ""
    echo "🗄️ 4. Menjalankan database migration di server..."
    $SSH_CMD "$SERVER_USER@$SERVER_HOST" "cd $REMOTE_PATH && php artisan migrate --force"
fi

# 5. Pastikan Storage Link & Set Permissions di Server
echo ""
echo "🔒 5. Mengatur storage symlink & folder permissions di server..."
$SSH_CMD "$SERVER_USER@$SERVER_HOST" "cd $REMOTE_PATH && ([ -L public/storage ] || php artisan storage:link) && chown -R www-data:www-data $REMOTE_PATH/storage $REMOTE_PATH/bootstrap/cache && chmod -R 775 $REMOTE_PATH/storage $REMOTE_PATH/bootstrap/cache"

# 6. Clear & Optimize Cache + Restart Queue Worker di Server
echo ""
echo "⚡ 6. Membersihkan cache aplikasi & merestart queue worker di server..."
$SSH_CMD "$SERVER_USER@$SERVER_HOST" "cd $REMOTE_PATH && php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear && php artisan queue:restart"

# 7. Restart Reverb WebSocket jika diminta
if [ "$RESTART_REVERB" = true ]; then
    echo ""
    echo "🔌 7. Merestart proses Laravel Reverb di server..."
    $SSH_CMD "$SERVER_USER@$SERVER_HOST" "supervisorctl restart dokuflow-reverb"
fi

echo ""
echo "=================================================="
echo "✅ Deployment dokuflow berhasil selesai!"
echo "Akses: https://dokuflow.cmhgroup.id"
echo "=================================================="

