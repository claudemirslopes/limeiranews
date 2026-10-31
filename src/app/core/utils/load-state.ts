import { HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, of, startWith } from 'rxjs';
import { LoadState, ValidationErrorBody } from '../models/api';

export interface ParsedApiError {
  status: number;
  message: string;
  fields: Record<string, string[]>;
}

/** Normaliza erros HTTP do Laravel (inclui 422 de validação) em mensagens em português. */
export function parseApiError(err: unknown): ParsedApiError {
  if (err instanceof HttpErrorResponse) {
    const body = (err.error ?? {}) as ValidationErrorBody;
    const fields = body.errors ?? {};
    const firstField = Object.values(fields)[0]?.[0];

    if (err.status === 0) {
      return {
        status: 0,
        message: 'Não foi possível conectar ao servidor. Tente novamente em instantes.',
        fields,
      };
    }
    if (err.status === 404) {
      return { status: 404, message: 'Conteúdo não encontrado.', fields };
    }
    return {
      status: err.status,
      message: firstField ?? body.message ?? 'Ocorreu um erro inesperado.',
      fields,
    };
  }
  return { status: -1, message: 'Ocorreu um erro inesperado.', fields: {} };
}

/** Converte um Observable em `LoadState` (loading → ready | error). */
export function toLoadState<T>(source: Observable<T>): Observable<LoadState<T>> {
  return source.pipe(
    map((data): LoadState<T> => ({ status: 'ready', data })),
    startWith<LoadState<T>>({ status: 'loading' }),
    catchError((err: unknown) => {
      const parsed = parseApiError(err);
      return of<LoadState<T>>({
        status: 'error',
        message: parsed.message,
        httpStatus: parsed.status,
      });
    }),
  );
}
