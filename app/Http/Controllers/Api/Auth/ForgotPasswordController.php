<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\OTPService;


class ForgotPasswordController extends Controller
{
    public function sendResetOTP(Request $request){
        $validator= Validator::make($request->all(), [
            'phone'=>'required|exists:users,phone',
        ]);
        if($validator->fails()){
            return response()->json(['message'=>$validator->errors()->first(), 422]);
        }
       
        $otp= OTPService::generateOTP($request->phone,);
        Log::info("Password Reset OTP for {$request->phone}: $otp");
        return response()->json(['message'=>'OTP sent successfully for password reset']);
    }
}