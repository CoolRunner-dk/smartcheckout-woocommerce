<?php
/**
 * Created by CoolRunner.
 * Developer: Kevin Hansen
 */

define( 'CSC_NAME', get_plugin_data( __DIR__ . '/../coolrunner_smartcheckout.php' )['Name'] );
define( 'CSC_VERSION', get_plugin_data( __DIR__ . '/../coolrunner_smartcheckout.php' )['Version'] );

// Add CoolRunner to navigation
add_action( 'admin_menu', function () {
    add_menu_page( 'CoolRunner - SmartCheckout', 'CoolRunner - SmartCheckout', 'manage_options', 'csc-options', function () {
        $section = isset( $_GET['section'] ) ? $_GET['section'] : 'settings';
        if ( in_array( $section, [ 'settings', 'box-sizes' ] ) ) {
            require_once plugin_dir_path( __FILE__ ) . "../pages/$section.php";
        }
    }, plugins_url( 'assets/images/coolrunner-logo.png', CSC_PLUGIN_FILE ) );
} );

// Open API endpoints
add_action( 'rest_api_init', function () {
    register_rest_route( 'coolrunner/v1', '/update/(?P<token>\d+)', array(
        'methods' => 'POST',
        'callback' => 'csc_updateproducts',
    ) );

    register_rest_route( 'coolrunner/v1', '/ping/(?P<token>\d+)', array(
        'methods' => 'get',
        'callback' => 'csc_ping',
    ) );
} );

// Function used by endpoint to update products
function csc_updateproducts( $data ) {
    $endpoint_data = $data->get_params();
    unset($endpoint_data['token']);

    // Maybe recieve products and token instead = less calls?
    if($data->get_param('token') == get_option('csc_token')) {
        $validate = new \SmartCheckoutSDK\Validate();
        $validate->endpoint_save(json_encode($endpoint_data));

        $old_tz = date_default_timezone_get();
        date_default_timezone_set('Europe/Copenhagen');
        $time = date('d-m-Y H:i:s');
        date_default_timezone_set($old_tz);

        update_option('last_product_save', date('d-m-y H:i:s'));
        echo json_encode(['status' => 'success', 'message' => 'Products got updated at ' . $time]);
    } else {
        echo json_encode(['status' => 'failed', 'message' => 'Authentication failed. Tokens didnt match.']);
    }
}

// Function used by endpoint
function csc_ping( $data ) {
    // Maybe recieve products and token instead = less calls?
    if($data->get_param('token') == get_option('csc_token')) {
        $smartcheckout = new SmartCheckoutSDK\Connect();

        echo $smartcheckout->ping($data->get_param('token'), get_option('csc_token'), 'WooCommerce', get_site_url(), CSC_PLUGIN_VERSION);
    } else {
        echo json_encode(['status' => 'failed', 'message' => 'Authentication failed. Tokens didnt match.']);
    }
}