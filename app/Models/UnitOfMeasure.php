<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    protected $table = 'units_of_measure';
    protected $fillable = ['name', 'symbol'];

    public function productUoms()
    {
        return $this->hasMany(ProductUom::class, 'uom_id');
    }
}
