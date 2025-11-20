<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request){
        $request->validate([
            'email'=>'required|string|email',
            'password'=>'required|string'
        ]);

        $user= CompanyUser::query()
            ->with('company:id,name')
            ->where('email', $request->email)->first();
     if($user && Hash::check($request->password,$user->password)){
        Auth::login($user);
        $token= $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'=>'Login Successful',
            'token'=>$token,
            'user'=>$user,
        ], 200);
     }
        return response()->json(['message'=>'Invalid Credentials'], 401);
    }
}
