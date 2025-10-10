<?php

// app/Models/Product.php
namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model {
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'product_type','name','code','description',
        'batch_no','manufactured_at','expired_at',
        'extra_field1_name','extra_field1_value','extra_field2_name','extra_field2_value',
        'category','costing_price','sales_price',
        'has_warranty','warranty_days','base_unit_name',
        'created_by','updated_by'
    ];

    protected $casts = [
        'has_warranty' => 'boolean',
        'manufactured_at' => 'date',
        'expired_at' => 'date',
    ];

    public function units() { return $this->hasMany(ProductUnit::class); }
    public function comboItems() { return $this->hasMany(ProductComboItem::class, 'combo_product_id'); }
    public function batches() { return $this->hasMany(ProductBatch::class); }
}
