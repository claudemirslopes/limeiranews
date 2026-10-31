<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Advertiser extends Model
{
    protected $fillable = ['name', 'contact_email', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }
}
