# Limeira News — Backend (Laravel 12 API)

## Setup local

```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate   # se necessário
# MySQL/MariaDB (XAMPP): crie o banco
mysql -uroot -e "CREATE DATABASE limeiranews CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve        # http://localhost:8000
php artisan schedule:work  # publica matérias agendadas (articles:publish-scheduled)
```

`.env` relevante: `DB_CONNECTION=mysql`, `DB_DATABASE=limeiranews`, `DB_USERNAME=root`, `DB_PASSWORD=` (vazio),
`FRONTEND_URL=http://localhost:4200`, `APP_TIMEZONE=America/Sao_Paulo`, `APP_LOCALE=pt_BR`.
CORS permite `http://localhost:4200` e `http://127.0.0.1:4200` (`config/cors.php`, variável `CORS_ALLOWED_ORIGINS`).

## Credenciais de desenvolvimento (TROCAR em produção)

| Papel | E-mail | Senha |
|-------|--------|-------|
| superadmin | `admin@limeiranews.com.br` | `ChangeMe!Admin1` |
| journalist | `jornalista@limeiranews.com.br` | `ChangeMe!Jornal1` |
| editor | `editor@limeiranews.com.br` | `ChangeMe!Editor1` |

Os artigos do `DemoArticleSeeder` são **conteúdo de demonstração** (somente `local`/`testing`), com `allow_index = false`.

## Autenticação

Token Bearer (Sanctum). `POST /api/v1/auth/login` → `{ token, token_type, user }`.
Enviar `Authorization: Bearer <token>` nas rotas admin. Login tem rate limit (5 tentativas/min por e-mail+IP).

## Endpoints (`/api/v1`)

**Públicos**

- `GET articles` (filtros: `category`, `city`, `region`, `tag`, `featured`, `per_page`) · `GET articles/{slug}`
- `GET categories` · `GET categories/{slug}` (com artigos paginados em `articles`)
- `GET homepage` · `GET search?q=` · `GET menus/{header|footer}` · `GET pages/{slug}`
- `GET breaking-news` · `GET ads?position=` · `POST newsletter/subscribe` · `GET newsletter/confirm/{token}`

Somente matérias `published` com `published_at <= agora` aparecem. `view_count` nunca é exposto publicamente.

**Auth**: `POST auth/login`, `POST auth/logout`, `GET auth/me`

**Admin** (`auth:sanctum`, prefixo `admin/`)

- `GET dashboard`
- `articles` (CRUD) + `POST articles/{id}/{publish|schedule|submit-review|approve|request-revision|archive|duplicate}`
- `categories` (CRUD) · `media` (GET/POST/DELETE) · `homepage-sections` (GET, PUT/PATCH `{id}`)

## Fluxo editorial e permissões

`draft → in_review → (revision_requested | approved) → (scheduled | published) → archived`

| Papel | Pode |
|-------|------|
| superadmin, admin | tudo |
| editor_chefe, editor | criar, editar, aprovar, publicar, arquivar (excluir: só editor_chefe) |
| journalist, columnist | criar; editar/enviar para revisão apenas as **próprias** matérias em rascunho/revisão solicitada; não publicam |
| commercial, analyst | sem acesso a matérias |

Regras em `App\Policies\ArticlePolicy` e `App\Services\ArticleWorkflowService`. O corpo das matérias é sanitizado por `App\Support\HtmlSanitizer`
(tags permitidas: `p, br, strong, em, ul, ol, li, h2, h3, blockquote, a, img, figure, figcaption`; atributos `on*`/`style`/URLs `javascript:` removidos).

## Testes

```bash
php artisan test            # SQLite em memória (phpunit.xml)
```

## Pendências conhecidas

- Envio do e-mail de confirmação da newsletter (token já é gerado; ver `TODO(mail)` em `NewsletterController`).
- Redirecionamentos (`redirects`) são gravados ao alterar slug de matéria publicada; a resolução no `GET articles/{slug}` ainda não é aplicada.
- CRUD admin de usuários, páginas, menus, anúncios e tags ainda não exposto (tabelas/modelos já existem).
