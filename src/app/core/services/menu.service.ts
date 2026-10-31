import { Injectable, inject } from '@angular/core';
import { Observable, catchError, map, of, shareReplay } from 'rxjs';
import { ApiItem } from '../models/api';
import { BreakingNews, Menu, MenuItem } from '../models/category';
import { ApiService } from './api.service';

const FALLBACK_SECTIONS = [
  'Limeira',
  'Região',
  'Política',
  'Polícia',
  'Esportes',
  'Economia',
  'Saúde',
  'Entretenimento',
  'Brasil',
  'Mundo',
];

export function slugify(value: string): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

export const FALLBACK_MENU_ITEMS: MenuItem[] = FALLBACK_SECTIONS.map((label) => ({
  label,
  url: `/categoria/${slugify(label)}`,
  category_slug: slugify(label),
  children: [],
}));

@Injectable({ providedIn: 'root' })
export class MenuService {
  private readonly api = inject(ApiService);

  private header$?: Observable<MenuItem[]>;

  /** Itens do menu do cabeçalho; usa fallback estático se a API falhar. */
  header(): Observable<MenuItem[]> {
    this.header$ ??= this.api.get<ApiItem<Menu>>('menus/header').pipe(
      map((res) => (res.data.items.length ? res.data.items : FALLBACK_MENU_ITEMS)),
      catchError(() => of(FALLBACK_MENU_ITEMS)),
      shareReplay(1),
    );
    return this.header$;
  }

  breaking(): Observable<BreakingNews | null> {
    return this.api.get<ApiItem<BreakingNews | null>>('breaking-news').pipe(
      map((res) => res.data),
      catchError(() => of(null)),
    );
  }
}
