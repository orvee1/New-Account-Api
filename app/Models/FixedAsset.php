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
}
