import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-empty-state',
  imports: [RouterLink],
  template: `
    <div class="empty" [class.is-error]="tone() === 'error'">
      <span class="mark" aria-hidden="true">LN</span>
      <h3>{{ title() }}</h3>
      @if (message()) {
        <p>{{ message() }}</p>
      }
      @if (linkLabel()) {
        <a class="back" [routerLink]="linkTo()">{{ linkLabel() }} <span>→</span></a>
      }
      <ng-content />
    </div>
  `,
  styles: `
    .empty {
      border: 1px dashed #cfd8e6;
      border-radius: 7px;
      background: var(--paper);
      padding: 44px 20px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
    }
    .mark {
      width: 44px;
      height: 44px;
      border-radius: 6px;
      background: var(--blue);
      color: #fff;
      display: grid;
      place-items: center;
      font-weight: 900;
      font-size: 14px;
    }
    .is-error .mark {
      background: #d62f3b;
    }
    h3 {
      margin: 6px 0 0;
      font-size: 18px;
      letter-spacing: -0.03em;
    }
    p {
      margin: 0;
      max-width: 420px;
      font-size: 12px;
      line-height: 1.6;
      color: var(--muted);
    }
    .back {
      margin-top: 10px;
      color: var(--blue);
      font-size: 11px;
      font-weight: 800;
    }
    .back span {
      color: var(--green);
      margin-left: 6px;
    }
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class EmptyState {
  readonly title = input('Nada por aqui ainda');
  readonly message = input('');
  readonly tone = input<'neutral' | 'error'>('neutral');
  readonly linkLabel = input('');
  readonly linkTo = input<string | string[]>('/');
}
