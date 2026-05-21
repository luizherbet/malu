#!/usr/bin/env bash
# Start Malu (Docker) and expose via Cloudflare quick tunnel.
# Usage: ./scripts/start-tunnel.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker não encontrado. Instale Docker Desktop: https://www.docker.com/products/docker-desktop/"
    exit 1
fi

if ! command -v cloudflared >/dev/null 2>&1; then
    echo "cloudflared não encontrado. Instale com:"
    echo "  brew install cloudflared"
    exit 1
fi

echo "==> Build do frontend (obrigatório para celular / túnel)..."
rm -f public/hot
if [ ! -f public/build/manifest.json ]; then
    npm run build
fi

echo "==> Subindo Malu (Docker)..."
docker compose up -d --build

echo ""
echo "==> Aguardando app na porta 8000..."
for i in $(seq 1 30); do
    if curl -fsS -o /dev/null "http://127.0.0.1:8000/up" 2>/dev/null || curl -fsS -o /dev/null "http://127.0.0.1:8000" 2>/dev/null; then
        break
    fi
    sleep 2
done

echo ""
echo "==> Iniciando Cloudflare Tunnel..."
echo ""
echo "    Quando aparecer o link https://....trycloudflare.com :"
echo "    1. Copie o link"
echo "    2. Coloque no .env:  APP_URL=https://SEU-LINK"
echo "    3. Rode: docker compose restart app"
echo "    4. Envie o link + login (malu@malu.com) para a pessoa"
echo ""
echo "    Mantenha este terminal aberto. Ctrl+C encerra o túnel."
echo ""

exec cloudflared tunnel --url http://127.0.0.1:8000
