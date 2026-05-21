# Malu

Serviço web para baixar mídia a partir de links (YouTube e outros suportados pelo [yt-dlp](https://github.com/yt-dlp/yt-dlp)).

**Stack:** Laravel 13, Vue 3, Vite, Redis (filas), yt-dlp + ffmpeg.

Documentação de arquitetura: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Docker (recomendado)

```bash
docker compose up --build
```

Guia completo: [docs/DOCKER.md](docs/DOCKER.md).

## Requisitos

- PHP 8.3+
- Composer
- Node.js 20+
- Redis
- [yt-dlp](https://github.com/yt-dlp/yt-dlp) e ffmpeg no PATH

## Setup local

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

Configure no `.env`:

- `QUEUE_CONNECTION=redis`
- `REDIS_CLIENT=predis` (ou `phpredis` se a extensão estiver instalada)
- `YTDLP_BINARY=yt-dlp`

## Desenvolvimento

Com Redis rodando:

```bash
composer dev
```

Isso sobe servidor HTTP, worker de fila, logs (Pail) e Vite em paralelo.

Ou manualmente:

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
npm run dev
```

## Manutenção (ops)

Comandos agendados (timeout de jobs e limpeza de arquivos):

```bash
php artisan downloads:expire-stale   # jobs presos em queued/processing
php artisan downloads:cleanup        # remove arquivos antigos
php artisan schedule:work            # roda o agendador em dev
```

Variáveis no `.env`:

- `DOWNLOAD_STALE_QUEUED_MINUTES` (padrão: 30)
- `DOWNLOAD_STALE_PROCESSING_MINUTES` (padrão: 15; alinhar com `YTDLP_TIMEOUT`)
- `DOWNLOAD_RETENTION_HOURS` (padrão: 24)
- `DOWNLOAD_PRUNE_RECORDS` (padrão: true)

## Testes

```bash
php artisan test
```
