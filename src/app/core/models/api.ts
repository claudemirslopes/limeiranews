/** Envelope padrão `{ data }` dos API Resources do Laravel. */
export interface ApiItem<T> {
  data: T;
}

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
}

/** Resposta paginada do Laravel `{ data, links, meta }`. */
export interface Paginated<T> {
  data: T[];
  links: PaginationLinks;
  meta: PaginationMeta;
}

export interface MessageResponse {
  message: string;
}

export interface ValidationErrorBody {
  message?: string;
  errors?: Record<string, string[]>;
}

export type LoadState<T> =
  | { status: 'loading' }
  | { status: 'error'; message: string; httpStatus: number }
  | { status: 'ready'; data: T };
