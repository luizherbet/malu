#!/usr/bin/env bash
# Setup Malu on Ubuntu (Oracle Cloud Always Free VM).
# Run on the server: bash scripts/setup-oracle.sh

set -euo pipefail

REPO_URL="${MALU_REPO_URL:-https://github.com/luizherbet/malu.git}"
INSTALL_DIR="${MALU_INSTALL_DIR:-$HOME/malu}"

echo "==> Malu — Oracle Cloud setup"

if ! command -v docker >/dev/null 2>&1; then
    echo "==> Installing Docker..."
    sudo apt-get update
    sudo apt-get install -y ca-certificates curl git
    sudo install -m 0755 -d /etc/apt/keyrings
    sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    sudo chmod a+r /etc/apt/keyrings/docker.asc
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
      $(. /etc/os-release && echo "${VERSION_CODENAME:-$VERSION}") stable" \
      | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
    sudo usermod -aG docker "$USER"
    echo "==> Docker installed. If 'docker' fails, log out and SSH again."
fi

if [ ! -d "$INSTALL_DIR/.git" ]; then
    echo "==> Cloning repository into $INSTALL_DIR"
    git clone "$REPO_URL" "$INSTALL_DIR"
fi

cd "$INSTALL_DIR"

mkdir -p storage/app/private/cookies storage/app/private/downloads
chmod -R u+rwX storage 2>/dev/null || true

if [ ! -f .env ]; then
    cp .env.example .env
    echo "==> Created .env from .env.example — edit before starting:"
    echo "    nano $INSTALL_DIR/.env"
fi

PUBLIC_IP=""
if command -v curl >/dev/null 2>&1; then
    PUBLIC_IP=$(curl -fsS --max-time 3 https://ifconfig.me 2>/dev/null || true)
fi

echo ""
echo "==> Next steps:"
echo "  1. Edit .env (APP_URL, APP_KEY, MALU_AUTH_PASSWORD):"
echo "       nano $INSTALL_DIR/.env"
echo "  2. Generate APP_KEY:"
echo "       cd $INSTALL_DIR && docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show"
echo "  3. Upload YouTube cookies from your Mac (see docs/DEPLOY-ORACLE.md)"
echo "  4. Start Malu:"
echo "       cd $INSTALL_DIR && docker compose -f docker-compose.prod.yml up -d --build"
if [ -n "$PUBLIC_IP" ]; then
    echo "  5. Open in browser: http://${PUBLIC_IP}:8000"
fi
echo ""
echo "Full guide: docs/DEPLOY-ORACLE.md"
