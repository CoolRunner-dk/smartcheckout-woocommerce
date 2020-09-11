<?php
namespace SmartCheckoutSDK;

use SmartCheckoutSDK\Checkouts\Ecommerce;
use SmartCheckoutSDK\Models\Products;

class Validate
{
    public function handle_ecommerce($checkoutData)
    {
        $checkout = new Ecommerce($checkoutData);
        $products = $checkout->validate_data();

        // Returns products or json with error
        return $products;
    }

    public function save_products_local()
    {
        $products = Products::getInstance();
        return $products->save_products();
    }

    public function endpoint_save($data)
    {
        $products = Products::getInstance();
        $products->endpoint_save($data);

        return true;
    }
}