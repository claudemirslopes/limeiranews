import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { ApiItem } from '../models/api';
import { StaticPage } from '../models/category';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class PageService {
  private readonly api = inject(ApiService);

  get(slug: string): Observable<StaticPage> {
    return this.api
      .get<ApiItem<StaticPage>>(`pages/${encodeURIComponent(slug)}`)
      .pipe(map((res) => res.data));
  }
}
