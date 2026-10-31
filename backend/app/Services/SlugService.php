<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Build a slug from $title that is unique for the given model class.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function unique(string $title, string $modelClass, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($title) ?: 'item';
        $base = Str::limit($base, 200, '');
        $slug = $base;
        $counter = 2;

        while ($this->exists($modelClass, $column, $slug, $ignoreId)) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function exists(string $modelClass, string $column, string $slug, ?int $ignoreId): bool
    {
        /** @var Model $instance */
        $instance = new $modelClass;
        $query = $modelClass::query()->where($column, $slug);

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        if ($ignoreId !== null) {
            $query->where($instance->getKeyName(), '!=', $ignoreId);
        }

        return $query->exists();
    }
}
