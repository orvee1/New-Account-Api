<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'company_id',
        'product_type',     // Stock | Non-stock | Service | Combo
        'name',
        'sku',
        'barcode',
        'category_id',
        'brand_id',
        'unit',
        'costing_price',
        'sales_price',
        'tax_percent',
        'has_warranty',
        'warranty_days',
        'description',
        'status',           // active | inactive
        'meta',
    ];

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
        return $this->hasMany(ProductComboItem::class, 'product_id');
    }
}
