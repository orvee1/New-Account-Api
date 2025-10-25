<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'invoice_date'   => 'date',
        'due_date'       => 'date',
        'subtotal'       => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_vat'      => 'decimal:2',
        'shipping_amount'=> 'decimal:2',
        'grand_total'    => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        // Multi-tenant protection
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
    }
}
