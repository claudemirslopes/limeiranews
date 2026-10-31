import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { Paginated } from '../models/api';
import { ArticleListItem } from '../models/article';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class SearchService {
  private readonly api = inject(ApiService);

  search(q: string, page = 1, perPage = 12): Observable<Paginated<ArticleListItem>> {
    return this.api.get<Paginated<ArticleListItem>>('search', { q, page, per_page: perPage });
  }
}
