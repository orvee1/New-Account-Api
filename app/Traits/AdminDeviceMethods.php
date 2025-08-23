<?php

namespace App\Traits;

use App\Models\AdminDevice;
use Illuminate\Support\Facades\Auth;

trait AdminDeviceMethods
{
    protected function getCurrentAdminDevice()
    {
        $doctor_device = AdminDevice::updateOrCreate(
            [
                "user_id"        => Auth::id(),
                "uuid"           => AdminDevice::device_uuid(),
            ],
            [
                "device_type"   => AdminDevice::device_type(),
                "user_agent"    => request()->userAgent(),
                "last_used_at"  => now(),
            ]
        );

        return $doctor_device;
    }

    protected function getAdminDevices($scope = null)
    {
        $query = $this->getAdminDeviceQuery($scope);

        return $query->get();
    }



    protected function countAdminDevices($scope = null)
    {
        $query = $this->getAdminDeviceQuery($scope);

        return $query->count();
    }

    protected function getActiveAdminDevices()
    {
        $query = $this->getAdminDeviceQuery('active');

        return $query->get();
    }


    protected function countActiveAdminDevices()
    {
        $query = $this->getAdminDeviceQuery('active');

        return $query->count();
    }

    
    protected function getAdminDeviceQuery($scope = null)
    {
        $query = AdminDevice::query()
            ->where('user_id', Auth::id());

        if($scope) {
            $query->$scope();
        }

        return $query;
    }
    

    protected function setActiveCurrentAdminDevice(bool $expire_previous = true)
    {
        $current_device = $this->getCurrentAdminDevice();

        $current_device->update([
            "verified_at"   => now(),
            "expired_at"    => null,
        ]);

        return $current_device;
    }

}