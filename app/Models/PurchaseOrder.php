<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'company_id', 'vendor_id', 'order_no', 'order_date',
        'expected_delivery_date', 'notes', 'status',
        'subtotal', 'discount_total', 'tax_amount', 'total_amount',
        'created_by', 'updated_by'
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
