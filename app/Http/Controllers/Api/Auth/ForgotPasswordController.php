<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\OTPService;


class ForgotPasswordController extends Controller
{
    public function sendResetOTP(Request $request){
        $data = $request->validate([
            'phone' => ['required', 'digits:11'],
        ]);

        $phone = $data['phone'];
        $user = User::query()->where('phone_number', $phone)->first();

        if ($user) {
            OTPService::generateOTP($phone);
        }

        Log::info("Password reset OTP requested.", [
            'phone_suffix' => substr($phone, -4),
            'user_found' => (bool) $user,
        ]);

        return response()->json(['message'=>'OTP sent successfully for password reset']);
    }
}
