import { ChangeDetectionStrategy, Component, computed, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { LoadState } from '../../../core/models/api';
import { ARTICLE_STATUS_LABELS, ArticleStatusValue } from '../../../core/models/article';
import { DashboardData } from '../../../core/models/user';
import { AdminDashboardService } from '../../../core/services/admin-dashboard.service';
import { toLoadState } from '../../../core/utils/load-state';
import { EmptyState } from '../../../shared/components/empty-state/empty-state';
import { LoadingState } from '../../../shared/components/loading-state/loading-state';
import { DateBrPipe } from '../../../shared/pipes/date-br.pipe';

interface StatCard {
  label: string;
  value: number;
  hint?: string;
}

@Component({
  selector: 'app-dashboard-page',
  imports: [RouterLink, LoadingState, EmptyState, DateBrPipe],
  templateUrl: './dashboard-page.html',
  styleUrl: './dashboard-page.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardPage {
  private readonly dashboard = inject(AdminDashboardService);

  protected readonly state = toSignal(toLoadState(this.dashboard.get()), {
    initialValue: { status: 'loading' } as LoadState<DashboardData>,
  });

  protected readonly cards = computed<StatCard[]>(() => {
    const s = this.state();
    if (s.status !== 'ready') return [];
    const d = s.data;
    const cards: StatCard[] = [
      { label: 'Total de matérias', value: d.articles.total },
      { label: 'Publicadas hoje', value: d.articles.published_today },
      { label: 'Publicadas no mês', value: d.articles.published_this_month },
      { label: 'Aguardando revisão', value: d.articles.awaiting_review },
      { label: 'Agendadas', value: d.articles.scheduled },
      { label: 'Categorias', value: d.categories },
      { label: 'Arquivos de mídia', value: d.media },
    ];
    if (d.newsletter) {
      cards.push(
        { label: 'Assinantes confirmados', value: d.newsletter.confirmed },
        { label: 'Assinantes pendentes', value: d.newsletter.pending },
      );
    }
    if (d.users !== null) cards.push({ label: 'Usuários ativos', value: d.users });
    if (d.active_campaigns !== null) {
      cards.push({ label: 'Campanhas ativas', value: d.active_campaigns });
    }
    return cards;
  });

  protected readonly statuses = computed(() => {
    const s = this.state();
    if (s.status !== 'ready') return [];
    return Object.entries(s.data.articles.by_status).map(([key, total]) => ({
      key,
      total,
      label: ARTICLE_STATUS_LABELS[key as ArticleStatusValue] ?? key,
    }));
  });
}
