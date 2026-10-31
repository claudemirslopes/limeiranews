import { ArticleListItem } from './article';

export type HomepageSectionType =
  | 'hero'
  | 'latest'
  | 'category'
  | 'region'
  | 'featured'
  | 'most_read'
  | (string & {});

export interface HomepageSection {
  id: number;
  key: string;
  title: string;
  section_type: HomepageSectionType;
  category_id: number | null;
  region_id: number | null;
  category?: { name: string; slug: string } | null;
  region?: { name: string; slug: string } | null;
  limit: number;
  sort_order: number;
  is_visible: boolean;
  config: unknown;
  articles?: ArticleListItem[];
}
