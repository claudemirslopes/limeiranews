<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCampaign extends Model
{
    protected $fillable = [
        'advertiser_id', 'name', 'image_path', 'target_url', 'position',
        'starts_at', 'ends_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'impressions' => 'integer',
            'clicks' => 'integer',
        ];
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }

    /** Active flag set, advertiser active and inside the schedule window. */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('advertiser', fn (Builder $q) => $q->where('is_active', true))
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
