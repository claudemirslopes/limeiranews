import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { ApiItem, MessageResponse, Paginated } from '../models/api';
import {
  AdminArticleListParams,
  Article,
  ArticleListItem,
  ArticlePayload,
} from '../models/article';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class AdminArticleService {
  private readonly api = inject(ApiService);

  list(params: AdminArticleListParams = {}): Observable<Paginated<ArticleListItem>> {
    return this.api.get<Paginated<ArticleListItem>>('admin/articles', { ...params });
  }

  get(id: number): Observable<Article> {
    return this.api.get<ApiItem<Article>>(`admin/articles/${id}`).pipe(map((res) => res.data));
  }

  create(payload: ArticlePayload): Observable<Article> {
    return this.api.post<ApiItem<Article>>('admin/articles', payload).pipe(map((res) => res.data));
  }

  update(id: number, payload: Partial<ArticlePayload>): Observable<Article> {
    return this.api
      .put<ApiItem<Article>>(`admin/articles/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  remove(id: number): Observable<MessageResponse> {
    return this.api.delete<MessageResponse>(`admin/articles/${id}`);
  }

  submitReview(id: number): Observable<Article> {
    return this.transition(id, 'submit-review');
  }

  publish(id: number): Observable<Article> {
    return this.transition(id, 'publish');
  }

  archive(id: number): Observable<Article> {
    return this.transition(id, 'archive');
  }

  private transition(id: number, action: string): Observable<Article> {
    return this.api
      .post<ApiItem<Article>>(`admin/articles/${id}/${action}`)
      .pipe(map((res) => res.data));
  }
}
