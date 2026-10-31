import { ChangeDetectionStrategy, Component, input } from '@angular/core';

@Component({
  selector: 'app-loading-state',
  template: `
    <div class="loading" role="status" aria-live="polite">
      <span class="spinner" aria-hidden="true"></span>
      <span>{{ label() }}</span>
    </div>
  `,
  styles: `
    .loading {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      padding: 56px 16px;
      color: var(--muted);
      font-size: 13px;
      font-weight: 600;
    }
    .spinner {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 3px solid var(--line);
      border-top-color: var(--green);
      animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LoadingState {
  readonly label = input('Carregando…');
}
