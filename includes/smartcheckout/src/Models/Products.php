<?php
namespace SmartCheckoutSDK\Models;

class Products
{
    private static $instance;

    public static function getInstance()
    {
        if(!self::$instance)
        {
            self::$instance = new Products();
        }

        return self::$instance;
    }

    public function save_products() {
        // TEST - Has to be changed with real data
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://crshop.crdev.dk/endpoints/test.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);

        file_put_contents( __DIR__ . '/Data/products.json', $response);
        curl_close($curl);

        return true;
    }

    public function endpoint_save($data)
    {
        file_put_contents( __DIR__ . '/Data/products.json', $data);
    }

    public function get_products()
    {
        $jsonProducts = file_get_contents(__DIR__ . '/Data/products.json');

        return json_decode($jsonProducts);
    }

}