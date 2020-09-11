<?php
namespace SmartCheckoutSDK;

use SmartCheckoutSDK\Models\Products;

class Connect
{
    public function connect($savedToken, $recievedToken = 123456)
    {
        // This is supposed to setup the checkout when you connect a new shop.
        // This will install the needed informations from CoolRunner

        if($savedToken == $recievedToken) {
            Products::getInstance()->save_products();
        } else {
            print_r(['error' => 'Authentication', 'text' => 'The authentications tokens doesnt match - Please try again.']);
        }
    }

    public function ping($token, $savedToken, $platform, $website, $version)
    {
        // This will return data about the shop if its connected
        // Used to ping if the shop is still active

        $old_tz = date_default_timezone_get();
        date_default_timezone_set('Europe/Copenhagen');
        $time = strtotime(date('d-m-Y H:i:s'));
        date_default_timezone_set($old_tz);

        if($token == $savedToken) {
            return json_encode([
                'status' => 'connected',
                'platform' => $platform,
                'website' => $website,
                'version' => $version,
                'time' => strtotime($time)
            ]);
        } else {
            return json_encode([
                'status' => 'disconnected'
            ]);
        }
    }
}