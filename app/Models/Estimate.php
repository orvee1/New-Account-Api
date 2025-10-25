<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estimate extends Model
{
    protected $guarded = [];

    protected $casts   = [
        'estimate_date'  => 'date',
        'expiry_date'    => 'date',
        'is_draft'       => 'boolean',
        'subtotal'       => 'decimal:2',
        'total_discount' => 'decimal:2',
        'grand_total'    => 'decimal:2',
    ];
    
    public function items()
    {return $this->hasMany(EstimateItem::class);}

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', auth()->user()->company_id)->firstOrFail();
    }
}
