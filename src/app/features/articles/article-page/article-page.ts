import { DOCUMENT, Location } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { Meta, Title } from '@angular/platform-browser';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { map, switchMap } from 'rxjs';
import { LoadState } from '../../../core/models/api';
import { ArticleShowResponse } from '../../../core/models/article';
import { ArticleService } from '../../../core/services/article.service';
import { toLoadState } from '../../../core/utils/load-state';
import { EmptyState } from '../../../shared/components/empty-state/empty-state';
import { LoadingState } from '../../../shared/components/loading-state/loading-state';
import { NewsCard } from '../../../shared/components/news-card/news-card';
import { DateBrPipe } from '../../../shared/pipes/date-br.pipe';

const FONT_SIZES = [15, 17, 19, 21];

@Component({
  selector: 'app-article-page',
  imports: [RouterLink, NewsCard, LoadingState, EmptyState, DateBrPipe],
  templateUrl: './article-page.html',
  styleUrl: './article-page.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ArticlePage {
  private readonly route = inject(ActivatedRoute);
  private readonly articles = inject(ArticleService);
  private readonly title = inject(Title);
  private readonly meta = inject(Meta);
  private readonly document = inject(DOCUMENT);
  private readonly location = inject(Location);

  protected readonly state = toSignal(
    this.route.paramMap.pipe(
      map((params) => params.get('slug') ?? ''),
      switchMap((slug) => toLoadState(this.articles.get(slug))),
    ),
    { initialValue: { status: 'loading' } as LoadState<ArticleShowResponse> },
  );

  protected readonly fontIndex = signal(1);
  protected readonly copied = signal(false);

  constructor() {
    effect(() => {
      const s = this.state();
      if (s.status !== 'ready') return;
      const article = s.data.data;
      this.title.setTitle(`${article.seo.meta_title ?? article.title} — Limeira News`);
      const description = article.seo.meta_description ?? article.summary ?? '';
      if (description) {
        this.meta.updateTag({ name: 'description', content: description });
      }
      this.window()?.scrollTo({ top: 0 });
    });
  }

  protected fontSize(): number {
    return FONT_SIZES[this.fontIndex()] ?? 17;
  }

  protected changeFont(delta: number): void {
    this.fontIndex.update((i) => Math.min(FONT_SIZES.length - 1, Math.max(0, i + delta)));
  }

  protected canIncrease(): boolean {
    return this.fontIndex() < FONT_SIZES.length - 1;
  }

  protected canDecrease(): boolean {
    return this.fontIndex() > 0;
  }

  protected shareUrl(): string {
    const origin = this.document.location?.origin ?? '';
    return origin + this.location.prepareExternalUrl(this.location.path());
  }

  protected whatsappLink(title: string): string {
    return `https://wa.me/?text=${encodeURIComponent(`${title} ${this.shareUrl()}`)}`;
  }

  protected twitterLink(title: string): string {
    return `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(this.shareUrl())}`;
  }

  protected async copyLink(): Promise<void> {
    try {
      await navigator.clipboard.writeText(this.shareUrl());
      this.copied.set(true);
      setTimeout(() => this.copied.set(false), 2200);
    } catch {
      this.window()?.prompt('Copie o link da matéria:', this.shareUrl());
    }
  }

  protected back(): void {
    this.location.back();
  }

  private window(): Window | null {
    return this.document.defaultView;
  }
}
