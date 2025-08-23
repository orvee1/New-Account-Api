<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OTPService
{

    public static function generateOTP($phone)
    {

        $OTP = rand(100000, 999999);


        $expiresAt = Carbon::now()->addMinutes(10);


        DB::table('otps')->updateOrInsert(
            ['phone' => $phone],
            ['otp' => $OTP, 'expires_at' => $expiresAt, 'created_at' => now(), 'updated_at' => now()]
        );

        return $OTP;
    }


    public static function validateOTP($phone, $otp)
    {

        $record = DB::table('otps')->where('phone', $phone)->first();


        if ($record && $record->otp == $otp) {

            if (Carbon::now()->gt(Carbon::parse($record->expires_at))) {
                return false;
            }
            return true;
        }

        return false;
    }



    public static function deleteOTP($phone)
    {

        DB::table('otps')->where('phone', $phone)->delete();
    }
}
