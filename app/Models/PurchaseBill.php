<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseBill extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'vendor_id','bill_no','bill_date','due_date','warehouse_id','notes',
        'subtotal','discount_total','tax_amount','total_amount',
        'created_by','updated_by'
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date'  => 'date',
    ];

    public function vendor(){ return $this->belongsTo(Vendor::class); }
    public function items(){ return $this->hasMany(PurchaseBillItem::class); }
}
