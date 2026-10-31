import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { Title } from '@angular/platform-browser';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { combineLatest, map, switchMap, tap } from 'rxjs';
import { LoadState } from '../../../core/models/api';
import { CategoryShowResponse } from '../../../core/models/category';
import { CategoryService } from '../../../core/services/category.service';
import { toLoadState } from '../../../core/utils/load-state';
import { EmptyState } from '../../../shared/components/empty-state/empty-state';
import { LoadingState } from '../../../shared/components/loading-state/loading-state';
import { NewsCard } from '../../../shared/components/news-card/news-card';
import { Pagination } from '../../../shared/components/pagination/pagination';

@Component({
  selector: 'app-category-page',
  imports: [RouterLink, NewsCard, LoadingState, EmptyState, Pagination],
  templateUrl: './category-page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CategoryPage {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly categories = inject(CategoryService);
  private readonly title = inject(Title);

  protected readonly state = toSignal(
    combineLatest([this.route.paramMap, this.route.queryParamMap]).pipe(
      map(([params, query]) => ({
        slug: params.get('slug') ?? '',
        page: Math.max(1, Number(query.get('page')) || 1),
      })),
      switchMap(({ slug, page }) =>
        toLoadState(this.categories.get(slug, page)).pipe(
          tap((s) => {
            if (s.status === 'ready') {
              this.title.setTitle(`${s.data.data.meta_title ?? s.data.data.name} — Limeira News`);
            }
          }),
        ),
      ),
    ),
    { initialValue: { status: 'loading' } as LoadState<CategoryShowResponse> },
  );

  protected goToPage(page: number): void {
    void this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { page: page > 1 ? page : null },
      queryParamsHandling: 'merge',
    });
    document.defaultView?.scrollTo({ top: 0, behavior: 'smooth' });
  }
}
