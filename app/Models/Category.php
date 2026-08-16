<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'company_id','name','slug','description','status','parent_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if ($category->isDirty('name') || blank($category->slug)) {
                $category->slug = static::uniqueSlug($category->name, (int) $category->company_id, $category->id);
            }
        });
    }

    private static function uniqueSlug(string $name, int $companyId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $counter = 2;

        while (static::query()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
