<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::InReview => 'Em revisão',
            self::RevisionRequested => 'Revisão solicitada',
            self::Approved => 'Aprovada',
            self::Scheduled => 'Agendada',
            self::Published => 'Publicada',
            self::Archived => 'Arquivada',
        };
    }

    /**
     * Statuses reachable from the current one.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::InReview, self::Approved, self::Scheduled, self::Published, self::Archived],
            self::InReview => [self::Draft, self::RevisionRequested, self::Approved, self::Archived],
            self::RevisionRequested => [self::Draft, self::InReview, self::Archived],
            self::Approved => [self::Draft, self::Scheduled, self::Published, self::Archived],
            self::Scheduled => [self::Draft, self::Published, self::Archived],
            self::Published => [self::Draft, self::Archived],
            self::Archived => [self::Draft],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
