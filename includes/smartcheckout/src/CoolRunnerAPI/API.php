<?php

namespace SmartCheckoutSDK\CoolRunnerAPI;

class API
{
    private $authentication;

    public function __construct($username, $integration_token)
    {
        $authentication = base64_encode($username.':'.$integration_token);
        $this->authentication = $authentication;
    }

    public function curl($endpoint, $type, $data = [])
    {
        $curl = curl_init();
        $request = array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Basic " . $this->authentication,
                "X-Developer-Id: SmartCheckoutSDK"
            ),
        );

        if($type == "POST") {
            $request[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $request);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function create_shipment($data, $warehouse)
    {
        // Handle if the customer got own warehouse or PCN
        if($warehouse == "normal") {
            // Define API properties
            $endpoint = "https://api.coolrunner.dk/v3/shipments";
            $type = "POST";
        } elseif ($warehouse == "pcn") {
            // Define API properties
            $endpoint = "https://api.coolrunner.dk/pcn/order/create";
            $type = "POST";
        }

        $response = $this->curl($endpoint, $type, $data);
        return $response;
    }

    public function validate_address($data)
    {
        // Define API properties
        $endpoint = "https://api.coolrunner.dk/v3/shipments/address/validate";
        $type = "POST";

        $response = $this->curl($endpoint, $type, $data);
        return json_decode($response);
    }

    public function get_shipment($package_number)
    {
        // Define API properties
        $endpoint = "https://api.coolrunner.dk/v3/shipments/".$package_number;
        $type = "GET";

        $response = $this->curl($endpoint, $type);
        return $response;
    }

    public function get_label($package_number)
    {
        // Define API properties
        $endpoint = "https://api.coolrunner.dk/v3/shipments/".$package_number."/label";
        $type = "GET";

        $response = $this->curl($endpoint, $type);
        return $response;
    }

    public function get_tracking($package_number)
    {
        // Define API properties
        $endpoint = "https://api.coolrunner.dk/v3/shipments/".$package_number."/tracking";
        $type = "GET";

        $response = $this->curl($endpoint, $type);
        return $response;
    }

    public function get_servicepoints($carrier, $country_code, $street, $zip_code, $city, $limit = 5)
    {
        // Define API properties
        $endpoint_data = array(
            "country_code" => $country_code,
            "zip_code" => $zip_code,
            "city" => $city,
            "street" => $street,
            "limit" => $limit
        );

        $endpoint = "https://api.coolrunner.dk/v3/servicepoints/".$carrier . '?' . http_build_query($endpoint_data);
        $type = "GET";

        $response = $this->curl($endpoint, $type);
        return $response;
    }

    public function get_servicepoint_by_id($carrier, $servicepoint_id)
    {
        // Define API properties
        $endpoint = "https://api.coolrunner.dk/v3/servicepoints/".$carrier."/".$servicepoint_id;
        $type = "GET";

        $response = $this->curl($endpoint, $type);
        return $response;
    }

    public function get_products($country) {
        // Define API properties
        $endpoint = "https://api.coolrunner.dk/v3/products/" . $country;
        $type = "GET";

        $response = $this->curl($endpoint, $type);
        return $response;
    }
}