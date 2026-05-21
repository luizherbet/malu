# YouTube — Malu / yt-dlp

YouTube frequentemente exige **runtime JavaScript** e **cookies de sessão** para permitir download. Sem isso aparecem erros como:

- `No supported JavaScript runtime could be found`
- `Sign in to confirm you're not a bot`
- `HTTP Error 429: Too Many Requests`

## 1. Runtime JavaScript (obrigatório)

O Malu já passa `--js-runtimes node` por padrão (`YTDLP_JS_RUNTIMES=node`).

**No Mac (sem Docker):** instale Node.js (`brew install node`).

**No Docker:** a imagem já inclui Node na etapa `base`. Rebuild:

```bash
docker compose build --no-cache
docker compose up -d
```

## 2. Cookies do YouTube (quase sempre necessário)

Dentro do Docker **não há navegador** — exporte cookies no seu Mac e monte o arquivo no projeto.

### Exportar cookies (no Mac, com Chrome logado no YouTube)

```bash
yt-dlp --cookies-from-browser chrome \
  --cookies storage/app/private/cookies/youtube.txt \
  --skip-download \
  "https://www.youtube.com/watch?v=VIDEO_ID"
```

### Configurar no `.env`

```env
YTDLP_COOKIES_FILE=storage/app/private/cookies/youtube.txt
YTDLP_JS_RUNTIMES=node
YTDLP_SLEEP_REQUESTS=1
```

Reinicie o worker:

```bash
php artisan queue:restart
# ou
docker compose restart queue
```

### Desenvolvimento local (sem arquivo de cookies)

Se o worker roda **no host** (não no container), pode usar o navegador direto:

```env
YTDLP_COOKIES_FROM_BROWSER=chrome
```

> Não use `cookies-from-browser` dentro do Docker — não há perfil do Chrome no container.

## 3. Erro 429 (Too Many Requests)

- Espere alguns minutos entre tentativas
- Use cookies exportados (`YTDLP_COOKIES_FILE`)
- `YTDLP_SLEEP_REQUESTS=1` já reduz rajadas (padrão)

## 4. Atualizar yt-dlp

```bash
yt-dlp -U
# Docker: rebuild da imagem ou atualizar binário no Dockerfile
```

## Checklist rápido

- [ ] Node instalado / imagem Docker reconstruída
- [ ] `youtube.txt` exportado e caminho no `.env`
- [ ] Worker reiniciado após mudar `.env`
- [ ] Testou o mesmo vídeo no terminal: `yt-dlp --cookies storage/app/private/cookies/youtube.txt URL`
