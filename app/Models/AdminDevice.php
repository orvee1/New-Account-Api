<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DeviceInfo;

class AdminDevice extends Model
{
    use HasFactory, DeviceInfo;

    protected $guarded  = [];

    public $timestamps = false;

    protected $casts = [
        'last_used_at' => 'datetime',
        'verified_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        $query
            ->whereNotNull('verified_at')
            ->whereNull('expired_at');
    }

    public function getNameAttribute()
    {
        return $this->user_agent
            ? self::getDeviceName($this->user_agent)
            : "Unknown";
    }

    public function admin_device_requests()
    {
        return $this->hasMany(AdminDeviceRequest::class, 'user_device_id', 'id')
            ->latest();
    }

    public function request()
    {
        return $this->hasOne(AdminDeviceRequest::class,  'user_device_id', 'id')
            ->where('accept_at', null)
            ->latest();
    }

    public static function device_type(){
        return  request()->cookie( 'X-DEVICE-TYPE', 'Browser' );
    }

    public static function device_uuid(){
        return  request()->cookie( 'A-GNS-UUID' );
    }

}
