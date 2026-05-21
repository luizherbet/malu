# Malu no Mac — teste com outra pessoa (Wi‑Fi ou Cloudflare Tunnel)

Sem VPS e sem cartão. O Malu roda no **seu Mac** com Docker; a outra pessoa abre um **link no navegador**.

## O que ela precisa receber

- **Link** (túnel HTTPS ou IP da rede)
- **E-mail:** `malu@malu.com`
- **Senha:** a do `MALU_AUTH_PASSWORD` no seu `.env`

Ela **não** instala Docker nem acessa seu Mac — só o site.

---

## Antes de começar (uma vez)

### 1. Cookies do YouTube (no Mac)

```bash
cd /caminho/do/malu
mkdir -p storage/app/private/cookies

yt-dlp --cookies-from-browser chrome \
  --cookies storage/app/private/cookies/youtube.txt \
  --skip-download \
  "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
```

No `.env`:

```env
YTDLP_COOKIES_FILE=storage/app/private/cookies/youtube.txt
YTDLP_COOKIES_FROM_BROWSER=
```

### 2. Senha de login no `.env`

```env
MALU_REQUIRE_AUTH=true
MALU_AUTH_EMAIL=malu@malu.com
MALU_AUTH_PASSWORD=sua-senha-aqui
```

### 3. Subir o Malu com Docker

```bash
cd /caminho/do/malu
docker compose up -d --build
```

Teste local: http://localhost:8000

---

## Opção A — Cloudflare Tunnel (ela em qualquer lugar)

Funciona com a pessoa **longe** da sua casa. **Grátis**, sem cartão Oracle.

### A.1 Instalar o túnel

```bash
brew install cloudflared
```

### A.2 Iniciar túnel + Malu

Na pasta do projeto:

```bash
./scripts/start-tunnel.sh
```

Ou manualmente:

```bash
docker compose up -d
cloudflared tunnel --url http://127.0.0.1:8000
```

O terminal mostra um link assim:

```text
https://alguma-coisa-random.trycloudflare.com
```

**Copie esse link.**

### A.3 Atualizar `APP_URL` (importante)

Edite `.env`:

```env
APP_URL=https://alguma-coisa-random.trycloudflare.com
```

Reinicie o app:

```bash
docker compose restart app
```

### A.4 Enviar para a pessoa

- Link: o `https://....trycloudflare.com`
- Login: `malu@malu.com` + senha

### Limitações do túnel rápido

| Item | Detalhe |
|------|---------|
| Mac ligado | Se desligar ou dormir, o site cai |
| Link muda | Cada vez que reinicia o túnel, URL nova → atualizar `APP_URL` e avisar ela |
| Internet | Upload/download passa pela sua rede |

Para link **fixo**, crie conta grátis em [Cloudflare](https://dash.cloudflare.com) e configure um Named Tunnel (fora do escopo deste guia rápido).

---

## Opção B — Mesma rede Wi‑Fi (mais simples)

Só funciona se ela estiver na **mesma rede** (mesma casa).

### B.1 Descobrir o IP do Mac

```bash
./scripts/local-network.sh
```

Ou:

```bash
ipconfig getifaddr en0
```

Exemplo: `192.168.1.42`

### B.2 Configurar `.env`

```env
APP_URL=http://192.168.1.42:8000
```

```bash
docker compose restart app
```

### B.3 Firewall do Mac

**Ajustes do Sistema → Rede → Firewall** (ou Segurança):

- Desative o firewall temporariamente para testar, **ou**
- Permita conexões para **Docker** / terminal

### B.4 Link para ela

```text
http://192.168.1.42:8000
```

Login: `malu@malu.com` + senha.

---

## Comandos úteis

```bash
# Ver se tudo está rodando
docker compose ps

# Logs do worker (downloads)
docker compose logs -f queue

# Parar tudo
docker compose down

# Reiniciar após mudar .env
docker compose restart app queue
```

---

## Problemas comuns

| Problema | Solução |
|----------|---------|
| **Abre no PC, não no celular** | Apague `public/hot`, rode `npm run build`, `docker compose restart app`. Não use `npm run dev` com túnel. |
| Ela não abre o link (Wi‑Fi) | Mesma rede? Firewall? IP certo? |
| Túnel não abre | `brew install cloudflared`; Docker na porta 8000? |
| Login falha | `MALU_AUTH_PASSWORD` no `.env`; `docker compose restart app` |
| Lista/baixa falha | Cookies `youtube.txt`; `docker compose restart queue` |
| Baixar MP3 não funciona (túnel) | `APP_URL` = URL do túnel (com `https://`) |

### Página em branco no celular (causa mais comum)

Se você rodou `composer dev` ou `npm run dev`, existe o arquivo `public/hot` e o HTML aponta para `http://localhost:5173` — só o seu Mac enxerga isso.

```bash
rm -f public/hot
npm run build
docker compose restart app
```

Depois abra o link do túnel **no celular** de novo (aba anônima ajuda).

---

## Resumo

```text
Seu Mac (Docker: app + queue + redis)
        ↑
   Cloudflare Tunnel  OU  IP Wi‑Fi :8000
        ↑
   Navegador dela (login + playlist)
```

Mantenha o Mac acordado e o Docker rodando enquanto ela usar.
