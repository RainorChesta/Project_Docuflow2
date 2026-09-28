#!/usr/bin/env bash
# ==============================================================================
# DocuFlow - OnlyOffice F4 Remote Patch Runner
# Usage:
#   ./setup-onlyoffice-f4-remote.sh [SSH_HOST] [SSH_USER] [SSH_PORT] [CONTAINER]
#
# Example:
#   ./setup-onlyoffice-f4-remote.sh 202.10.46.4 root 22 dokuflow-onlyoffice
# ==============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PYTHON_SCRIPT="${SCRIPT_DIR}/patch_onlyoffice_f4.py"

SSH_HOST="${1:-202.10.46.4}"
SSH_USER="${2:-root}"
SSH_PORT="${3:-22}"
CONTAINER="${4:-dokuflow-onlyoffice}"

echo "=================================================="
echo "  DocuFlow - Remote OnlyOffice F4 Setup Runner   "
echo "=================================================="
echo "Remote Server    : ${SSH_USER}@${SSH_HOST}:${SSH_PORT}"
echo "Target Container : ${CONTAINER}"
echo "Patch Script     : ${PYTHON_SCRIPT}"
echo "=================================================="
echo ""

if [ ! -f "${PYTHON_SCRIPT}" ]; then
    echo "❌ Error: File ${PYTHON_SCRIPT} tidak ditemukan."
    exit 1
fi

echo "🚀 Menyuntikkan patch F4 ke dalam container [${CONTAINER}] di [${SSH_HOST}]..."
ssh -p "${SSH_PORT}" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "docker exec -i ${CONTAINER} python3 -" < "${PYTHON_SCRIPT}"

echo ""
echo "✅ Patching selesai! Silakan lakukan Hard Refresh (Ctrl + F5) di browser."
