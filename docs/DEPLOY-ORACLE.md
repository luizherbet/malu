# Deploy no Oracle Cloud (Always Free)

Guia para colocar o Malu online **sem mensalidade**, usando uma VM gratuita da Oracle. Uma pessoa acessa pelo **navegador** (link + login), sem instalar Docker.

## O que você vai ter no final

- URL: `http://IP-PUBLICO:8000` (ou `https://seu-dominio.com` se configurar HTTPS)
- Login: `malu@malu.com` + senha definida por você no `.env`
- App, fila de download e Redis rodando 24h na VM

## Parte 1 — Conta e VM na Oracle (console web)

### 1.1 Criar conta

1. Acesse [https://www.oracle.com/cloud/free/](https://www.oracle.com/cloud/free/)
2. Cadastre-se (pede cartão para **verificação** — recursos **Always Free** não geram cobrança se usados corretamente)
3. Escolha a **região** mais próxima (ex.: São Paulo `sa-saopaulo-1`)

### 1.2 Criar a VM (Always Free)

1. Menu **Compute** → **Instances** → **Create instance**
2. Nome: `malu`
3. **Image:** Ubuntu 22.04 ou 24.04 (aarch64 / ARM)
4. **Shape:** clique em **Change shape** → filtre **Ampere** → escolha **VM.Standard.A1.Flex**
   - OCPUs: **2**, Memory: **12 GB** (cabe no free tier e sobra para o Malu)
   - Confirme o badge **Always Free-eligible**
5. **Networking:** marque **Assign a public IPv4 address**
6. **SSH keys:** gere ou envie sua chave pública (recomendado)
7. **Boot volume:** padrão (50 GB free)
8. **Create**

Anote o **IP público** da instância (ex. `123.45.67.89`).

### 1.3 Abrir portas no firewall da Oracle

1. Na instância, clique no **Subnet** (link azul)
2. **Security Lists** → lista padrão → **Add Ingress Rules**

Adicione estas regras (Source CIDR `0.0.0.0/0`):

| Porta | Para quê |
|-------|----------|
| **22** | SSH (administração) |
| **8000** | Malu (HTTP direto) |
| **80** | HTTPS (se usar Caddy depois) |
| **443** | HTTPS |

Protocol: TCP. Depois **Add Ingress Rules**.

### 1.4 Conectar por SSH

No seu Mac:

```bash
ssh -i ~/.ssh/sua_chave ubuntu@IP-PUBLICO
```

(Usuário pode ser `ubuntu` ou `opc` conforme a imagem.)

---

## Parte 2 — Instalar o Malu na VM (script automático)

No servidor (SSH), rode:

```bash
curl -fsSL https://raw.githubusercontent.com/luizherbet/malu/main/scripts/setup-oracle.sh | bash
```

Ou, se preferir clonar manualmente:

```bash
sudo apt-get update
sudo apt-get install -y git docker.io docker-compose-v2
sudo usermod -aG docker $USER
# logout e login de novo no SSH

git clone https://github.com/luizherbet/malu.git
cd malu
bash scripts/setup-oracle.sh
```

O script instala Docker, clona o projeto (se necessário) e prepara pastas.

---

## Parte 3 — Configurar `.env` de produção

Na VM, dentro da pasta `malu`:

```bash
cd ~/malu   # ou onde clonou
cp .env.example .env
nano .env
```

Ajuste **obrigatoriamente**:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://SEU-IP-PUBLICO:8000

REDIS_HOST=redis
QUEUE_CONNECTION=redis

MALU_REQUIRE_AUTH=true
MALU_AUTH_EMAIL=malu@malu.com
MALU_AUTH_PASSWORD=escolha-uma-senha-forte

YTDLP_COOKIES_FILE=storage/app/private/cookies/youtube.txt
YTDLP_JS_RUNTIMES=node
YTDLP_SLEEP_REQUESTS=1
QUEUE_RETRY_AFTER=3720
QUEUE_WORKER_TIMEOUT=3720
```

Gere `APP_KEY`:

```bash
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show
```

Cole o valor em `APP_KEY=` no `.env`.

---

## Parte 4 — Cookies do YouTube (no seu Mac)

No **Mac** (Chrome logado no YouTube), na pasta do projeto Malu:

```bash
mkdir -p storage/app/private/cookies
yt-dlp --cookies-from-browser chrome \
  --cookies storage/app/private/cookies/youtube.txt \
  --skip-download \
  "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
```

Envie o arquivo para a VM:

```bash
scp -i ~/.ssh/sua_chave \
  storage/app/private/cookies/youtube.txt \
  ubuntu@IP-PUBLICO:~/malu/storage/app/private/cookies/youtube.txt
```

Na VM, confira:

```bash
ls -la ~/malu/storage/app/private/cookies/youtube.txt
```

---

## Parte 5 — Subir o Malu

Na VM:

```bash
cd ~/malu
docker compose -f docker-compose.prod.yml up -d --build
```

Aguarde o build (primeira vez demora alguns minutos).

Verifique:

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f queue
```

Todos os serviços devem estar `running`: `app`, `queue`, `scheduler`, `redis`.

---

## Parte 6 — Testar e passar para quem vai usar

1. No navegador: `http://IP-PUBLICO:8000`
2. Login: `malu@malu.com` + senha do `MALU_AUTH_PASSWORD`
3. Cole um link de playlist → **Listar músicas** → **Baixar** em uma faixa

Envie para a pessoa:

- **Link:** `http://IP-PUBLICO:8000`
- **E-mail:** `malu@malu.com`
- **Senha:** (a que você definiu)

Ela **não** usa Docker — só o site.

---

## HTTPS opcional (domínio + Caddy)

Se tiver um domínio apontando para o IP da VM, na VM:

```bash
cd ~/malu
docker compose -f docker-compose.prod.yml -f docker-compose.https.yml up -d
```

Atualize `APP_URL=https://malu.seudominio.com` no `.env` e reinicie:

```bash
docker compose -f docker-compose.prod.yml -f docker-compose.https.yml up -d --build
```

---

## Comandos úteis

```bash
# Ver logs
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f queue

# Reiniciar após mudar .env
docker compose -f docker-compose.prod.yml restart queue app

# Atualizar código
cd ~/malu && git pull
docker compose -f docker-compose.prod.yml up -d --build

# Parar tudo
docker compose -f docker-compose.prod.yml down
```

---

## Evitar cobrança na Oracle

- Use apenas shape **Ampere A1** com badge **Always Free**
- Não crie load balancers, databases pagos ou VMs AMD extras
- Monitore **Billing** → Cost Analysis (deve ficar em zero no Always Free)

---

## Problemas comuns

| Sintoma | Solução |
|---------|---------|
| Site não abre de fora | Confira Security List (porta 8000) e `docker compose ps` |
| Login falha | `MALU_AUTH_PASSWORD` no `.env`, `config:clear`, reinicie `app` |
| Lista/baixa falha no YouTube | Envie `youtube.txt` de cookies; veja `docs/YOUTUBE.md` |
| Job trava / falha na fila | `docker compose logs queue`; reinicie `queue` |

---

## Resumo

```text
Oracle VM (grátis) → Docker → Malu
         ↑
    IP público :8000
         ↑
   Navegador da pessoa (login + playlist)
```

Você administra pela SSH; ela só usa o link.
