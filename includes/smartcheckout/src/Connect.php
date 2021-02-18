<?php
namespace SmartCheckoutSDK;

class Connect
{
    public function connect($savedToken, $data)
    {
        $endpoint = "https://api.smartcheckout.coolrunner.dk?activation_token=" . $savedToken;

        if(is_array($data) OR is_object($data)) {
            $data = json_encode($data);
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
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
                'time' => $time
            ]);
        } else {
            return json_encode([
                'status' => 'disconnected',
                'time' => strtotime($time)
            ]);
        }
    }
}
