import { ChangeDetectionStrategy, Component, effect, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { Title } from '@angular/platform-browser';
import { ActivatedRoute, Router } from '@angular/router';
import { map, of, switchMap } from 'rxjs';
import { LoadState, Paginated } from '../../../core/models/api';
import { ArticleListItem } from '../../../core/models/article';
import { SearchService } from '../../../core/services/search.service';
import { toLoadState } from '../../../core/utils/load-state';
import { EmptyState } from '../../../shared/components/empty-state/empty-state';
import { LoadingState } from '../../../shared/components/loading-state/loading-state';
import { NewsCard } from '../../../shared/components/news-card/news-card';
import { Pagination } from '../../../shared/components/pagination/pagination';

interface SearchQuery {
  q: string;
  page: number;
}

@Component({
  selector: 'app-search-page',
  imports: [ReactiveFormsModule, NewsCard, LoadingState, EmptyState, Pagination],
  templateUrl: './search-page.html',
  styleUrl: './search-page.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SearchPage {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly search = inject(SearchService);
  private readonly title = inject(Title);

  protected readonly input = new FormControl('', { nonNullable: true });

  protected readonly query = toSignal(
    this.route.queryParamMap.pipe(
      map(
        (params): SearchQuery => ({
          q: (params.get('q') ?? '').trim(),
          page: Math.max(1, Number(params.get('page')) || 1),
        }),
      ),
    ),
    { initialValue: { q: '', page: 1 } as SearchQuery },
  );

  protected readonly state = toSignal(
    this.route.queryParamMap.pipe(
      map((params) => ({
        q: (params.get('q') ?? '').trim(),
        page: Math.max(1, Number(params.get('page')) || 1),
      })),
      switchMap(({ q, page }) =>
        q.length < 2
          ? of<LoadState<Paginated<ArticleListItem>> | null>(null)
          : toLoadState(this.search.search(q, page)),
      ),
    ),
    { initialValue: null as LoadState<Paginated<ArticleListItem>> | null },
  );

  constructor() {
    effect(() => {
      const { q } = this.query();
      this.input.setValue(q, { emitEvent: false });
      this.title.setTitle(q ? `Busca: ${q} — Limeira News` : 'Busca — Limeira News');
    });
  }

  protected submit(): void {
    const q = this.input.value.trim();
    void this.router.navigate(['/busca'], { queryParams: { q: q || null } });
  }

  protected goToPage(page: number): void {
    void this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { page: page > 1 ? page : null },
      queryParamsHandling: 'merge',
    });
    document.defaultView?.scrollTo({ top: 0, behavior: 'smooth' });
  }
}
