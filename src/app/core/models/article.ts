export interface NamedRef {
  id: number;
  name: string;
  slug: string;
}

export interface ArticleAuthorRef {
  id: number;
  name: string;
  slug: string | null;
}

export type ArticleStatusValue =
  | 'draft'
  | 'in_review'
  | 'revision_requested'
  | 'approved'
  | 'scheduled'
  | 'published'
  | 'archived';

/** Formato de lista (ArticleListResource). Campos de admin só vêm em /admin/*. */
export interface ArticleListItem {
  id: number;
  title: string;
  slug: string;
  subtitle: string | null;
  summary: string | null;
  cover_url: string | null;
  image_credit: string | null;
  published_at: string | null;
  is_featured: boolean;
  is_breaking: boolean;
  is_sponsored: boolean;
  category?: NamedRef | null;
  city?: NamedRef | null;
  region?: NamedRef | null;
  author?: ArticleAuthorRef;
  // admin
  status?: ArticleStatusValue;
  status_label?: string;
  scheduled_at?: string | null;
  author_id?: number;
  editor_id?: number | null;
  view_count?: number;
  updated_at?: string | null;
  created_at?: string | null;
}

export interface ArticleAuthor extends ArticleAuthorRef {
  bio?: string | null;
  specialty?: string | null;
}

export interface ArticleSeo {
  meta_title: string | null;
  meta_description: string | null;
  canonical_url: string | null;
  og_image: string | null;
  allow_index: boolean;
}

/** Formato completo (ArticleResource). */
export interface Article extends Omit<ArticleListItem, 'author'> {
  body: string;
  source_name: string | null;
  categories?: NamedRef[];
  tags?: NamedRef[];
  author?: ArticleAuthor;
  seo: ArticleSeo;
  cover_media_id?: number | null;
}

/** GET /articles/{slug} → `{ data, related }`. */
export interface ArticleShowResponse {
  data: Article;
  related?: ArticleListItem[];
}

export interface ArticleListParams {
  category?: string;
  city?: string;
  region?: string;
  tag?: string;
  featured?: boolean;
  page?: number;
  per_page?: number;
}

/** Payload aceito por POST/PUT /admin/articles. */
export interface ArticlePayload {
  title: string;
  subtitle?: string | null;
  summary?: string | null;
  body: string;
  primary_category_id: number;
  cover_url?: string | null;
  image_credit?: string | null;
  source_name?: string | null;
  meta_title?: string | null;
  meta_description?: string | null;
}

export interface AdminArticleListParams {
  status?: ArticleStatusValue | '';
  q?: string;
  page?: number;
  per_page?: number;
}

export const ARTICLE_STATUS_LABELS: Record<ArticleStatusValue, string> = {
  draft: 'Rascunho',
  in_review: 'Em revisão',
  revision_requested: 'Revisão solicitada',
  approved: 'Aprovada',
  scheduled: 'Agendada',
  published: 'Publicada',
  archived: 'Arquivada',
};
