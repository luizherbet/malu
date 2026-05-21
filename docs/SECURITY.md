# Segurança — Malu

## Autenticação JWT

O Malu usa **JWT (Bearer token)** para um único usuário configurado no `.env`:

```env
MALU_REQUIRE_AUTH=true
MALU_AUTH_EMAIL=malu@malu.com
MALU_AUTH_PASSWORD=sua-senha-aqui
MALU_JWT_SECRET=              # vazio = usa APP_KEY
MALU_JWT_TTL_MINUTES=10080    # 7 dias
```

- Login: `POST /api/auth/login` com e-mail e senha
- Demais rotas: header `Authorization: Bearer <token>`
- O token fica no `localStorage` do navegador
- Apenas o e-mail `MALU_AUTH_EMAIL` é aceito; a senha deve coincidir com `MALU_AUTH_PASSWORD`

Na primeira autenticação o usuário é criado/atualizado na tabela `users`.

## O que está protegido

| Área | Medida |
|------|--------|
| API | JWT obrigatório (quando `MALU_REQUIRE_AUTH=true`) |
| Downloads | Cada job pertence ao `user_id` do token; outro usuário → 403 |
| URLs | Anti-SSRF (sem IPs privados) |
| Arquivos | Paths limitados a `downloads/` |
| Rate limit | Por IP (login, criar job, ler status) |
| HTTP | Security headers (nosniff, frame deny, HSTS em HTTPS) |

## Desenvolvimento / testes

```env
MALU_REQUIRE_AUTH=false
```

Desliga o JWT e permite API aberta (apenas para ambiente local).

## Produção

1. `APP_DEBUG=false` e HTTPS
2. `MALU_AUTH_PASSWORD` forte e único
3. `MALU_JWT_SECRET` dedicado (ou `APP_KEY` forte)
4. Cookies YouTube em arquivo privado (`docs/YOUTUBE.md`)
5. Não expor Redis na internet
