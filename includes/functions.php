<?php
/**
 * Created by CoolRunner.
 * Developer: Kevin Hansen
 */

// Add CoolRunner to navigation
add_action( 'admin_menu', function () {
    add_menu_page( 'CoolRunner - SmartCheckout', 'CoolRunner - SmartCheckout', 'manage_options', 'csc-options', function () {
        $section = isset( $_GET['section'] ) ? $_GET['section'] : 'settings';
        if ( in_array( $section, [ 'settings', 'box-sizes' ] ) ) {
            require_once plugin_dir_path( __FILE__ ) . "../pages/$section.php";
        }
    }, plugins_url( 'assets/images/coolrunner-logo.png', CSC_PLUGIN_FILE ) );
} );

// Handle droppoint
function csc_pickup_to_checkout() {
    ?>
    <div class="coolrunner_select_shop" data-carrier="" name="coolrunner_select_shop">
        <h3><?php echo __( 'Choose package shop', CSC_TEXTDOMAIN ); ?></h3>
        <p><?php echo __( 'Choose where you want your package to be dropped off', CSC_TEXTDOMAIN ); ?></p>

        <input type="hidden" name="coolrunner_carrier" id="coolrunner_carrier">
        <label for="coolrunner_zip_code_search" class=""><?php echo __( 'Input Zip Code', CSC_TEXTDOMAIN ); ?></label>
        <div class="zip-row">
            <div>
                <input class="input-text" type="text" id="coolrunner_zip_code_search" name="coolrunner_zip_code_search">
            </div>
            <div>
                <button style="width: 100%;" type="button" id="coolrunner_search_droppoints" name="coolrunner_search_droppoints">
                    <?php echo __( 'Search for package shop', CSC_TEXTDOMAIN ); ?>
                </button>
            </div>
        </div>
        <div class="clear"></div>
        <div class="coolrunner-droppoints">
            <!-- Used for showing all the droppoints -->
        </div>
    </div>
    <?php
}
add_action( 'woocommerce_review_order_before_payment', 'csc_pickup_to_checkout' );

function csc_droppoint_search() {
    global $woocommerce;

    $coolrunner_api = new \SmartCheckoutSDK\CoolRunnerAPI\API(get_option('coolrunner_username'), get_option('coolrunner_token'));
    $response = $coolrunner_api->get_servicepoints($_POST['carrier'], $_POST['country'], isset( $_POST['street'] ) ? $_POST['street'] : null, $_POST['zip_code'], isset( $_POST['city'] ) ? $_POST['city'] : null);
    $response = json_decode($response);
    $radios = array();

    if ( !empty( $response->servicepoints ) ) {
        foreach ( $response->servicepoints as $entry ) {
            ob_start();

            $props = array(
                'id'      => $entry->id,
                'name'    => $entry->name,
                'address' => $entry->address->street . ', ' . $entry->address->zip_code
            );

            ?>
            <label>
                <table style="margin: 0;">
                    <colgroup>
                        <col width="1">
                        <col>
                    </colgroup>
                    <tr>
                        <td>
                            <div class="cr-check">
                                <input required type="radio" name="coolrunner_droppoint" value='<?php echo base64_encode( json_encode( $props ) ) ?>'>
                            </div>
                        </td>
                        <td>
                            <b><?php echo $entry->name ?></b>
                            <div>
                                <?php printf( '%s, %s-%s %s', $entry->address->street, $entry->address->country_code, $entry->address->zip_code, $entry->address->city ) ?>
                            </div>
                            <?php if ( $_POST['city'] && $_POST['street'] ) : ?>
                                <div>
                                    <?php echo __( 'Distance', CSC_TEXTDOMAIN ) ?>: <?php echo number_format( intval( $entry->distance ) / 1000, 2 ) ?>km
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </label>
            <?php
            $radios[] = ob_get_clean();
        }

        echo implode( $radios );
    } else {
        echo "No Droppoints were found";
    }
    exit();
}
add_action( 'wp_ajax_nopriv_coolrunner_droppoint_search', 'csc_droppoint_search' );
add_action( 'wp_ajax_coolrunner_droppoint_search', 'csc_droppoint_search' );

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