import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { ApiItem } from '../models/api';
import { HomepageSection } from '../models/homepage';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class HomepageService {
  private readonly api = inject(ApiService);

  sections(): Observable<HomepageSection[]> {
    return this.api
      .get<ApiItem<HomepageSection[]>>('homepage')
      .pipe(map((res) => res.data.filter((section) => section.is_visible !== false)));
  }
}
