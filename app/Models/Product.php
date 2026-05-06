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
        'weighted_avg_cost' => 'decimal:4',
        'current_stock_in_base_uom' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'vat_inclusive' => 'boolean',
        'ait_rate' => 'decimal:2',
    ];

    // Relations
    public function units()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function productUoms()
    {
        return $this->hasMany(ProductUom::class);
    }

    public function baseUom()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_uom_id');
    }

    public function inventoryLedger()
    {
        return $this->hasMany(InventoryLedger::class);
    }

    // For Combo products
    public function comboItems()
    {
        return $this->hasMany(ProductComboItem::class, 'product_id');
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

    // Sales relations
    public function salesOrderItems()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function salesInvoiceItems()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function salesReturnItems()
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    // Purchase relations
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function purchaseBillItems()
    {
        return $this->hasMany(PurchaseBillItem::class);
    }
}
