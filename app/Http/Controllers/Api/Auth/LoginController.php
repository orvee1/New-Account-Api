<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request){

        $request->validate([
            'email'=>'required|string|email',
            'password'=>'required|string'
        ]);

        $throttleKey = strtolower((string) $request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => ['Too many login attempts. Please try again later.'],
            ]);
        }

        $user= CompanyUser::query()
            ->with('company:id,name')
            ->where('email', $request->email)
            ->first();
         

        if ($user && Hash::check($request->password,$user->password)) {
            RateLimiter::clear($throttleKey);
            Auth::login($user);
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // last_login_at update করতে চাইলে:
            $user->markLoggedIn();

            return response()->json([
                'message'    => 'Login Successful',
                'token'      => $token,
                'user'       => $user,
                'company_id' => $user->company_id,
            ], 200);
        }

        RateLimiter::hit($throttleKey, 60);

        return response()->json(['message'=>'Invalid Credentials'], 401);
    }
}
