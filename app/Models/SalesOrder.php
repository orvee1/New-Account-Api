<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $guarded = [];
    protected $casts   = [
        'order_date' => 'date', 'expected_delivery_date' => 'date',
        'subtotal'   => 'decimal:2', 'total_discount'    => 'decimal:2', 'tax_amount' => 'decimal:2', 'grand_total' => 'decimal:2',
    ];
    public function items()
    {return $this->hasMany(SalesOrderItem::class);}

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', auth()->user()->company_id)->firstOrFail();
    }
}
