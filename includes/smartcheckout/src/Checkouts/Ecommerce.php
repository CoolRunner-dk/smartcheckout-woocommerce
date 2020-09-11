<?php
namespace SmartCheckoutSDK\Checkouts;

use SmartCheckoutSDK\Models\Filtering;
use SmartCheckoutSDK\Models\Products;

class Ecommerce
{
    protected $checkoutData;
    protected $products;
    protected $filtering;

    public function __construct($data)
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
        $this->products = Products::getInstance()->get_products();
    }

    public function validate_data()
    {
        // Instantiate
        $allowedToValidate = true;
        $missingFields = [];
        $error = "";

        // Check if the fields exists in the array
        $requiredFields = ['customer_name', 'customer_address1', 'customer_address2', 'customer_country', 'customer_city', 'customer_zipcode',
            'customer_phone', 'customer_email', 'customer_company', 'cart_amount', 'cart_weight', 'cart_time',
            'cart_date', 'cart_day', 'cart_currency', 'cart_subtotal', 'cart_items', ];

        if(!empty($this->checkoutData)) {

            // Required field for cart_items
            $requiredItemsFields = ['item_name', 'item_sku', 'item_id', 'item_qty', 'item_weight', 'item_price'];

            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $this->checkoutData)) {
                    $allowedToValidate = false;
                    $missingFields[] = $field;
                    $error = "Error: Missing fields. Please add these fields to the checkout data";
                }
            }

            // Validate cart_items and content
            if(isset($this->checkoutData['cart_items'])) {
                if (!is_array($this->checkoutData['cart_items'])) {
                    $allowedToValidate = false;
                    $missingFields[] = 'cart_items';

                    if($error == '') {
                        $error = "The field cart_items needs to be an array. Please make this an array with needed fields";
                    }
                } else {
                    if(!empty($this->checkoutData['cart_items'])) {
                        foreach ($this->checkoutData['cart_items'] as $item) {
                            foreach ($requiredItemsFields as $field) {
                                if (!isset($item[$field])) {
                                    $allowedToValidate = false;
                                    $missingFields[] = $field;
                                    if ($error == '') {
                                        $error = 'Wrong format of a cart_item, please check these again. These fields are required';
                                    }
                                }
                            }
                        }
                    } else {
                        $allowedToValidate = false;
                        $error = 'cart_items is empty, please add cart items to this array';
                        $missingFields = 'cart_items';
                    }
                }
            }
        } else {
            $allowedToValidate = false;
            $error = 'checkoutData isnt set, and has to be either an array or json';
            $missingFields = 'checkoutData';
        }

        // If all fields set then validate else give error
        if(!$allowedToValidate AND !empty($missingFields)) {
            $returnValue = json_encode(["error" => $error, "fields" => $missingFields]);
        } else {
            $returnValue = array();

            foreach (json_decode($this->filtering->validate($this->checkoutData, $this->products)) as $product) {
                $returnValue[] = $product;
            }

            $returnValue = json_encode($returnValue);
        }

        return $returnValue;
    }
}