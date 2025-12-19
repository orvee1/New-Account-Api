<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'movement_type',
        'qty_in',
        'qty_out',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'occurred_at',
        'created_by',
        'notes'
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];
}
