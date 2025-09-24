<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PurchaseBillItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'purchase_bill_id','product_id',
        'qty_unit_id','qty','qty_base',
        'rate_unit_id','rate_per_unit','rate_per_base',
        'discount_percent','discount_amount',
        'line_subtotal','line_total',
        'warehouse_id','batch_id','batch_no','manufactured_at','expired_at',
    ];

    protected $casts = [
        'manufactured_at' => 'date',
        'expired_at'      => 'date',
    ];

    public function bill(){ return $this->belongsTo(PurchaseBill::class,'purchase_bill_id'); }
    public function product(){ return $this->belongsTo(Product::class); }
    public function qtyUnit(){ return $this->belongsTo(ProductUnit::class,'qty_unit_id'); }
    public function rateUnit(){ return $this->belongsTo(ProductUnit::class,'rate_unit_id'); }
}
