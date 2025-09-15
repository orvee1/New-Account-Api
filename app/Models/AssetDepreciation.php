<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetDepreciation extends Model
{
    use SoftDeletes;

    protected $table = 'asset_depreciations';

    protected $guarded = [];

    protected $casts = [
        'amount'      => 'decimal:2',
        'is_active'   => 'boolean',
        'start_date'  => 'date:Y-m-d',
        'end_date'    => 'date:Y-m-d',
    ];

    // Relationships
    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id'); // Asset model -> fixed_assets
    }
}
