<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $fillable = [
        'sales_return_id',
        'sales_invoice_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'line_total'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relationships
    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function salesInvoiceItem()
    {
        return $this->belongsTo(SalesInvoiceItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
