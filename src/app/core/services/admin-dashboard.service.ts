import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { ApiItem } from '../models/api';
import { DashboardData } from '../models/user';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class AdminDashboardService {
  private readonly api = inject(ApiService);

  get(): Observable<DashboardData> {
    return this.api.get<ApiItem<DashboardData>>('admin/dashboard').pipe(map((res) => res.data));
  }
}
