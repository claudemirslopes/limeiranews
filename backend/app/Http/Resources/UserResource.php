<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'public_name' => $this->public_name ?? $this->name,
            'email' => $this->email,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'specialty' => $this->specialty,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'display_name' => $this->role->display_name,
            ]),
            'permissions' => $this->whenLoaded('role', fn () => $this->role?->relationLoaded('permissions')
                ? $this->role->permissions->pluck('name')->values()
                : []),
            'can_publish' => $this->canPublish(),
        ];
    }
}
