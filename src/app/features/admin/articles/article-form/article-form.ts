import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Observable, of, switchMap } from 'rxjs';
import { Article, ArticlePayload, ArticleStatusValue } from '../../../../core/models/article';
import { AdminArticleService } from '../../../../core/services/admin-article.service';
import { AuthService } from '../../../../core/services/auth.service';
import { CategoryService } from '../../../../core/services/category.service';
import { parseApiError } from '../../../../core/utils/load-state';
import { LoadingState } from '../../../../shared/components/loading-state/loading-state';
import { EmptyState } from '../../../../shared/components/empty-state/empty-state';

type Transition = 'submit-review' | 'publish';

@Component({
  selector: 'app-article-form',
  imports: [ReactiveFormsModule, RouterLink, LoadingState, EmptyState],
  templateUrl: './article-form.html',
  styleUrl: './article-form.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ArticleForm {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly articles = inject(AdminArticleService);
  private readonly categoryService = inject(CategoryService);
  protected readonly auth = inject(AuthService);

  protected readonly articleId: number | null = this.parseId(this.route.snapshot.paramMap.get('id'));
  protected readonly isEdit = this.articleId !== null;

  protected readonly categories = toSignal(this.categoryService.listFlat(), { initialValue: [] });

  protected readonly form = new FormGroup({
    title: new FormControl('', {
      nonNullable: true,
      validators: [Validators.required, Validators.maxLength(255)],
    }),
    subtitle: new FormControl('', { nonNullable: true, validators: [Validators.maxLength(255)] }),
    summary: new FormControl('', { nonNullable: true, validators: [Validators.maxLength(1000)] }),
    body: new FormControl('', { nonNullable: true, validators: [Validators.required] }),
    primary_category_id: new FormControl<number | null>(null, [Validators.required]),
    cover_url: new FormControl('', { nonNullable: true }),
    image_credit: new FormControl('', { nonNullable: true }),
    source_name: new FormControl('', { nonNullable: true }),
    meta_title: new FormControl('', { nonNullable: true, validators: [Validators.maxLength(255)] }),
    meta_description: new FormControl('', {
      nonNullable: true,
      validators: [Validators.maxLength(320)],
    }),
  });

  protected readonly article = signal<Article | null>(null);
  protected readonly loading = signal(this.isEdit);
  protected readonly loadError = signal<string | null>(null);
  protected readonly busy = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly success = signal<string | null>(
    (history.state as { saved?: boolean } | null)?.saved ? 'Notícia criada como rascunho.' : null,
  );
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  protected readonly status = computed<ArticleStatusValue | null>(
    () => this.article()?.status ?? null,
  );
  protected readonly canSubmitReview = computed(() => {
    const status = this.status();
    return status === 'draft' || status === 'revision_requested';
  });
  protected readonly canPublish = computed(() => {
    const status = this.status();
    return (
      this.auth.canPublish() &&
      (status === 'draft' || status === 'approved' || status === 'scheduled')
    );
  });

  constructor() {
    if (this.articleId !== null) {
      this.articles.get(this.articleId).subscribe({
        next: (article) => {
          this.applyArticle(article);
          this.loading.set(false);
        },
        error: (err: unknown) => {
          this.loadError.set(parseApiError(err).message);
          this.loading.set(false);
        },
      });
    }
  }

  protected fieldError(name: string): string | null {
    const server = this.serverErrors()[name]?.[0];
    if (server) return server;
    const control = this.form.get(name);
    if (!control || !control.touched || !control.errors) return null;
    if (control.errors['required']) return 'Campo obrigatório.';
    if (control.errors['maxlength']) return 'Texto acima do limite permitido.';
    return null;
  }

  protected save(): void {
    if (!this.validate()) return;
    this.begin();
    this.persist().subscribe({
      next: (article) => {
        this.busy.set(false);
        if (!this.isEdit) {
          void this.router.navigate(['/admin/noticias', article.id, 'editar'], {
            state: { saved: true },
          });
          return;
        }
        this.applyArticle(article);
        this.success.set('Alterações salvas.');
      },
      error: (err: unknown) => this.fail(err),
    });
  }

  protected transition(kind: Transition): void {
    if (this.articleId === null || !this.validate()) return;
    this.begin();
    const id = this.articleId;

    const save$: Observable<Article | null> = this.form.dirty ? this.persist() : of(null);
    save$
      .pipe(
        switchMap(() =>
          kind === 'publish' ? this.articles.publish(id) : this.articles.submitReview(id),
        ),
      )
      .subscribe({
        next: (article) => {
          this.busy.set(false);
          this.applyArticle(article);
          this.success.set(
            kind === 'publish' ? 'Notícia publicada com sucesso.' : 'Notícia enviada para revisão.',
          );
        },
        error: (err: unknown) => this.fail(err),
      });
  }

  private persist(): Observable<Article> {
    const payload = this.buildPayload();
    return this.articleId !== null
      ? this.articles.update(this.articleId, payload)
      : this.articles.create(payload);
  }

  private buildPayload(): ArticlePayload {
    const v = this.form.getRawValue();
    const orNull = (value: string): string | null => (value.trim() === '' ? null : value.trim());
    return {
      title: v.title.trim(),
      subtitle: orNull(v.subtitle),
      summary: orNull(v.summary),
      body: v.body,
      primary_category_id: Number(v.primary_category_id),
      cover_url: orNull(v.cover_url),
      image_credit: orNull(v.image_credit),
      source_name: orNull(v.source_name),
      meta_title: orNull(v.meta_title),
      meta_description: orNull(v.meta_description),
    };
  }

  private applyArticle(article: Article): void {
    this.article.set(article);
    this.form.reset(
      {
        title: article.title,
        subtitle: article.subtitle ?? '',
        summary: article.summary ?? '',
        body: article.body ?? '',
        primary_category_id: article.category?.id ?? null,
        cover_url: article.cover_url ?? '',
        image_credit: article.image_credit ?? '',
        source_name: article.source_name ?? '',
        // A API devolve título/resumo como fallback de SEO; só preenche se for diferente.
        meta_title: article.seo?.meta_title === article.title ? '' : (article.seo?.meta_title ?? ''),
        meta_description:
          article.seo?.meta_description === article.summary
            ? ''
            : (article.seo?.meta_description ?? ''),
      },
      { emitEvent: false },
    );
  }

  private validate(): boolean {
    this.error.set(null);
    this.success.set(null);
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.error.set('Revise os campos destacados antes de continuar.');
      return false;
    }
    return true;
  }

  private begin(): void {
    this.busy.set(true);
    this.error.set(null);
    this.success.set(null);
    this.serverErrors.set({});
  }

  private fail(err: unknown): void {
    const parsed = parseApiError(err);
    this.busy.set(false);
    this.serverErrors.set(parsed.fields);
    this.error.set(
      parsed.status === 403
        ? 'Você não tem permissão para realizar esta ação.'
        : parsed.message,
    );
  }

  private parseId(raw: string | null): number | null {
    const id = raw === null ? NaN : Number(raw);
    return Number.isInteger(id) && id > 0 ? id : null;
  }
}
