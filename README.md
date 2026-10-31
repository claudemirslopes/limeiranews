# Limeira News

Portal jornalístico de Limeira/SP — Laravel API + Angular.

- **Arquitetura:** [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- **Instalação:** [docs/INSTALL.md](docs/INSTALL.md)
- **Backend:** [docs/BACKEND.md](docs/BACKEND.md)

## Abrir pelo XAMPP

Com Apache e MySQL ligados no painel do XAMPP:

- Portal: http://localhost/limeiranews/
- Admin: http://localhost/limeiranews/admin/login
- API: http://localhost/limeiranews/api/v1
- Sitemap: http://localhost/limeiranews/sitemap.xml
- RSS: http://localhost/limeiranews/feed.xml

Depois de mudar o portal, gere de novo os arquivos estáticos:

```bash
npm run build:xampp
```  
