import { ChangeDetectionStrategy, Component, computed, input, output } from '@angular/core';
import { PaginationMeta } from '../../../core/models/api';

@Component({
  selector: 'app-pagination',
  template: `
    @if (meta().last_page > 1) {
      <nav class="pagination" aria-label="Paginação">
        <button
          type="button"
          [disabled]="meta().current_page <= 1"
          (click)="pageChange.emit(meta().current_page - 1)"
        >
          ← Anterior
        </button>
        <span class="status">Página {{ meta().current_page }} de {{ meta().last_page }}</span>
        <button
          type="button"
          [disabled]="meta().current_page >= meta().last_page"
          (click)="pageChange.emit(meta().current_page + 1)"
        >
          Próxima →
        </button>
      </nav>
    }
  `,
  styles: `
    .pagination {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 16px;
      margin-top: 32px;
    }
    button {
      border: 1px solid var(--line);
      background: #fff;
      color: var(--blue);
      border-radius: 4px;
      padding: 10px 14px;
      font-size: 11px;
      font-weight: 800;
      cursor: pointer;
    }
    button:hover:not(:disabled) {
      border-color: var(--green);
    }
    button:disabled {
      color: #a6b0bf;
      cursor: not-allowed;
    }
    .status {
      font-size: 11px;
      color: var(--muted);
    }
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Pagination {
  readonly meta = input.required<PaginationMeta>();
  readonly pageChange = output<number>();
  protected readonly hasPages = computed(() => this.meta().last_page > 1);
}
