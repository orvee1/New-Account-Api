<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\OTPService;

class ResetpasswordController extends Controller
{
    // public function __construct(){
    //     $this->middleware('guest');
    // }

    public function reset(Request $request)
    {

        $request->validate([
            'phone' => 'required|exists:users,phone',
            'otp' => 'required|numeric',
            'password' => 'required|min:8|confirmed',
        ]);


        $isValidOTP = OTPService::validateOTP($request->phone, $request->otp);

        if (!$isValidOTP) {
            return response()->json([
                'message' => 'Invalid OTP.',
            ], 400);
        }


        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }


        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();


        OTPService::deleteOTP($request->phone);

        return response()->json([
            'message' => 'Password has been successfully reset.',
        ], 200);
    }
}
