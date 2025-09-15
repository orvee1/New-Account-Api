<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Customer extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'credit_limit'        => 'decimal:2',
        'opening_balance'     => 'decimal:2',
        'opening_balance_date'=> 'date',
    ];

    // Always scope by current user's company
    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $q) {
            if ($user = Auth::user()) {
                $q->where('company_id', $user->company_id);
            }
        });

        static::creating(function (self $model) {
            if ($user = Auth::user()) {
                $model->company_id = $model->company_id ?? $user->company_id;
                $model->created_by = $user->id;
                $model->updated_by = $user->id;
            }
            // default number if not provided
            if (empty($model->customer_number)) {
                $model->customer_number = 'C'.now()->format('ymd').'-'.substr((string) now()->timestamp, -4);
            }
        });

        static::updating(function (self $model) {
            if ($user = Auth::user()) {
                $model->updated_by = $user->id;
            }
        });
    }

    // Simple search scope
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('name', 'like', "%$term%")
               ->orWhere('display_name', 'like', "%$term%")
               ->orWhere('phone_number', 'like', "%$term%")
               ->orWhere('email', 'like', "%$term%")
               ->orWhere('customer_number', 'like', "%$term%");
        });
    }
}
