<?php

// app/Models/ProductUnit.php
namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model {
    use BelongsToCompany;

    protected $fillable = ['product_id','name','factor','is_base'];
    protected $casts = ['is_base'=>'boolean','factor'=>'decimal:6'];
    public function product(){ return $this->belongsTo(Product::class); }
}
