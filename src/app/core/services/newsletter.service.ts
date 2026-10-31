import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { MessageResponse } from '../models/api';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class NewsletterService {
  private readonly api = inject(ApiService);

  subscribe(email: string, name?: string): Observable<MessageResponse> {
    return this.api.post<MessageResponse>('newsletter/subscribe', {
      email,
      ...(name ? { name } : {}),
    });
  }
}
