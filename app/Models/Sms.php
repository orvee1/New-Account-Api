<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sms extends Model
{
    use SoftDeletes;
    protected $table = 'sms';

    public function sms_event()
    {
        return $this->belongsTo(SmsEvent::class,'sms_event_id','id');
    }
}
