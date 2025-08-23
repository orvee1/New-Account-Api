<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AdminDeviceRequest;
use App\Traits\AdminDeviceMethods;
use App\Traits\SendSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDeviceVerificationController extends Controller
{
    use SendSms, AdminDeviceMethods;

    public function index(Request $request)
    {
        $active_devices = $this->getActiveAdminDevices();      
        $current_device = $this->getCurrentAdminDevice();
        $count_active_devices = $this->countActiveAdminDevices();
        

        return view('admin.admin-device-verification.index', compact(
            'current_device',
            'active_devices',
            'count_active_devices',
        ));
    }


    public function store(Request $request)
    {
        $request->validate([
            'reason' => ['required'],
        ]);

        $current_device = $this->getCurrentAdminDevice();

        AdminDeviceRequest::updateOrCreate(
            [
                "user_id"           => Auth::id(),
                "user_device_id"    => $current_device->id,
                "accept_at"         => null,
            ],
            [
                "reason"            => $request->reason ?? '',
            ]
        );

        return redirect()->route('admin-device-verification.index', [
            'message' => session('Request sent successfully. Please contact the adminstrator to accept your request.')
        ]);
    }

    public function otp_send( )
    {
        if(Auth::user())
        {
            $code = session("AdminVerificationOTP");
    
            if(!$code){
                $code = rand(11111, 99999);
            }

            // Set Code from session
            session()->put("AdminVerificationOTP" , $code);

            $msg = 'Your Admin Device Verification OTP : ' . $code;

            $user = Auth::user();
            $user->otp = $code;
            $user->save();

            $this->send_admin_otp_sms( Auth::user()->phone_number, $msg, 'OTP for Admin Device Verification');
            
            return response()->json([
                'status' => true,
                'message' => 'Device Verification OTP send successfully.',
            ]);
            
        }
        
        return response()->json([
            'status' => false,
            'message' => 'Aunthorizied user can only send OTP.',
        ]);
    }

    public function check_otp_store(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string']
        ]);

        $code = session("AdminVerificationOTP");

        $otp_code = $request->otp ?? null;

        if(!$code || $code != $otp_code)
        {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP.',
            ]);
        }

        $current_device = $this->getCurrentAdminDevice();
        $current_device->verified_at = now();
        $current_device->expired_at = null;
        $current_device->save();

        AdminDeviceRequest::updateOrCreate(
            [
                "user_id"           => Auth::id(),
                "user_device_id"    => $current_device->id,
                "accept_at"         => null,
            ],
            [
                'note'      => 'Update from Admin Panel',
                'accept_at' => now(),
                'accept_by' => Auth::id(),
            ]
        );

        session()->remove("AdminVerificationOTP");

        return response()->json([
            'status' => true,
            'message' => 'successfully verified.',
        ]);
    }
}
