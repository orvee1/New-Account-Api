<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:4',
        'useful_life' => 'integer',
    ];

    // Optional relationships
    public function creator() { return $this->belongsTo(CompanyUser::class, 'created_by'); }
    public function updater() { return $this->belongsTo(CompanyUser::class, 'updated_by'); }

    public function depreciations()
    {
        return $this->hasMany(AssetDepreciation::class, 'fixed_asset_id');
    }

    public function activeDepreciation()
    {
        return $this->hasOne(AssetDepreciation::class, 'fixed_asset_id')
            ->where('is_active', true)
            ->latestOfMany();
    }

    public function disposals()
    {
        return $this->hasMany(AssetDisposal::class, 'fixed_asset_id');
    }

}
