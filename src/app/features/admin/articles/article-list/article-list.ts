import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { switchMap } from 'rxjs';
import { LoadState, Paginated } from '../../../../core/models/api';
import {
  ARTICLE_STATUS_LABELS,
  ArticleListItem,
  ArticleStatusValue,
} from '../../../../core/models/article';
import { AdminArticleService } from '../../../../core/services/admin-article.service';
import { parseApiError, toLoadState } from '../../../../core/utils/load-state';
import { EmptyState } from '../../../../shared/components/empty-state/empty-state';
import { LoadingState } from '../../../../shared/components/loading-state/loading-state';
import { Pagination } from '../../../../shared/components/pagination/pagination';
import { DateBrPipe } from '../../../../shared/pipes/date-br.pipe';

interface ListQuery {
  status: ArticleStatusValue | '';
  q: string;
  page: number;
  nonce: number;
}

@Component({
  selector: 'app-article-list',
  imports: [RouterLink, ReactiveFormsModule, LoadingState, EmptyState, Pagination, DateBrPipe],
  templateUrl: './article-list.html',
  styleUrl: './article-list.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ArticleList {
  private readonly articles = inject(AdminArticleService);

  protected readonly statusOptions = Object.entries(ARTICLE_STATUS_LABELS).map(
    ([value, label]) => ({ value: value as ArticleStatusValue, label }),
  );

  protected readonly search = new FormControl('', { nonNullable: true });
  protected readonly statusFilter = new FormControl<ArticleStatusValue | ''>('', {
    nonNullable: true,
  });

  private readonly query = signal<ListQuery>({ status: '', q: '', page: 1, nonce: 0 });

  protected readonly state = toSignal(
    toObservable(this.query).pipe(
      switchMap((q) =>
        toLoadState(this.articles.list({ status: q.status, q: q.q, page: q.page, per_page: 15 })),
      ),
    ),
    { initialValue: { status: 'loading' } as LoadState<Paginated<ArticleListItem>> },
  );

  protected readonly actionError = signal<string | null>(null);

  protected applyFilters(): void {
    this.query.update((q) => ({
      ...q,
      status: this.statusFilter.value,
      q: this.search.value.trim(),
      page: 1,
    }));
  }

  protected goToPage(page: number): void {
    this.query.update((q) => ({ ...q, page }));
  }

  protected remove(article: ArticleListItem): void {
    if (!confirm(`Mover "${article.title}" para a lixeira?`)) return;
    this.actionError.set(null);
    this.articles.remove(article.id).subscribe({
      next: () => this.query.update((q) => ({ ...q, nonce: q.nonce + 1 })),
      error: (err: unknown) => this.actionError.set(parseApiError(err).message),
    });
  }
}
