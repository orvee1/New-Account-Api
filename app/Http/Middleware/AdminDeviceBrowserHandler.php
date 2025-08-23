<?php

namespace App\Http\Middleware;

use App\Models\AdminDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDeviceBrowserHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $need_verification = Auth::user();
        if(env('APP_ENV') == 'production' &&  $need_verification) {
    
            $uuid = AdminDevice::device_uuid();

            if(!$uuid) {
                $new_uuid = base64_encode(uniqid(str_random(10) . str_random(10) . str_random(10), true));
            }

            $is_device_active =  AdminDevice::query()
                ->where('user_id', Auth::id())
                ->where('uuid', $uuid ?? $new_uuid)
                ->active()
                ->exists();

            if(!$is_device_active){
                return redirect()->route('admin-device-verification.index')
                    ->with([
                        'message' => 'Need verification for this device...'
                    ])
                    ->withCookie(cookie()->forever('A-GNS-UUID', $uuid ?? $new_uuid));
            }
        }

        return $next($request);
    }

}
