<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminDevice;
use App\Models\AdminDeviceRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDeviceLogController extends Controller
{
    public function __construct()
    {
        // $this->middleware('can:Admin Device Verification Log');
    }

    public function index()
    {

        if (request()->isXmlHttpRequest()) {

            $users = User::query()
                ->where("status", 1)
                ->when(
                    request()->search,
                    function ($query, $search) {
                        $query
                            ->where("phone_number", "like", "%{$search}%")
                            ->orWhere("name", "like", "%{$search}%")
                            ->orWhere("email", "like", "%{$search}%")
                        ;
                    }
                )
                ->when(request()->request_pending && request()->request_pending == 'yes', function ($query) {
                    $query->whereHas('admin_devices', function ($query) {
                        $query->whereHas('request', function ($query) {
                            $query->where('accept_at', null);
                        });
                    });
                })
                ->select(
                    'id',
                    'name',
                    'email',
                    'phone_number',
                )
                ->paginate(24);

            return  view('admin.admin-device-log.partials.doctor-log-data', compact('users'))->render();
        }

        $permission_ids = Setting::where('name', 'admin_device_verification_permissions')->first()->value ?? [];

        $permission_ids = $permission_ids ? json_decode($permission_ids, true) : [];

        $is_device_accept_permission = Auth::user()->hasRole("Developer") || Auth::user()->hasRole("Administrator") || in_array(Auth::user()->id, $permission_ids);

        if (!$is_device_accept_permission) {
            return abort(404);
        }

        // Android active batch android user count 

        return view('admin.admin-device-log.index');
    }

    public function show(User $user)
    {
        $active_devices = $user->admin_devices()
            ->active()
            ->get();

        $pending_user_devices = $user->admin_devices()
            ->with('admin_device_requests')
            ->whereHas('admin_device_requests', function ($query) {
                $query->where('accept_at', null);
            })
            ->get();


        $device_requests = $user->admin_device_requests()
            ->accepted()
            // ->with('admin_device')
            ->latest()
            ->get();

        foreach ($device_requests as &$device_request) {
            $device_request->admin_device->name = $this->getDeviceBrowserStringFromUserAgent($device_request->admin_device->user_agent);

            $device_request->admin_device->last_active = $device_request->admin_device->last_used_at->format("d M Y, h:ia");
        }

        $permission_ids = Setting::where('name', 'admin_device_verification_permissions')->first()->value ?? [];

        $permission_ids = $permission_ids ? json_decode($permission_ids, true) : [];

        $is_device_accept_permission = Auth::user()->hasRole("Developer") || Auth::user()->hasRole("Administrator") || in_array(Auth::user()->id, $permission_ids);

        if (!$is_device_accept_permission) {
            return abort(404);
        }

        return view('admin.admin-device-log.show', compact(
            'user',
            'active_devices',
            'pending_user_devices',
            'device_requests',
            'is_device_accept_permission',
        ));
    }

    public function acceptDeviceRequest(AdminDevice $admin_device, AdminDeviceRequest $admin_device_request)
    {

        if ($admin_device->id != $admin_device_request->user_device_id) {
            return abort(404);
        }

        $is_update = false;

        $data = [];

        // verify device
        $data["verified_at"] = now();
        $data["expired_at"] = null;


        $is_update = $admin_device->update($data);

        if ($is_update) {

            $admin_device_request->update([
                'note'      => request()->note ?? 'Update from Admin Panel',
                'accept_at' => now(),
                'accept_by' => Auth::guard('web')->id(),
            ]);
        }

        return back();
    }

    public function cancelDeviceRequest(AdminDevice $admin_device, AdminDeviceRequest $admin_device_request)
    {
        if ($admin_device->id != $admin_device_request->user_device_id) {
            return abort(404);
        }

        $admin_device_request->update([
            'note'      => request()->note ?? 'Cancel from Admin Panel',
            'accept_at' => now(),
            'accept_by' => Auth::guard('web')->id(),
            'type'      => 0
        ]);

        $admin_device->update([
            'expired_at' => now(),
        ]);

        return back();
    }
}
