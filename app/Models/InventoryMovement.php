<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'product_id','warehouse_id','batch_id',
        'quantity_base','unit_cost_base',
        'document_type','document_id','meta','created_by'
    ];

    protected $casts = ['meta' => 'array'];
}
