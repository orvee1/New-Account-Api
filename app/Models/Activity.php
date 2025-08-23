<?php

namespace App\Models;

// use App\Traits\DeviceBrowserString;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    // use DeviceBrowserString;

    protected $guarded = [];

    protected $casts = [
        "request" => "json",
        "data" => "json",
        "files" => "json",
    ];

    protected $appends = [
        "device_browser_string",
    ];

    public function user()
    {
        $class_name = $this->guard == 'doctor'
            ? Doctors::class
            : User::class;
 
        return $this->belongsTo($class_name, "user_id", "id");
    }

    public function doctor()
    {
        return $this->belongsTo( Doctors::class, "user_id", "id");
    }

    public function uuid_admin_activities()
    {
        return $this->hasMany(Activity::class, 'uuid', 'uuid')
            ->where('guard', 'web');
    }
}
