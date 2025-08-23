<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSmsLog;
use App\Traits\SendSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminOtpSendController extends Controller
{
    use SendSms;
    
    public function send_otp (Request $request)
    {
        $user = $request->user();
        $otp = rand(1000, 9999);

        $postvars = $this->getSmsBodyDataAsArray($user->phone_number, "Your password change OTP is: $otp");

        $statusCode = $this->callApiGetStatusCode($postvars);

        $admin_id = Auth::user()->id;

        $event_id = 1 ?? 0;

        $event = AdminSmsLog::$smsLogEvents[1] ?? '';

        $user->otp = $otp;

        $user->save();

        AdminSmsLog::create([
            'mobile_no' => $user->phone_number,
            'event_id' => $event_id,
            'event' => $event,
            'delivery_status' => $statusCode == 1000 ? 'success' : "failed_{$statusCode}",
            'admin_id' => $admin_id,
            'created_at' => now(),
        ]);


        return response()->json(['status' => 'success', 'message' => "OTP sent successfully to {$user->phone_number}"]);
    }

    public function verify_otp (Request $request)
    {
        $user = $request->user();
        $otp = $request->otp;

        if ($user->otp == $otp) {
            return response()->json(['status' => 'success', 'message' => 'OTP verified successfully']);
        }

        return response()->json(['status' => 'error', 'message' => 'Invalid OTP']);
    }

    public function change_password(Request $request)
    {
        $user = $request->user();
        $old_password = $request->old_password;
        $new_password = $request->new_password;
        if ($old_password == $new_password) {
            return response()->json(['status' => 'error', 'message' => 'You cant use the same password.']);
        }
        if (!Hash::check($old_password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Old password is incorrect']);
        }

        $user->password = Hash::make($new_password);
        $user->last_password_changed_at = now();
        $user->save();

        Auth::logout();

        return response()->json(['status' => 'success', 'message' => 'Password changed successfully']);
    }
}
