<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    
    public function sendOtp(Request $request){
       $validator= Validator::make($request->all(),[
        'email'=>'required|email|exists:users,email',
        'phone'=>'required|digits:11',
       ]);
       
       if($validator->fails()){
        return response()->json(['error'=>$validator->errors()],422);
       }
       $otp= rand(100000,999999);
       $email=$request->email;

       DB::table('email_verification')->updateOrInsert(
        ['email'=>$email],
        [
            'otp'=>$otp,
            'phone'=>$request->phone,
            'created_at'=> Carbon::now(),
        ]
        );

        // Mail::send('email_verification', ['otp'=>$otp] , function($message) use ($email){
        //     $message->to($email)->subject('Your Email Verification OTP');
        // });
        // return response()->json(['message'=>'OTP sent to your phone number. '], 200);
    }

    public function verifyOtp(Request $request){
        $validator= Validator::make($request->all(),[
            'email'=>'required|email|exists:users,email',
            'otp'=>'required|digits:6',
        ]);

        if($validator->fails()){
            return response()->json(['errors'=>$validator->errors()], 422);
        }

        $verificationEntry= DB::table('email_verification')
          ->where('email', $request->email) 
          ->where('otp', $request->otp)
          ->first();

        $otpCreatedAt= Carbon::parse($verificationEntry->created_at);
        if ($otpCreatedAt->diffInMinutes(Carbon::now())>10){
            return response()->json(['error'=>'OTP has expired'], 400);
        }

        $user= $request->user();
        $user->created_at=now();
        $user->save();

        DB::table('email_verifications')->where('email',$request->email)->delete();
        return response()->json(['message'=>'Email Verified Successfully.'], 200);
        }
}
