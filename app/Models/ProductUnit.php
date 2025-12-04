<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'name',
        'factor',
        'is_base',
    ];

    protected $casts = [
        'factor'  => 'decimal:6',
        'is_base' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
