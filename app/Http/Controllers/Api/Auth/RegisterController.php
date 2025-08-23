<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
   
    public function register(Request $request)
    {
       
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|numeric|digits:11|unique:users', 
            'password' => 'required|string|min:8',
        ]);

       
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone, 
            'password' => Hash::make($request->password),
        ]);

        

        return response()->json([
            'message' => 'Registration successful',
            
        ], 201);
    }
}
