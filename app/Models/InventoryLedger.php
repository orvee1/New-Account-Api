<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLedger extends Model
{
    protected $table = 'inventory_ledger';

    protected $fillable = [
        'product_id',
        'reference_id',
        'reference_type',
        'qty_in',
        'qty_out',
        'qty_balance',
        'unit_cost',
        'total_cost',
        'new_weighted_avg_cost',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
