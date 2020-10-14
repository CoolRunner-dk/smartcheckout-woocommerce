<?php
namespace SmartCheckoutSDK\Checkouts;

use SmartCheckoutSDK\Models\Filtering;

class Ecommerce
{
    protected $checkoutData;
    protected $filtering;
    protected $shop_token;

    public function __construct($data, $shop_token)
    {
        // Set data from webshop and where the products is saved local
        // If not array and is string, it might be JSON
        if(!is_array($data) AND is_string($data)) {
            $this->checkoutData = json_decode($data);
        } else {
            $this->checkoutData = $data;
        }

        // Read products and instantiate products
        $this->filtering = Filtering::getInstance();

        // CoolRunner authentication
        $this->shop_token = $shop_token;
    }

    public function validate_data()
    {
        return $this->filtering->validate($this->checkoutData, $this->shop_token);
    }
}