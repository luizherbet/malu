# Deploy — Malu

Guia para colocar o Malu em produção usando a imagem Docker.

**Sem servidor pago?** Use Oracle Cloud Always Free: [DEPLOY-ORACLE.md](DEPLOY-ORACLE.md).

## Requisitos do servidor

- Docker 24+ e Compose v2 (ou runtime compatível)
- Porta HTTP/HTTPS exposta
- Espaço em disco para arquivos temporários de download

## Build da imagem

```bash
docker build --target production -t malu:latest .
```

A imagem inclui PHP 8.4, `yt-dlp`, `ffmpeg` e assets Vue já compilados.

## Variáveis de ambiente

Copie `.env.example` para `.env` no host ou use `environment` no Compose:

| Variável | Produção |
|----------|----------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Gerar com `php artisan key:generate --show` |
| `APP_URL` | URL pública (ex. `https://malu.example.com`) |
| `QUEUE_CONNECTION` | `redis` |
| `REDIS_HOST` | `redis` (nome do serviço no Compose) |
| `DB_CONNECTION` | `sqlite` (MVP) ou `pgsql` / `mysql` |
| `YTDLP_TIMEOUT` | `600` ou conforme carga |
| `DOWNLOAD_RETENTION_HOURS` | `24` (ajustar disco) |

## Exemplo `docker-compose.prod.yml`

```yaml
services:
  app:
    image: malu:latest
    ports:
      - "8000:8000"
    env_file: .env
    environment:
      REDIS_HOST: redis
    depends_on:
      - redis
    restart: unless-stopped

  queue:
    image: malu:latest
    env_file: .env
    environment:
      REDIS_HOST: redis
    command: ["php", "artisan", "queue:work", "--tries=1", "--timeout=3720"]
    depends_on:
      - redis
    restart: unless-stopped

  scheduler:
    image: malu:latest
    env_file: .env
    command: ["php", "artisan", "schedule:work"]
    depends_on:
      - redis
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    restart: unless-stopped
```

```bash
docker compose -f docker-compose.prod.yml up -d
```

## HTTPS

Coloque um reverse proxy (Caddy, Nginx ou Traefik) na frente do serviço `app` na porta 8000.

Exemplo Caddy:

```
malu.example.com {
    reverse_proxy app:8000
}
```

## Persistência

Monte volumes para:

- `storage/app/private` — arquivos baixados
- `database/database.sqlite` — se usar SQLite

```yaml
volumes:
  - malu_storage:/var/www/html/storage/app/private
  - malu_db:/var/www/html/database
```

## Checklist pós-deploy

- [ ] `php artisan migrate --force` executado (entrypoint faz na primeira subida)
- [ ] Worker `queue` rodando
- [ ] `scheduler` rodando (ou cron com `schedule:run`)
- [ ] `yt-dlp` atualizado periodicamente na imagem (`docker build` nova versão)
- [ ] Rate limits adequados (`DOWNLOAD_RATE_LIMIT_*`)
- [ ] Logs e espaço em disco monitorados

## CI

Cada push em `main` e PR disparam testes, Pint e build da imagem Docker — ver [.github/workflows/ci.yml](../.github/workflows/ci.yml).
