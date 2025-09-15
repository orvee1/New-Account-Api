<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model {
    use BelongsToCompany;

    protected $fillable = ['name','is_default'];
    protected $casts = ['is_default'=>'boolean'];
}