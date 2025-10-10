<?php
namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCompany {
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    protected static function bootBelongsToCompany(): void
    {
        // Set company_id on create (only when an authenticated user exists)
        static::creating(function (Model $model) {
            $user = auth('sanctum')->user() ?? auth()->user();
            if (!$model->company_id && $user) {
                $model->company_id = $user->company_id;
            }
        });

        // Company scope (applies only when a user is present)
        static::addGlobalScope('company', function (Builder $q) {
            $user = auth('sanctum')->user() ?? auth()->user();
            if ($user) {
                $q->where(
                    $q->qualifyColumn('company_id'), // Laravel 10 ok
                    $user->company_id
                );
            }
        });
    }
}
