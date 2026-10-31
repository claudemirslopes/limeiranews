import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ArticleListItem, ArticleListParams, ArticleShowResponse } from '../models/article';
import { Paginated } from '../models/api';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class ArticleService {
  private readonly api = inject(ApiService);

  list(params: ArticleListParams = {}): Observable<Paginated<ArticleListItem>> {
    return this.api.get<Paginated<ArticleListItem>>('articles', { ...params });
  }

  get(slug: string): Observable<ArticleShowResponse> {
    return this.api.get<ArticleShowResponse>(`articles/${encodeURIComponent(slug)}`);
  }
}
