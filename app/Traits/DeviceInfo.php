<?php

namespace App\Traits;

use DeviceDetector\DeviceDetector;

trait DeviceInfo
{
    public static function checkIsSmartPhone($user_agent = null, $clientHints = null)
    {
        $user_agent = $user_agent ?? $_SERVER['HTTP_USER_AGENT'];
        
        $device_detector = new DeviceDetector($user_agent, $clientHints);

        $device_detector->parse();

        return $device_detector->isSmartphone();
    }

    public static function getDeviceName($user_agent = null, $clientHints = null)
    {
        $user_agent = $user_agent ?? $_SERVER['HTTP_USER_AGENT'];
        
        $device_detector = new DeviceDetector($user_agent, $clientHints);

        $device_detector->parse();

        $data_array = [
            trim($device_detector->getBrandName() . " " . $device_detector->getModel()),
            $device_detector->getOs("name") . " " . $device_detector->getOs("version"),
            $device_detector->getClient("family") . " " . $device_detector->getClient("version"),
        ];

        // remove empty value
        $data_array = array_filter($data_array);

        $device_browser_name = implode(" . ", $data_array);

        return $device_browser_name;
    }
}