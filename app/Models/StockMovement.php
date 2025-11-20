<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model {
    use BelongsToCompany;

    protected $fillable = [
        'product_id','warehouse_id','product_batch_id',
        'type','quantity','unit_name','unit_factor_to_base','created_by'
    ];
    protected $casts = ['quantity'=>'decimal:6','unit_factor_to_base'=>'decimal:6'];

}
