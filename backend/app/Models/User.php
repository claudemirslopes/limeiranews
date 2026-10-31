<?php

namespace App\Models;

use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'bio',
        'avatar_path',
        'is_active',
        'last_login_at',
        'public_name',
        'slug',
        'specialty',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function hasRole(RoleName|string ...$roles): bool
    {
        $current = $this->role?->name;
        if ($current === null) {
            return false;
        }

        foreach ($roles as $role) {
            $name = $role instanceof RoleName ? $role->value : $role;
            if ($name === $current) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return (bool) $this->role?->permissions->contains('name', $permission);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(...RoleName::administrators());
    }

    public function isEditor(): bool
    {
        return $this->hasRole(...RoleName::editors());
    }

    public function isWriter(): bool
    {
        return $this->hasRole(...RoleName::writers());
    }

    /** Admins and editors can publish; writers cannot. */
    public function canPublish(): bool
    {
        return $this->is_active && ($this->isAdmin() || $this->isEditor());
    }

    public function canApprove(): bool
    {
        return $this->canPublish();
    }

    /** Whether the user can access the editorial back office at all. */
    public function canAccessAdmin(): bool
    {
        return $this->is_active && $this->role_id !== null;
    }
}
