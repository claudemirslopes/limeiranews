import { Injectable, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, catchError, finalize, map, of, tap } from 'rxjs';
import { ApiItem } from '../models/api';
import { LoginPayload, LoginResponse, User } from '../models/user';
import { ApiService } from './api.service';

export const TOKEN_KEY = 'ln_token';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);

  private readonly tokenSignal = signal<string | null>(this.readToken());
  private readonly userSignal = signal<User | null>(null);

  readonly token = this.tokenSignal.asReadonly();
  readonly user = this.userSignal.asReadonly();
  readonly isAuthenticated = computed(() => this.tokenSignal() !== null);
  readonly canPublish = computed(() => this.userSignal()?.can_publish === true);

  /** Login: o endpoint devolve `{ token, token_type, user }` (sem envelope `data`). */
  login(payload: LoginPayload): Observable<User> {
    return this.api.post<LoginResponse>('auth/login', payload).pipe(
      tap((res) => {
        this.writeToken(res.token);
        this.tokenSignal.set(res.token);
        this.userSignal.set(res.user);
      }),
      map((res) => res.user),
    );
  }

  /** Carrega o usuário atual (GET /auth/me → `{ data: user }`). */
  loadUser(): Observable<User> {
    return this.api.get<ApiItem<User>>('auth/me').pipe(
      map((res) => res.data),
      tap((user) => this.userSignal.set(user)),
    );
  }

  /** Garante que o usuário esteja carregado; devolve false se o token for inválido. */
  ensureUser(): Observable<boolean> {
    if (!this.tokenSignal()) return of(false);
    if (this.userSignal()) return of(true);
    return this.loadUser().pipe(
      map(() => true),
      catchError(() => {
        this.clear();
        return of(false);
      }),
    );
  }

  logout(): void {
    const redirect = () => {
      this.clear();
      void this.router.navigate(['/admin/login']);
    };
    if (!this.tokenSignal()) {
      redirect();
      return;
    }
    this.api
      .post('auth/logout')
      .pipe(
        catchError(() => of(null)),
        finalize(redirect),
      )
      .subscribe();
  }

  /** Limpa a sessão local (token + usuário). */
  clear(): void {
    this.writeToken(null);
    this.tokenSignal.set(null);
    this.userSignal.set(null);
  }

  private readToken(): string | null {
    try {
      return localStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  }

  private writeToken(token: string | null): void {
    try {
      if (token) localStorage.setItem(TOKEN_KEY, token);
      else localStorage.removeItem(TOKEN_KEY);
    } catch {
      /* armazenamento indisponível */
    }
  }
}
