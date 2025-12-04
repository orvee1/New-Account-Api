<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta'          => 'array',
        'has_warranty'  => 'boolean',
        'costing_price' => 'decimal:4',
        'sales_price'   => 'decimal:4',
        'tax_percent'   => 'decimal:2',
    ];

    // Relations
    public function units()
    {
        return $this->hasMany(ProductUnit::class);
    }

    // For Combo products
    public function comboItems()
    {
        return $this->hasMany(ProductComboItem::class, 'combo_product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
