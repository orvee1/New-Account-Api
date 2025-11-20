<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'purchase_return_id','product_id',
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

    public function purchaseReturn(){ return $this->belongsTo(PurchaseReturn::class,'purchase_return_id'); }
    public function product(){ return $this->belongsTo(Product::class); }
}
