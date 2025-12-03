<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'company_id', 'product_id',
        'qty_unit_id', 'qty', 'qty_base',
        'rate_unit_id', 'rate_per_unit',
        'discount_percent', 'discount_amount',
        'line_subtotal', 'line_total'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
