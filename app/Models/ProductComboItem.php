<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ProductComboItem extends Model {
    use BelongsToCompany;

    protected $fillable = ['combo_product_id','item_product_id','quantity'];
    protected $casts = ['quantity'=>'decimal:6'];
    public function combo(){ return $this->belongsTo(Product::class, 'combo_product_id'); }
    public function item(){ return $this->belongsTo(Product::class, 'item_product_id'); }
}