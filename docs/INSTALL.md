# Limeira News — Instalação (desenvolvimento)

## Requisitos

- PHP 8.2+ (XAMPP)
- Composer 2
- MySQL/MariaDB (XAMPP)
- Node.js 20+ e npm
- Angular CLI via `npx`

## Estrutura

```
limeiranews/
  backend/     # Laravel 12 API (Sanctum)
  src/         # Angular 22 (portal + /admin)
  docs/        # Arquitetura e instalação
```

## 1. Banco de dados

No MySQL (phpMyAdmin ou CLI):

```sql
CREATE DATABASE limeiranews CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 2. Backend

```bash
cd backend
copy .env.example .env   # se ainda não existir
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
```

A API sobe pelo Apache do XAMPP, sem `php artisan serve`:

`http://localhost/limeiranews/api/v1`

### Credenciais de desenvolvimento (seed)

| Papel        | E-mail                         | Senha              |
|--------------|--------------------------------|--------------------|
| Superadmin   | admin@limeiranews.com.br       | ChangeMe!Admin1    |
| Editor       | editor@limeiranews.com.br      | ChangeMe!Editor1   |
| Jornalista   | jornalista@limeiranews.com.br  | ChangeMe!Jornal1   |

Altere essas senhas antes de qualquer ambiente compartilhado.

## 3. Frontend

```bash
cd ..   # raiz do monorepo
npm install
npm run build:xampp
```

Portal: `http://localhost/limeiranews/`  
Admin: `http://localhost/limeiranews/admin/login`

A URL da API no build do XAMPP está em `src/environments/environment.ts` (`/limeiranews/api/v1`).

`npm start` continua disponível para o servidor de desenvolvimento do Angular e usa a mesma API do XAMPP.

## 4. Filas e agendamento

- Publicação agendada: `php artisan articles:publish-scheduled` (também no scheduler Laravel).
- Em produção: configure o cron `* * * * * php artisan schedule:run` e um worker de fila.

## 5. Testes backend

```bash
cd backend
php artisan test
```

## Pendências conhecidas (próximas etapas)

- SSR / prerender Angular para SEO completo
- Sitemap XML, RSS e JSON-LD
- CRUD admin de usuários, mídia avançada, ads e menus
- E-mail de confirmação da newsletter
- Deploy Apache/Nginx + HTTPS em limeiranews.com.br
