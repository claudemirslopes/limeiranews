import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ArticleListItem } from '../../../core/models/article';
import { RelativeTimePipe } from '../../pipes/relative-time.pipe';

@Component({
  selector: 'app-news-card',
  imports: [RouterLink, RelativeTimePipe],
  templateUrl: './news-card.html',
  styleUrl: './news-card.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NewsCard {
  readonly article = input.required<ArticleListItem>();

  protected readonly location = computed(() => {
    const a = this.article();
    return a.city?.name ?? a.region?.name ?? a.category?.name ?? '';
  });
}
