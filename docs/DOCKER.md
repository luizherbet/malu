# Docker — Malu

Stack completo com um comando: app HTTP, worker de fila, scheduler e Redis.

## Requisitos

- Docker 24+
- Docker Compose v2

## Subir o projeto

```bash
docker compose up --build
```

Abra http://localhost:8000

Na primeira execução o entrypoint:

1. Cria `.env` a partir de `.env.example` (se não existir)
2. Roda `composer install` e `npm run build` (imagem `development` inclui Node)
3. Executa migrations no SQLite

## Serviços

| Serviço | Função |
|---------|--------|
| `app` | `php artisan serve` na porta 8000 |
| `queue` | `php artisan queue:work` |
| `scheduler` | `php artisan schedule:work` |
| `redis` | Fila Redis |

## Comandos úteis

```bash
# Rodar em segundo plano
docker compose up -d --build

# Testes
docker compose run --rm app php artisan test

# Shell no container
docker compose exec app bash

# Parar
docker compose down
```

## Variáveis

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `APP_PORT` | `8000` | Porta HTTP exposta |
| `REDIS_PORT` | `6379` | Porta Redis exposta |
| `APP_URL` | `http://localhost:8000` | URL da aplicação |

O Compose define `REDIS_HOST=redis` automaticamente nos serviços PHP.

## Imagem de produção

Build da imagem otimizada (assets pré-compilados, sem Node no runtime):

```bash
docker build --target production -t malu:latest .
```

## Notas

- O volume `vendor` evita sobrescrever dependências PHP do host (macOS/Linux).
- `yt-dlp` e `ffmpeg` já vêm na imagem.
- Para desenvolvimento frontend com HMR, use `npm run dev` no host ou adicione um serviço Vite ao Compose.
