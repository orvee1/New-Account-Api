<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'sale_uom_id',
        'price_uom_id',
        'quantity',
        'quantity_in_sale_uom',
        'quantity_in_base_uom',
        'unit_price',
        'unit_price_original',
        'trade_discount_pct',
        'trade_discount_amt',
        'net_unit_price',
        'line_gross_amount',
        'line_discount_pct',
        'line_discount_amt',
        'line_subtotal',
        'vat_rate',
        'vat_amount',
        'ait_rate',
        'ait_amount',
        'weighted_avg_cost',
        'cogs',
        'gross_profit',
        'discount_amount',
        'tax_amount',
        'line_total',
        'description'
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'unit_price_original' => 'decimal:4',
        'trade_discount_pct' => 'decimal:2',
        'trade_discount_amt' => 'decimal:4',
        'net_unit_price' => 'decimal:4',
        'line_gross_amount' => 'decimal:4',
        'line_discount_pct' => 'decimal:2',
        'line_discount_amt' => 'decimal:4',
        'line_subtotal' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:4',
        'ait_rate' => 'decimal:2',
        'ait_amount' => 'decimal:4',
        'weighted_avg_cost' => 'decimal:4',
        'cogs' => 'decimal:4',
        'gross_profit' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relationships
    public function saleUom()
    {
        return $this->belongsTo(ProductUom::class, 'sale_uom_id');
    }

    public function priceUom()
    {
        return $this->belongsTo(ProductUom::class, 'price_uom_id');
    }
    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnItems()
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}
