<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    /**
     * Administrators can do everything; inactive users can do nothing.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isEditor() || $user->isWriter();
    }

    public function view(User $user, Article $article): bool
    {
        return $user->isEditor() || ($user->isWriter() && $this->owns($user, $article));
    }

    public function create(User $user): bool
    {
        return $user->isEditor() || $user->isWriter();
    }

    public function update(User $user, Article $article): bool
    {
        if ($user->isEditor()) {
            return true;
        }

        return $user->isWriter()
            && $this->owns($user, $article)
            && $this->isEditableByWriter($article);
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->hasRole('editor_chefe');
    }

    public function duplicate(User $user, Article $article): bool
    {
        return $this->create($user) && $this->view($user, $article);
    }

    /** Send to editorial review. */
    public function submitReview(User $user, Article $article): bool
    {
        return $this->update($user, $article)
            && in_array($article->status, [ArticleStatus::Draft, ArticleStatus::RevisionRequested], true);
    }

    /** Approve or request revisions. */
    public function approve(User $user, Article $article): bool
    {
        return $user->isEditor();
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->isEditor();
    }

    public function archive(User $user, Article $article): bool
    {
        return $user->isEditor();
    }

    private function owns(User $user, Article $article): bool
    {
        return $article->author_id === $user->id;
    }

    private function isEditableByWriter(Article $article): bool
    {
        return in_array($article->status, [ArticleStatus::Draft, ArticleStatus::RevisionRequested], true);
    }
}
