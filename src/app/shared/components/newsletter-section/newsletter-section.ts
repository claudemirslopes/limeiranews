import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { NewsletterService } from '../../../core/services/newsletter.service';
import { parseApiError } from '../../../core/utils/load-state';

@Component({
  selector: 'app-newsletter-section',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './newsletter-section.html',
  styleUrl: './newsletter-section.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NewsletterSection {
  private readonly newsletter = inject(NewsletterService);

  protected readonly form = new FormGroup({
    email: new FormControl('', {
      nonNullable: true,
      validators: [Validators.required, Validators.email],
    }),
  });

  protected readonly sending = signal(false);
  protected readonly feedback = signal<{ ok: boolean; text: string } | null>(null);

  protected submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.feedback.set({ ok: false, text: 'Informe um e-mail válido.' });
      return;
    }

    this.sending.set(true);
    this.feedback.set(null);

    this.newsletter.subscribe(this.form.controls.email.value.trim()).subscribe({
      next: (res) => {
        this.sending.set(false);
        this.feedback.set({ ok: true, text: res.message });
        this.form.reset();
      },
      error: (err: unknown) => {
        this.sending.set(false);
        this.feedback.set({ ok: false, text: parseApiError(err).message });
      },
    });
  }
}
