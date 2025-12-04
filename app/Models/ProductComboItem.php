<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductComboItem extends Model
{
    protected $fillable = [
        'company_id',
        'combo_product_id',  // combo product id
        'item_product_id',   // component product id
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
    ];

    public function comboProduct()
    {
        return $this->belongsTo(Product::class, 'combo_product_id');
    }

    public function itemProduct()
    {
        return $this->belongsTo(Product::class, 'item_product_id');
    }
}
