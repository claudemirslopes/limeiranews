<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ArticleWorkflowService
{
    /**
     * Policy ability required to move an article INTO the given status.
     */
    private function abilityFor(ArticleStatus $target): string
    {
        return match ($target) {
            ArticleStatus::Draft => 'update',
            ArticleStatus::InReview => 'submitReview',
            ArticleStatus::RevisionRequested, ArticleStatus::Approved => 'approve',
            ArticleStatus::Scheduled, ArticleStatus::Published => 'publish',
            ArticleStatus::Archived => 'archive',
        };
    }

    /**
     * Transition an article to a new status after validating both the allowed
     * state graph and the actor's permissions.
     *
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function transition(Article $article, ArticleStatus $target, User $actor, ?Carbon $scheduledAt = null): Article
    {
        $current = $article->status;

        if ($current === $target) {
            throw ValidationException::withMessages([
                'status' => ["O artigo já está com o status \"{$target->label()}\"."],
            ]);
        }

        if (! $current->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => ["Transição inválida: de \"{$current->label()}\" para \"{$target->label()}\"."],
            ]);
        }

        Gate::forUser($actor)->authorize($this->abilityFor($target), $article);

        $this->assertReadyFor($article, $target, $scheduledAt);

        return DB::transaction(function () use ($article, $target, $actor, $scheduledAt, $current) {
            $article->status = $target;

            match ($target) {
                ArticleStatus::Published => $this->markPublished($article, $actor),
                ArticleStatus::Scheduled => $this->markScheduled($article, $actor, $scheduledAt),
                ArticleStatus::Approved, ArticleStatus::RevisionRequested => $article->editor_id = $actor->id,
                ArticleStatus::Draft => $this->clearSchedule($article),
                default => null,
            };

            $article->save();

            $this->snapshot($article, $actor);
            AuditLog::record(
                'article.status_changed',
                $article,
                ['from' => $current->value, 'to' => $target->value],
                $actor,
            );

            return $article->refresh();
        });
    }

    /**
     * Persist a revision snapshot of the article's current content.
     */
    public function snapshot(Article $article, ?User $actor = null): ArticleRevision
    {
        return $article->revisions()->create([
            'user_id' => $actor?->id,
            'title' => $article->title,
            'subtitle' => $article->subtitle,
            'summary' => $article->summary,
            'body' => $article->body,
            'status' => $article->status->value,
            'snapshot' => $article->only([
                'slug', 'primary_category_id', 'city_id', 'region_id', 'cover_url',
                'cover_media_id', 'is_featured', 'is_breaking', 'is_sponsored',
                'meta_title', 'meta_description',
            ]),
        ]);
    }

    /**
     * Publish every scheduled article whose time has come (used by the scheduler).
     */
    public function publishDueScheduled(): int
    {
        $count = 0;

        Article::query()
            ->where('status', ArticleStatus::Scheduled->value)
            ->where('scheduled_at', '<=', now())
            ->each(function (Article $article) use (&$count) {
                $article->status = ArticleStatus::Published;
                $article->published_at = $article->scheduled_at;
                $article->save();
                $this->snapshot($article);
                AuditLog::record('article.published_by_schedule', $article);
                $count++;
            });

        return $count;
    }

    private function assertReadyFor(Article $article, ArticleStatus $target, ?Carbon $scheduledAt): void
    {
        if (! in_array($target, [ArticleStatus::Published, ArticleStatus::Scheduled, ArticleStatus::Approved, ArticleStatus::InReview], true)) {
            return;
        }

        $errors = [];

        if (trim(strip_tags((string) $article->body)) === '') {
            $errors['body'][] = 'O texto da matéria é obrigatório para avançar no fluxo editorial.';
        }

        if (blank($article->primary_category_id)) {
            $errors['primary_category_id'][] = 'Defina a categoria principal.';
        }

        if ($target === ArticleStatus::Scheduled) {
            $when = $scheduledAt ?? $article->scheduled_at;
            if ($when === null || $when->lte(now())) {
                $errors['scheduled_at'][] = 'Informe uma data de agendamento no futuro.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function markPublished(Article $article, User $actor): void
    {
        $article->editor_id ??= $actor->id;
        $article->published_at ??= now();
        $article->scheduled_at = null;
    }

    private function markScheduled(Article $article, User $actor, ?Carbon $scheduledAt): void
    {
        $article->editor_id ??= $actor->id;
        $article->scheduled_at = $scheduledAt ?? $article->scheduled_at;
    }

    private function clearSchedule(Article $article): void
    {
        $article->scheduled_at = null;
    }
}
