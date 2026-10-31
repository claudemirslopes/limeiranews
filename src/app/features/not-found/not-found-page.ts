import { ChangeDetectionStrategy, Component } from '@angular/core';
import { EmptyState } from '../../shared/components/empty-state/empty-state';

@Component({
  selector: 'app-not-found-page',
  imports: [EmptyState],
  template: `
    <div class="container page-block" style="padding-top: 48px">
      <app-empty-state
        title="Página não encontrada"
        message="O endereço digitado não existe. Volte para a página inicial e continue navegando."
        linkLabel="Voltar para a página inicial"
        linkTo="/"
      />
    </div>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NotFoundPage {}
