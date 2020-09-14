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

            $req = array(
                "sender" => array("name", "attention", "street1", "street2", "zip_code", "city", "country", "phone", "email"),
                "receiver" => array("name", "attention", "street1", "street2", "zip_code", "city", "country", "phone", "email", "notify_sms", "notify_email"),
                "length", "width", "height", "weight", "carrier", "carrier_product", "carrier_service", "reference", "description", "comment", "label_format", "servicepoint_id"
            );
        } elseif ($warehouse == "pcn") {
            // Define API properties
            $endpoint = "https://api.coolrunner.dk/pcn/order/create";
            $type = "POST";

            $req = array(
                "order_number", "receiver_name", "receiver_attention", "receiver_street1", "receiver_street2", "receiver_zipcode", "receiver_city", "receiver_country", "receiver_phone",
                "receiver_email", "receiver_phone", "receiver_email", "receiver_notify", "receiver_notify_sms", "receiver_notify_email", "droppoint_id", "droppoint_name", "droppoint_street1",
                "droppoint_street2", "droppoint_zipcode", "droppoint_city", "droppoint_country", "carrier", "carrier_product", "carrier_service", "reference", "description", "comment",
                "order_lines" => array("item_number", "qty")
            );
        }

        // Used to check if there is errors in data array
        $errors = [];

        foreach ($req as $field) {
            // Handle arrays
            if(is_array($field)) {
                foreach ($field as $single_field) {
                    if(!in_array($single_field, $data[$field])) {
                        $errors[] = "Error: " . $single_field ." is missing in " . $field;
                    }
                }
            } else {
                if(!in_array($field, $data)) {
                    $errors[] = "Error: " . $field ." is missing";
                }
            }
        }

        if(!empty($errors)) {
            return json_encode($errors);
        } else {
            $response = $this->curl($endpoint, $type, $data);
            return $response;
        }
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

    public function get_servicepoints($carrier, $country_code, $street, $zip_code, $city)
    {
        // Define API properties
        $endpoint_data = array(
            "country_code" => $country_code,
            "street" => $street,
            "zip_code" => $zip_code,
            "city" => $city
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
}