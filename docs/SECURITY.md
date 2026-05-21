# Segurança — Malu

## O que já está protegido

| Área | Medida |
|------|--------|
| URLs | Apenas `http`/`https`; bloqueio de IPs privados/reservados (SSRF) |
| Arquivos | Downloads só em `downloads/`; `basename` no nome da faixa |
| API | Rate limit por IP (criar job / ler status) |
| Sessão | CSRF em POST/PUT/DELETE; cookies `same-origin` |
| HTTP | Headers `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, HSTS em HTTPS |
| Jobs | Um worker por download (`WithoutOverlapping`); timeout alinhado à fila |
| Conta | Opcional: `MALU_REQUIRE_AUTH=true` liga login obrigatório |
| Downloads | Com login: cada job fica no `user_id`; outro usuário recebe 403 |

## Modos de deploy

### Uso pessoal (padrão)

```env
MALU_REQUIRE_AUTH=false
```

Qualquer pessoa com o link do site pode listar playlists e baixar. O UUID do job ainda é difícil de adivinhar, mas **não é autenticação**.

Recomendado: rede local, VPN ou reverse proxy com senha (Basic Auth / Authelia).

### Servidor compartilhado

```env
MALU_REQUIRE_AUTH=true
MALU_ALLOW_REGISTRATION=false
```

Crie usuários com `php artisan tinker` ou seed. Desative registro público para evitar contas abertas.

## Checklist de produção

1. `APP_DEBUG=false`
2. HTTPS na frente do app
3. `MALU_REQUIRE_AUTH=true` se exposto na internet
4. Cookies do YouTube em arquivo privado (`docs/YOUTUBE.md`)
5. Limites de rate limit ajustados se necessário
6. Firewall: não expor Redis/MySQL publicamente

## Autenticação

- Sessão Laravel (cookie), não token em `localStorage`
- Senhas com bcrypt (padrão Laravel)
- Logout invalida sessão e regenera token CSRF
