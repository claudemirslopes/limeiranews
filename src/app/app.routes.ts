import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { AdminLayout } from './layouts/admin-layout/admin-layout';
import { PublicLayout } from './layouts/public-layout/public-layout';

export const routes: Routes = [
  {
    path: '',
    component: PublicLayout,
    children: [
      {
        path: '',
        title: 'Limeira News — Informação que conecta Limeira ao mundo',
        loadComponent: () => import('./features/home/home-page/home-page').then((m) => m.HomePage),
      },
      {
        path: 'noticia/:slug',
        loadComponent: () =>
          import('./features/articles/article-page/article-page').then((m) => m.ArticlePage),
      },
      {
        path: 'categoria/:slug',
        loadComponent: () =>
          import('./features/categories/category-page/category-page').then((m) => m.CategoryPage),
      },
      {
        path: 'busca',
        loadComponent: () =>
          import('./features/search/search-page/search-page').then((m) => m.SearchPage),
      },
      {
        path: 'pagina/:slug',
        loadComponent: () =>
          import('./features/pages/static-page/static-page').then((m) => m.StaticPage),
      },
    ],
  },
  {
    path: 'admin/login',
    title: 'Entrar — Limeira News',
    loadComponent: () => import('./features/admin/login/login-page').then((m) => m.LoginPage),
  },
  {
    path: 'admin',
    component: AdminLayout,
    canActivate: [authGuard],
    title: 'Painel — Limeira News',
    children: [
      {
        path: '',
        loadComponent: () =>
          import('./features/admin/dashboard/dashboard-page').then((m) => m.DashboardPage),
      },
      {
        path: 'noticias',
        loadComponent: () =>
          import('./features/admin/articles/article-list/article-list').then((m) => m.ArticleList),
      },
      {
        path: 'noticias/nova',
        loadComponent: () =>
          import('./features/admin/articles/article-form/article-form').then((m) => m.ArticleForm),
      },
      {
        path: 'noticias/:id/editar',
        loadComponent: () =>
          import('./features/admin/articles/article-form/article-form').then((m) => m.ArticleForm),
      },
    ],
  },
  {
    path: '**',
    component: PublicLayout,
    children: [
      {
        path: '',
        title: 'Página não encontrada — Limeira News',
        loadComponent: () =>
          import('./features/not-found/not-found-page').then((m) => m.NotFoundPage),
      },
    ],
  },
];
