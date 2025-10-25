<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimateItem extends Model
{
    protected $guarded = [];

    protected $casts   = [
        'qty_input'             => 'decimal:6',
        'qty_unit_factor'       => 'decimal:6',
        'base_qty'              => 'decimal:6',
        'billing_unit_factor'   => 'decimal:6',
        'rate_per_billing_unit' => 'decimal:6',
        'unit_price_base'       => 'decimal:6',
        'line_subtotal'         => 'decimal:2',
        'discount_percent'      => 'decimal:4',
        'discount_amount'       => 'decimal:2',
        'line_total'            => 'decimal:2',
    ];
    
    public function estimate()
    {return $this->belongsTo(Estimate::class);}
}
