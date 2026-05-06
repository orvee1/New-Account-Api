<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PurchaseBillItem extends Model
{
    use BelongsToCompany;

    // protected $fillable = [
    //     'purchase_bill_id','product_id',
    //     'qty_unit_id','qty','qty_base',
    //     'rate_unit_id','rate_per_unit','rate_per_base',
    //     'discount_percent','discount_amount',
    //     'line_subtotal','line_total',
    //     'warehouse_id','batch_id','batch_no','manufactured_at','expired_at',
    // ];
    protected $fillable = [
        'company_id',
        'purchase_bill_id',
        'product_id',
        'purchase_uom_id',
        'price_uom_id',
        'quantity_in_purchase_uom',
        'quantity_in_base_uom',
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
        'net_unit_cost',
        'weighted_avg_cost_before',
        'weighted_avg_cost_after',
        'line_total',
        'warehouse_id'
    ];

    protected $casts = [
        'manufactured_at' => 'date',
        'expired_at'      => 'date',
    ];

    public function bill(){ return $this->belongsTo(PurchaseBill::class,'purchase_bill_id'); }
    public function product(){ return $this->belongsTo(Product::class); }
    public function purchaseUom(){ return $this->belongsTo(ProductUom::class,'purchase_uom_id'); }
    public function priceUom(){ return $this->belongsTo(ProductUom::class,'price_uom_id'); }
}
