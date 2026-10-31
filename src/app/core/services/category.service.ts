import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { ApiItem } from '../models/api';
import { Category, CategoryShowResponse } from '../models/category';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class CategoryService {
  private readonly api = inject(ApiService);

  list(): Observable<Category[]> {
    return this.api.get<ApiItem<Category[]>>('categories').pipe(map((res) => res.data));
  }

  /** Lista plana (pais + filhos), útil para selects. */
  listFlat(): Observable<Category[]> {
    return this.list().pipe(
      map((categories) =>
        categories.flatMap((category) => [category, ...(category.children ?? [])]),
      ),
    );
  }

  get(slug: string, page = 1, perPage = 12): Observable<CategoryShowResponse> {
    return this.api.get<CategoryShowResponse>(`categories/${encodeURIComponent(slug)}`, {
      page,
      per_page: perPage,
    });
  }
}
