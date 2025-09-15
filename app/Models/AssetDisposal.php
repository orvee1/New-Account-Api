<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetDisposal extends Model
{
    use SoftDeletes;

    protected $table = 'asset_disposals';
    protected $guarded = [];

    protected $casts = [
        'disposal_value' => 'decimal:2',
        'disposed_at'    => 'date:Y-m-d',
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
