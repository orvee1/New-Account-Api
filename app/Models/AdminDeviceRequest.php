<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminDeviceRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        "accept_at" => "datetime",
    ];

    public function scopePending($query)
    {
        $query
            ->whereNull('accept_at');
    }

    public function scopeAccepted($query)
    {
        $query
            ->whereNotNull('accept_at');
    }

    public function getIsPendingAttribute()
    {
        return (bool) (!$this->accept_at);
    }

    public function admin_device()
    {
        return $this->belongsTo(AdminDevice::class, 'user_device_id', 'id');
    }
}
