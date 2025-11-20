<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model {
    use BelongsToCompany;

    protected $fillable = ['product_id','batch_no','manufactured_at','expired_at'];
    protected $casts = ['manufactured_at'=>'date','expired_at'=>'date'];
    public function product(){ return $this->belongsTo(Product::class); }
}