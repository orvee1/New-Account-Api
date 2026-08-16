<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = [
        'company_id','name','slug','description','status',
    ];

    protected static function booted(): void
    {
        static::saving(function (Brand $brand) {
            if ($brand->isDirty('name') || blank($brand->slug)) {
                $brand->slug = static::uniqueSlug($brand->name, (int) $brand->company_id, $brand->id);
            }
        });
    }

    private static function uniqueSlug(string $name, int $companyId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'brand';
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
}
