# Limeira News — Arquitetura

## Visão geral

Monorepo com dois aplicativos:

| Pasta | Papel |
|-------|--------|
| `backend/` | Laravel API (fonte oficial de dados, auth, permissões, SEO feeds) |
| raiz Angular (`src/`) | Portal público + painel `/admin` consumindo `/api/v1` |

Domínio de produção: `https://limeiranews.com.br`  
Timezone: `America/Sao_Paulo` · Locale: `pt_BR`

## Princípios

1. **Laravel é a fonte da verdade** — regras editoriais, permissões e estados vivem no backend.
2. **Angular só consome a API** — guards no front; autorização real no servidor.
3. **SSR/prerender** — páginas públicas indexáveis (título, corpo, metadados no HTML inicial).
4. **Workflow editorial** — rascunho → revisão → aprovada → agendada/publicada → arquivada.
5. **Mídia e performance desde o início** — upload validado, variantes, cache e filas.

## Estrutura Laravel (`backend/`)

```
app/
  Http/Controllers/Api/V1/     # Public + Admin
  Http/Requests/
  Http/Resources/
  Models/
  Policies/
  Services/                    # ArticlePublisher, SlugService, MediaOptimizer…
  Jobs/
  Enums/                       # ArticleStatus, UserRole…
database/migrations|seeders|factories
routes/api.php                 # prefix /api/v1
```

## Estrutura Angular

```
src/app/
  core/          # auth, interceptors, guards, API config
  shared/        # UI reutilizável, pipes (data BR)
  layouts/       # public-shell, admin-shell
  features/
    home/
    articles/
    categories/
    search/
    pages/
    admin/       # dashboard, articles, media, users, ads…
```

## Modelo de dados (núcleo)

- `users`, `roles`, `permissions`, `role_permission`, `role_user`
- `categories` (hierárquicas), `tags`, `regions`, `cities`
- `articles` + `article_revisions` + pivôs (`category`, `tag`, `media`)
- `media`, `redirects`, `menus` / `menu_items`
- `homepage_sections`, `pages`
- `advertisers`, `ad_campaigns`, `newsletter_subscribers`
- `audit_logs`

## API (`/api/v1`)

**Público:** articles, categories, search, homepage, menus, pages, ads ativos, newsletter subscribe  
**Admin (Sanctum):** CRUD completo, publish/schedule, media, users, dashboard stats

## Auth e papéis

Sanctum (token SPA/API). Papéis: `superadmin`, `admin`, `editor_chefe`, `editor`, `journalist`, `columnist`, `commercial`, `analyst`.

## Etapas de entrega

1. Fundação (este passo): Laravel + migrations + seeders + auth + Angular core  
2. Núcleo editorial + APIs públicas/admin  
3. Portal público integrado  
4. Painel admin  
5. SEO (sitemap, RSS, JSON-LD), ads, newsletter  
6. SSR, testes, docs de deploy  
