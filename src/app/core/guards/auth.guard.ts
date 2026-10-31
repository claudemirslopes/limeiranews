import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { map } from 'rxjs';
import { AuthService } from '../services/auth.service';

/** Bloqueia /admin sem token válido e redireciona para /admin/login. */
export const authGuard: CanActivateFn = (_route, state) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return auth.ensureUser().pipe(
    map((ok) =>
      ok ? true : router.createUrlTree(['/admin/login'], { queryParams: { returnUrl: state.url } }),
    ),
  );
};
