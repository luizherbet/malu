#!/usr/bin/env bash
# Show local network URL for Malu (same Wi-Fi access).
# Usage: ./scripts/local-network.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

PORT="${APP_PORT:-8000}"

pick_ip() {
    for iface in en0 en1 bond0; do
        ip=$(ipconfig getifaddr "$iface" 2>/dev/null || true)
        if [ -n "$ip" ]; then
            echo "$ip"
            return 0
        fi
    done
    return 1
}

IP="$(pick_ip || true)"

echo "==> Malu — acesso na rede local (Wi‑Fi)"
echo ""

if [ -z "$IP" ]; then
    echo "Não foi possível detectar o IP local (en0/en1)."
    echo "Veja em Ajustes do Sistema → Rede → Wi‑Fi → Detalhes."
    exit 1
fi

URL="http://${IP}:${PORT}"

echo "  URL para compartilhar: ${URL}"
echo ""
echo "  No .env defina:"
echo "    APP_URL=${URL}"
echo ""
echo "  Depois:"
echo "    docker compose up -d"
echo "    docker compose restart app"
echo ""
echo "  A outra pessoa precisa estar na MESMA rede Wi‑Fi."
echo "  Login: malu@malu.com + MALU_AUTH_PASSWORD do .env"
echo ""
echo "  Se não abrir: Ajustes → Firewall → permitir Docker ou desativar temporariamente."
