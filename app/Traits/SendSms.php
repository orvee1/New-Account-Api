<?php

namespace App\Traits;

use App\Models\DeviceOtpLog;
use App\Models\SmsEvent;
use App\Models\SmsLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


trait SendSms
{
    private function getSmsBodyDataAsArray($numbers_separate_by_coma, $text_message, $uuid = null)
    {
        return array(
            'username'      => "Genesis",
            'password'      => "Genes!s@12",
            'apicode'       => "5",
            'msisdn'        => [
                $numbers_separate_by_coma
            ],
            'countrycode'   => "880",
            'cli'           => "Genesis",
            'messagetype'   => preg_match('/^[a-z0-9 .\-]+$/i',  $text_message) ? '1' : '3',
            'message'       => $text_message,
            'clienttransid' => $uuid ?? uniqid(),
            'bill_msisdn'   => "8801969901099",
            'tran_type'     => "T",
            'request_type'  => "S",
        );
    }

    // bulk sms send option
    private function getBulkSmsBodyDataAsArray($number_array, $text_message)
    {
        return array(
            'username'      => "Genesis",
            'password'      => "Genes!s@12",
            'apicode'       => "5",
            'msisdn'        => $number_array, // array number limit 999
            'countrycode'   => "880",
            'cli'           => "Genesis",
            'messagetype'   => preg_match('/^[a-z0-9 .\-]+$/i',  $text_message) ? '1' : '3',
            'message'       => $text_message,
            'clienttransid' => uniqid(),
            'bill_msisdn'   => "8801969901099",
            'tran_type'     => "P", // Promotional
            'request_type'  => "B", // Bulk 
        );
    }

    private function callApiGetStatusCode($postvars)
    {
        try {
            $headers = [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json'
            ];
    
            $url = "https://corpsms.banglalink.net/bl/api/v1/smsapigw/";
    
            $response = Http::withHeaders($headers)
                ->post($url, $postvars);
    
            $response_as_json = $response->json();
    
            return $response_as_json['statusInfo']['statusCode'] ?? '';
        } catch(Exception $exception) {
            return 'server_error';
        }
    }

    private function storeSmsLog($mobile_number, $event_type, $event, $statusCode = '', $admin_id = null, $doctor_id = null)
    {
        return SmsLog::insert([
            'mobile_no'         => $mobile_number,
            'event_type'        => $event_type,
            'delivery_status'   => $statusCode == 1000 ? 'success' : "failed_{$statusCode}",
            'event'             => $event,
            'doctor_id'         => $doctor_id,
            'admin_id'          => $admin_id,
            'created_at'        => Carbon::now(),
        ]);
    }

    public function panel_send_sms($doctor_mobile_number, $message, $event, $uuid)
    {
        $postvars = $this->getSmsBodyDataAsArray($doctor_mobile_number, $message, $uuid);

        $statusCode = $this->callApiGetStatusCode($postvars);

        return $statusCode;
    }

    public function new_send_sms($doctor_mobile_number, $doctor_id, $sms, $uuid)
    {
        $postvars = $this->getSmsBodyDataAsArray($doctor_mobile_number, $sms->sms, $uuid);

        $statusCode = $this->callApiGetStatusCode($postvars);

        $admin_id = Auth::user()->id;

        $event_type = 0;

        $event = $sms->title ?? '';

        $this->storeSmsLog($doctor_mobile_number, $event_type, $event, $statusCode, $admin_id, $doctor_id);

        return $statusCode;
    }

    public function send_sms($doctor, $sms)
    {
        $postvars = $this->getSmsBodyDataAsArray($doctor->mobile_number, $sms->sms);

        $statusCode = $this->callApiGetStatusCode($postvars);

        $admin_id = Auth::user()->id;

        $event_type = $sms->sms_event->id ?? 0;

        $event = $sms->sms_event->name ?? '';

        return $this->storeSmsLog($doctor->mobile_number, $event_type, $event, $statusCode, $admin_id, $doctor->id);
    }

    public function send_admin_otp_sms($mobile_number, $smsText, $event)
    {

        $postvars = $this->getSmsBodyDataAsArray($mobile_number, $smsText);

        $statusCode = 0;

        if(env('SMS', true)) {
            $statusCode = $this->callApiGetStatusCode($postvars);
        }

        return $this->storeSmsLog($mobile_number, 1, $event, $statusCode, null, null);

    }
    
    public function send_custom_sms($doctor, $smsText, $event, $isAdmin = true)
    {
        $postvars = $this->getSmsBodyDataAsArray($doctor->mobile_number, $smsText);

        $statusCode = $this->callApiGetStatusCode($postvars);

        $admin_id = ($isAdmin == true) ? Auth::id() : '';

        $sms_event = SmsEvent::where(['name'=>$event])->first();

        $event_type = $sms_event->id ?? 0;

        $event = $sms_event->name ?? $event;

        return $this->storeSmsLog($doctor->mobile_number, $event_type, $event, $statusCode, $admin_id, $doctor->id);
    }


    public function send_custom_otp_sms($doctor, $smsText, $event, $otp_code)
    {
        $postvars = $this->getSmsBodyDataAsArray($doctor->mobile_number, $smsText);

        $statusCode = 0;

        if(env('SMS', true)) {
            $statusCode = $this->callApiGetStatusCode($postvars);
        }

        return DeviceOtpLog::insert([
            'doctor_id'         => $doctor->id,
            'event'             => $event,
            'delivery_status'   => $statusCode == 1000 ? 'success' : "failed_{$statusCode}",
            'otp'               => $otp_code,
            'created_at'        => Carbon::now(),
        ]);

    }

    public function send_custom_sms_unregistered($mobile_number, $smsText, $event, $isAdmin = true)
    {
        $postvars = $this->getSmsBodyDataAsArray($mobile_number, $smsText);

        $statusCode = $this->callApiGetStatusCode($postvars);

        $admin_id = ($isAdmin == true) ? Auth::id() : '';

        $sms_event = SmsEvent::where(['name'=>$event])->first();

        $event_type = $sms_event->id ?? 0;

        $event = $sms_event->name ?? '';

        return $this->storeSmsLog($mobile_number, $event_type, $event, $statusCode, $admin_id);
    }
    
}
