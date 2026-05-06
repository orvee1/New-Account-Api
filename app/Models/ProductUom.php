<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUom extends Model
{
    protected $fillable = [
        'product_id',
        'uom_id',
        'conversion_factor',
        'sale_price',
        'is_base_uom',
        'is_default_sale_uom',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }
}
