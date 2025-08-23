<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{

    const UPDATED_AT = null;

    public static $smsLogEvents = [
        1 => "Admission",
        2 => "Admission (from app)",
        3 => "Admission Auto",
        4 => "Admission(Office)",
        5 => "BMDC Verified",
        6 => "Call Center",
        7 => "Complain Create",
        8 => "Complain Reply",
        9 => "Complain Box Registration",
        10 => "Discount",
        11 => "Doctor Course",
        12 => "Forget Password",
        13 => "Forget Password (from app)",
        14 => "Lecture Sheets",
        15 => "OMR Result Published (from app)",
        16 => "Payment Completed",
        17 => "Payment Completed (Office)",
        18 => "Quick Enroll",
        19 => "Quick Registration",
        20 => "Registration",
        21 => "Registration (Office)",
        22 => "Request Discount",
        34 => "Batch Shifted",
        35 => "Doctor System Driven Accepted",
        36 => "Doctor System Driven Denied",
        37 => "FB group link",
        38 => "Exam Reminder",
        39 => "Question Print",
        40 => "Installment Payment",
        41 => "Installment Due Reminder",
        42 => "Installment Due Auto Reminder",
        43 => "Online Payment",
        51 => "Subscription Manual Payment",
        52 => "Scholar Manual Payment",
        53 => "OTP for Lecture Video",
        56 => "Event Booking",
    ];

    public static function getSmsLogEvents()
    {
        return self::$smsLogEvents;
    }


    public function set_response($response, $doctor_id, $mob = null, $admin_id = null)
    {
        $response = json_decode($response, true);
        if (is_array($response) && isset($response['JobId'])) {
            $this->job_id = $response['JobId'];
            $this->doctor_id = $doctor_id;

            if ($admin_id != null) {
                $this->admin_id = $admin_id;
            }

            if ($mob != null) {
                $this->mobile_no = preg_replace('/^88/', '', $mob);
            }
        }
        return $this;
    }

    public function save(array $options = [])
    {
        $cUrl = curl_init();
        curl_setopt($cUrl, CURLOPT_URL, "http://sms4.digitalsynapsebd.com/api/mt/GetDelivery?user=genesispg&password=123321@12&jobid=$this->job_id");
        curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $cUrl,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Accept: application/json',
            ]
        );

        $response = curl_exec($cUrl);
        curl_close($cUrl);
        $response = json_decode($response, true);

        if (isset($response['DeliveryReports']) && is_array($response['DeliveryReports'])) {
            $data = $response['DeliveryReports'][0] ?? null;
            if ($data) {
                $this->delivery_status = $data["DeliveryStatus"];
            }
        }

        if ($this->id != null) {
            $this->created_at = Carbon::now();
        }

        return parent::save($options);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctors::class, 'doctor_id', 'id');
    }
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'id');
    }


    public  function getEventTextAttribute()
    {
        $event_type = $this->event_type;
        $logEvents = self::getSmsLogEvents();

        return $logEvents[$event_type] ?? '';
    }

    public function set_event($event)
    {


        $logEvents = self::getSmsLogEvents();


        if (is_numeric($event)) {
            $this->event_type = $event ?? 0;
            $this->event = $logEvents[$event] ?? 0;
        } else {
            $event_type = array_search($event, $logEvents);
            $this->event_type = $event_type ?? 0;
            $this->event = $event;
        }

        return $this;
    }
}
