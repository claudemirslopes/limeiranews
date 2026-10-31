import { ArticleListItem } from './article';

export interface UserRole {
  id: number;
  name: string;
  display_name: string;
}

export interface User {
  id: number;
  name: string;
  public_name: string;
  email: string;
  slug: string | null;
  bio: string | null;
  specialty: string | null;
  avatar_url: string | null;
  is_active: boolean;
  last_login_at: string | null;
  role?: UserRole;
  permissions?: string[];
  can_publish: boolean;
}

/** POST /auth/login → resposta SEM envelope `data`. */
export interface LoginResponse {
  token: string;
  token_type: string;
  user: User;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface DashboardData {
  articles: {
    total: number;
    by_status: Record<string, number>;
    published_today: number;
    published_this_month: number;
    awaiting_review: number;
    scheduled: number;
  };
  categories: number;
  media: number;
  newsletter: { confirmed: number; pending: number } | null;
  users: number | null;
  active_campaigns: number | null;
  recent_articles: ArticleListItem[];
}
