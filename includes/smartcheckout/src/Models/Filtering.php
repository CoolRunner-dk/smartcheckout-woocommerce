<?php
namespace SmartCheckoutSDK\Models;

class Filtering
{
    private static $instance;

    public static function getInstance()
    {
        if(!self::$instance)
        {
            self::$instance = new Filtering();
        }

        return self::$instance;
    }

    public function validate($data, $shop_token)
    {
        $curl = curl_init();

        $endpoint = "https://api.smartcheckout.coolrunner.dk?shop_token=" . $shop_token;

        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            )
        ));

        $response = curl_exec($curl);

        curl_close($curl);


        return $response;
    }
}