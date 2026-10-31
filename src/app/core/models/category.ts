import { Paginated } from './api';
import { ArticleListItem } from './article';

export interface Category {
  id: number;
  parent_id: number | null;
  name: string;
  slug: string;
  description: string | null;
  image_url: string | null;
  meta_title: string | null;
  meta_description: string | null;
  type: string;
  sort_order: number;
  is_active: boolean;
  children?: Category[];
}

/** GET /categories/{slug} → `{ data: category, articles: paginado }`. */
export interface CategoryShowResponse {
  data: Category;
  articles: Paginated<ArticleListItem>;
}

export interface MenuChild {
  id: number;
  label: string;
  url: string;
  target: string | null;
}

export interface MenuItem {
  id?: number;
  label: string;
  url: string;
  target?: string | null;
  category_slug: string | null;
  children: MenuChild[];
}

export interface Menu {
  id: number;
  name: string;
  location: string;
  items: MenuItem[];
}

export interface BreakingNews {
  id: number;
  headline: string;
  position: string;
  article_slug: string | null;
  starts_at: string | null;
  ends_at: string | null;
}

export interface StaticPage {
  id: number;
  title: string;
  slug: string;
  body: string;
  meta_title: string | null;
  meta_description: string | null;
  published_at: string | null;
  updated_at: string | null;
}
