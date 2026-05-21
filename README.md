# Malu

Serviço web para baixar mídia a partir de links (YouTube e outros suportados pelo [yt-dlp](https://github.com/yt-dlp/yt-dlp)).

**Stack:** Laravel 13, Vue 3, Vite, Redis (filas), yt-dlp + ffmpeg.

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
npm run dev
```

## Próximos passos

1. Modelo e migration `downloads`
2. Job `ProcessDownloadJob` com yt-dlp
3. API e UI de colar link + progresso
# malu
