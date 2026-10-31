import { DOCUMENT } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { LoadState } from '../../../core/models/api';
import { ArticleListItem } from '../../../core/models/article';
import { HomepageSection } from '../../../core/models/homepage';
import { HomepageService } from '../../../core/services/homepage.service';
import { toLoadState } from '../../../core/utils/load-state';
import { EmptyState } from '../../../shared/components/empty-state/empty-state';
import { LoadingState } from '../../../shared/components/loading-state/loading-state';
import { NewsCard } from '../../../shared/components/news-card/news-card';
import { RelativeTimePipe } from '../../../shared/pipes/relative-time.pipe';

interface HomeView {
  hero: HomepageSection | null;
  lead: ArticleListItem | null;
  quick: ArticleListItem[];
  latest: HomepageSection | null;
  blocks: HomepageSection[];
  mostRead: HomepageSection | null;
  hasContent: boolean;
}

@Component({
  selector: 'app-home-page',
  imports: [RouterLink, NewsCard, LoadingState, EmptyState, RelativeTimePipe],
  templateUrl: './home-page.html',
  styleUrl: './home-page.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class HomePage {
  private readonly homepage = inject(HomepageService);
  private readonly document = inject(DOCUMENT);

  protected readonly state = toSignal(toLoadState(this.homepage.sections()), {
    initialValue: { status: 'loading' } as LoadState<HomepageSection[]>,
  });

  protected readonly view = computed<HomeView | null>(() => {
    const state = this.state();
    if (state.status !== 'ready') return null;

    const sections = state.data
      .map((section) => ({ ...section, articles: section.articles ?? [] }))
      .sort((a, b) => a.sort_order - b.sort_order);

    const hero = sections.find((s) => s.section_type === 'hero') ?? null;
    const latest = sections.find((s) => s.section_type === 'latest') ?? null;
    const mostRead = sections.find((s) => s.section_type === 'most_read') ?? null;
    const blocks = sections.filter(
      (s) => !['hero', 'latest', 'most_read'].includes(s.section_type) && s.articles.length > 0,
    );

    const heroArticles = hero?.articles ?? [];
    const lead = heroArticles[0] ?? latest?.articles?.[0] ?? null;
    let quick = heroArticles.slice(1, 5);
    if (quick.length === 0 && latest) {
      quick = latest.articles?.filter((a) => a.id !== lead?.id).slice(0, 4) ?? [];
    }

    return {
      hero,
      lead,
      quick,
      latest,
      blocks,
      mostRead,
      hasContent: sections.some((s) => s.articles.length > 0),
    };
  });

  protected categoryLink(section: HomepageSection): string[] {
    const slug = section.category?.slug ?? section.region?.slug ?? section.key;
    return ['/categoria', slug];
  }

  protected location(article: ArticleListItem): string {
    return article.city?.name ?? article.region?.name ?? '';
  }

  protected scrollTo(id: string): void {
    this.document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  protected indexLabel(index: number): string {
    return String(index + 1).padStart(2, '0');
  }
}
