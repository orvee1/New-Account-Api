<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceOtpLog extends Model
{
    use HasFactory, SoftDeletes;

    public function doctor()
    {
        return $this->belongsTo(Doctors::class, 'doctor_id');
    }
}
