import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { Meta, Title } from '@angular/platform-browser';
import { ActivatedRoute } from '@angular/router';
import { map, switchMap, tap } from 'rxjs';
import { LoadState } from '../../../core/models/api';
import { StaticPage as StaticPageModel } from '../../../core/models/category';
import { PageService } from '../../../core/services/page.service';
import { toLoadState } from '../../../core/utils/load-state';
import { EmptyState } from '../../../shared/components/empty-state/empty-state';
import { LoadingState } from '../../../shared/components/loading-state/loading-state';
import { DateBrPipe } from '../../../shared/pipes/date-br.pipe';

@Component({
  selector: 'app-static-page',
  imports: [LoadingState, EmptyState, DateBrPipe],
  templateUrl: './static-page.html',
  styleUrl: './static-page.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class StaticPage {
  private readonly route = inject(ActivatedRoute);
  private readonly pages = inject(PageService);
  private readonly title = inject(Title);
  private readonly meta = inject(Meta);

  protected readonly state = toSignal(
    this.route.paramMap.pipe(
      map((params) => params.get('slug') ?? ''),
      switchMap((slug) =>
        toLoadState(this.pages.get(slug)).pipe(
          tap((s) => {
            if (s.status === 'ready') {
              this.title.setTitle(`${s.data.meta_title ?? s.data.title} — Limeira News`);
              if (s.data.meta_description) {
                this.meta.updateTag({ name: 'description', content: s.data.meta_description });
              }
            }
          }),
        ),
      ),
    ),
    { initialValue: { status: 'loading' } as LoadState<StaticPageModel> },
  );
}
