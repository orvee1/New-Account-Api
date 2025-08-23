<?php

namespace App\Http\Controllers;

use DeviceDetector\DeviceDetector;

abstract class Controller
{
    public function phpinfo()
    {
        return phpinfo();
    }

    public function laravelinfo()
    {
        return "Laravel v" . (\Illuminate\Foundation\Application::VERSION ?? '') . " (PHP v " . (PHP_VERSION ?? '') . ")";
    }

    protected function getDeviceBrowserStringFromUserAgent($user_agent = null, $clientHints = null)
    {
        // [
        //     "clientInfo" => $dd->getClient(),
        //     "osInfo" => $dd->getOs(),
        //     "device" => $dd->getDeviceName(),
        //     "brand" => $dd->getBrandName(),
        //     "model" => $dd->getModel(),
        // ]

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
