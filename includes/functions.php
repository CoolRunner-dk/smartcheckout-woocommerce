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

// Handle shipment on order page
function crship_package_information($order = null) {
    /** @var WC_Order $order */

    if ($shipping_method = CSC::getCSCShippingMethod($order->get_id())) {
        ?>
        <div id="csc-shipment-information">
            <div id="csc-shipment-loading"></div>
            <div class="csc-shipment-header">
                <div class="col1"><h3>CoolRunner</h3></div>
                <div class="col2">
                    <div class="csc-shiporder">
                        <button id="create_label" name="create_label">Opret label</button>
                    </div>
                </div>
            </div>
            <div class="csc-shipment-line box-sizes">
                <div class="selectbox-fullwidth">
                    <select name="box-sizes" id="box-sizes">
                        <option>Vælge kassestørrelse..</option>
                        <?php foreach ( CSC::getBoxSizes() as $size ) : ?>
                            <option data-height="<?php echo $size['height'] ?>"
                                    data-width="<?php echo $size['width'] ?>"
                                    data-length="<?php echo $size['length'] ?>"
                                    data-weight="<?php echo $size['weight'] ?>">
                                <?php echo $size['name'] ?>
                            </option>
                            <?php $primary = $size['primary'] ? $size : $primary; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" id="csc_orderid" name="csc_orderid" value="<?php echo $order->get_id(); ?>">
                <div>
                    <label for="csc_width">Bredde:</label>
                    <input name="csc_width" id="csc_width" type="text" placeholder="ex. 10 cm">
                </div>
                <div>
                    <label for="csc_length">Længde:</label>
                    <input name="csc_length" id="csc_length" type="text" placeholder="ex. 10 cm">
                </div>
                <div>
                    <label for="csc_height">Højde:</label>
                    <input name="csc_height" id="csc_height" type="text" placeholder="ex. 10 cm">
                </div>
                <div>
                    <label for="csc_weight">Vægt:</label>
                    <input name="csc_weight" id="csc_weight" type="text" placeholder="ex. 10 g">
                </div>
            </div>
            <div class="csc-shipment-body">
                <?php if(isset($shipping_method['droppoint_data'])): ?>
                    <div class="csc-shipment-line">
                        <div class="csc-shipment-data" style="margin-left: 5px;">
                            <div><b><?php echo $shipping_method['name']; ?></b></div>
                            <div><?php echo $shipping_method['droppoint_data']['name']; ?></div>
                            <div><?php echo $shipping_method['droppoint_data']['street'] . ', ' . $shipping_method['droppoint_data']['zip_code'] . ' ' . $shipping_method['droppoint_data']['city']; ?></div>
                        </div>
                        <div class="csc-shipment-price">
                            <div><b>Subtotal</b></div>
                            <div><?php echo $shipping_method['method_price']; ?> DKK</div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if(isset(get_post_meta($order->get_id(), '_csc_shipments')[0])): ?>
                    <?php foreach (get_post_meta($order->get_id(), '_csc_shipments')[0] as $shipment): ?>
                        <?php if(get_option('csc_warehouse') == 'normal'): ?>
                            <div class="csc-shipment-line">
                                <div class="csc-shipment-image"><img class="printlabel" data-packagenumber="<?php echo $shipment['package_number']; ?>" src="/wp-content/plugins/coolrunner_smartcheckout/assets/images/pdf.png" height="35px"></div>
                                <div class="csc-shipment-data">
                                    <div><b><?php echo $shipment['package_number']; ?></b></div>
                                    <div><?php if($shipment['labelless_code'] != '') { echo $shipment['labelless_code']; } else { echo 'Ingen labellelss kode'; } ?></div>
                                    <div><a target="_blank" href="https://tracking.coolrunner.dk/?shipment=<?php echo $shipment['package_number']; ?>">Track denne forsendelse</a></div>
                                </div>
                                <div class="csc-shipment-price">
                                    <div><b>Subtotal</b></div>
                                    <div>-<?php echo number_format($shipment['price_incl_tax'], 2, '.', ''); ?> DKK</div>
                                </div>
                            </div>
                        <?php elseif (get_option('csc_warehouse') == 'pcn'): ?>
                            <div class="csc-shipment-line">
                                <div class="csc-shipment-image"><img src="/wp-content/plugins/coolrunner_smartcheckout/assets/images/print.png" height="30px"></div>
                                <div class="csc-shipment-data">
                                    <div><b><?php echo $shipment['package_number']; ?></b></div>
                                    <div><?php echo $shipment['shipment_id'] . ' - ' . $shipment['pcn_pack_id'];  ?></div>
                                    <div><a target="_blank" href="https://tracking.coolrunner.dk/?shipment=<?php echo $shipment['package_number']; ?>">Track denne forsendelse</a></div>
                                </div>
                                <div class="csc-shipment-price">
                                    <div><b>Subtotal</b></div>
                                    <div>-<?php echo $shipment['price_incl_tax']; ?> DKK</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <script>
            jQuery(function ($) {
                let crmeta = $('#csc-shipment-information'),
                    createBtn = crmeta.find('#create_label'),
                    sizeSelect = crmeta.find('#box-sizes'),
                    printLabel = crmeta.find('#printlabel');

                sizeSelect.on('change input', function () {
                    let option = $(this).find(':selected'),
                        props = ['height', 'length', 'width'];

                    props.forEach(function (e) {
                        // console.log(e);
                        crmeta.find('[name=csc_' + e + ']').val(option.attr('data-' + e));
                    })
                });

                // Handle click on create label button
                createBtn.on('click', function () {
                    crmeta.fadeTo("slow", 0.55);

                    let width = $('#csc_width').val(),
                        height = $('#csc_height').val(),
                        length = $('#csc_length').val(),
                        weight = $('#csc_weight').val(),
                        order_id = $('#csc_orderid').val();

                    let data = {
                        action: 'csc_create_shipment',
                        width: width,
                        height: height,
                        length: length,
                        weight: weight,
                        order_id: order_id
                    };

                    $.ajax({
                        method: 'post',
                        url: ajaxurl,
                        data: data,
                        success: function (data) {
                            console.log(data);
                        },
                        error: function (data) {
                            console.log(data);
                        }
                    })
                });

                // Handle every click on div with class printlabel
                $('[class^="printlabel"]').click(function() {
                    let data = {
                        action: 'csc_print_label',
                        package_number: $(this).attr("data-packagenumber")
                    };

                    console.log($(this).attr("data-packagenumber"));

                    $.ajax({
                        method: 'post',
                        url: ajaxurl,
                        data: data,
                        success: function (data) {
                            const win = window.open("","_blank");
                            let html = '';

                            html += '<html>';
                            html += '<body style="margin:0!important">';
                            html += '<embed width="100%" height="100%" src="data:application/pdf;base64,'+data+'" type="application/pdf" />';
                            html += '</body>';
                            html += '</html>';

                            setTimeout(() => {
                                win.document.write(html);
                            }, 0);
                        },
                        error: function (data) {
                            console.log('base64 failed: ' + data);
                        }
                    })
                });

            });

        </script>
        <?php
    }
}
add_action('woocommerce_admin_order_data_after_shipping_address', 'crship_package_information', 10, 1);

// Handle ajax call
add_action( 'wp_ajax_csc_create_shipment', function () {
    if ( isset( $_POST['order_id'] ) && $order_id = $_POST['order_id'] ) {
        $size = [
            'height' => sanitize_text_field($_POST['height']),
            'width'  => sanitize_text_field($_POST['width']),
            'length' => sanitize_text_field($_POST['length']),
            'weight' => sanitize_text_field($_POST['weight']),
        ];

        $response = csc_create_shipment( $order_id , $size );

        return $response;
    }

    wp_die();
} );

add_action( 'wp_ajax_csc_print_label', function () {
    if ( isset( $_POST['package_number'] ) ) {
        error_log($_POST['package_number']);
        $cr_api = new SmartCheckoutSDK\CoolRunnerAPI\API(get_option('coolrunner_username'), get_option('coolrunner_token'));
        $label = $cr_api->get_label($_POST['package_number']);

        echo base64_encode($label);
    }

    wp_die();
} );

// Create shipment
function csc_create_shipment($order_id = null, $size = null) {
    $warehouse = get_option('csc_warehouse');
    $order = new WC_Order($order_id);
    $shipping_method = CSC::getCSCShippingMethod($order->get_id());

    // Create shipment array
    if($warehouse == "normal") {
        $shipment_data = array(
             "sender" => array(
                 "name" => get_option('csc_storename'),
                 "attention" => "",
                 "street1" => WC()->countries->get_base_address(),
                 "street2" => WC()->countries->get_base_address_2(),
                 "zip_code" => WC()->countries->get_base_postcode(),
                 "city" => WC()->countries->get_base_city(),
                 "country" => WC()->countries->get_base_country(),
                 "phone" => get_option( 'woocommerce_store_phone' ),
                 "email" => get_option( 'woocommerce_store_email' )
             ),
            "receiver" => array(
                "name" => ($order->get_shipping_company() != '') ? $order->get_shipping_company() : $order->get_formatted_shipping_full_name(),
                "attention" => ($order->get_shipping_company() != '') ? $order->get_formatted_shipping_full_name() : "",
                "street1" => $order->get_shipping_address_1(),
                "street2" => $order->get_shipping_address_2(),
                "zip_code" => $order->get_shipping_postcode(),
                "city" => $order->get_shipping_city(),
                "country" => $order->get_shipping_country(),
                "phone" => $order->get_billing_phone(),
                "email" => $order->get_billing_email(),
                "notify_sms" => $order->get_billing_phone(),
                "notify_mail" => $order->get_billing_email()
            ),
            "length" => $size['length'],
            "width" => $size['width'],
            "height" => $size['height'],
            "weight" => $size['weight'],
            "carrier" => $shipping_method['method_meta']['carrier'],
            "carrier_product" => $shipping_method['method_meta']['product'],
            "carrier_service" => $shipping_method['method_meta']['service'],
            "reference" => $order->get_id(),
            "description" => "",
            "comment" => "",
            "label_format" => "LabelPrint"
        );

        if(isset($shipping_method['droppoint_data'])) {
            $shipment_data['servicepoint_id'] = $shipping_method['droppoint_data']['id'];
        }

        // Handle orderlines
        if($order->get_shipping_country() == 'NO') {
            foreach ($order->get_items() as $item) {
                $prod = new WC_Order_Item_Product($item->get_id());
                if (!$prod->get_product()->is_virtual()) {
                    $productArray = array(
                        'item_number' => $prod->get_product()->get_sku(),
                        'qty' => $item->get_quantity()
                    );

                    $productArray['customs'] = array(
                        "description" => $prod->get_product()->get_name(),
                        "total_price" => $prod->get_subtotal(),
                        "currency_code" => "NOK",
                        "sender_tariff" => $prod->get_product()->get_attribute('hs-tariff'),
                        "receiver_tariff" => $prod->get_product()->get_attribute('hs-tariff'),
                        "weight" => $prod->get_product()->get_weight(),
                        "origin_country" => "DK"
                    );

                    $array['order_lines'][] = $productArray;
                }
            }
        }
    } elseif ($warehouse = "pcn") {
        $shipment_data = array(
            "order_number" => $order->get_id(),
            "receiver_name" => ($order->get_shipping_company() != '') ? $order->get_shipping_company() : $order->get_formatted_shipping_full_name(),
            "receiver_attention" => ($order->get_shipping_company() != '') ? $order->get_formatted_shipping_full_name() : "",
            "receiver_street1" => $order->get_shipping_address_1(),
            "receiver_street2" => $order->get_shipping_address_2(),
            "receiver_zipcode" => $order->get_shipping_postcode(),
            "receiver_city" => $order->get_shipping_city(),
            "receiver_country" => $order->get_shipping_country(),
            "receiver_phone" => $order->get_billing_phone(),
            "receiver_email" => $order->get_billing_email(),
            "receiver_notify" => true,
            "receiver_notify_sms" => $order->get_billing_phone(),
            "receiver_notify_email" => $order->get_billing_email(),
            "droppoint_id" => $shipping_method['droppoint_data']['id'],
            "droppoint_name" => $shipping_method['droppoint_data']['name'],
            "droppoint_street1" => $shipping_method['droppoint_data']['street'],
            "droppoint_zipcode" => $shipping_method['droppoint_data']['zip_code'],
            "droppoint_city" => $shipping_method['droppoint_data']['city'],
            "droppoint_country" => $shipping_method['droppoint_data']['country'],
            "carrier" => $shipping_method['method_meta']['carrier'],
            "carrier_product" => $shipping_method['method_meta']['product'],
            "carrier_service" => $shipping_method['method_meta']['service'],
            "reference" => $order->get_id(),
            "description" => "",
            "comment" => ""
        );

        // Handle orderlines
        foreach ($order->get_items() as $item) {
            $prod = new WC_Order_Item_Product($item->get_id());
            if (!$prod->get_product()->is_virtual()) {
                $productArray = array(
                    'item_number' => $prod->get_product()->get_sku(),
                    'qty'         => $item->get_quantity()
                );

                if($order->get_shipping_country() == 'NO') {
                    $productArray['customs'] = array(
                        "description" => $prod->get_product()->get_name(),
                        "total_price" => $prod->get_subtotal(),
                        "currency_code" => "NOK",
                        "sender_tariff" => $prod->get_product()->get_attribute('hs-tariff'),
                        "receiver_tariff" => $prod->get_product()->get_attribute('hs-tariff'),
                        "weight" => $prod->get_product()->get_weight(),
                        "origin_country" => "DK"
                    );
                }

                $array['order_lines'][] = $productArray;
            }
        }
    }

    $cr_api = new SmartCheckoutSDK\CoolRunnerAPI\API(get_option('coolrunner_username'), get_option('coolrunner_token'));
    $response = $cr_api->create_shipment($shipment_data, $warehouse);
    $response = json_decode($response);

    if(isset($response->package_number)) {
        if($warehouse == 'normal') {
            if(empty(get_post_meta($order->get_id(), '_csc_shipments')[0])) {
                $shipments = array();
            } else {
                $shipments = get_post_meta($order->get_id(), '_csc_shipments')[0];
            }

            $shipments[] = array(
                "package_number" => $response->package_number,
                "labelless_code" => (isset($response->labelless_code)) ? $response->labelless_code : '',
                "price_incl_tax" => $response->price->incl_tax,
                "price_excl_tax" => $response->price->excl_tax,
                "label" => $response->_links->label,
                "tracking" => $response->_links->tracking
            );

            update_post_meta($order->get_id(), '_csc_shipments', $shipments);
        } elseif ($warehouse == 'pcn') {
            if(!get_post_meta($order->get_id(), '_csc_shipments')) {
                $shipments = array();
            } else {
                $shipments = get_post_meta($order->get_id(), '_csc_shipments');
            }

            $shipments[] = array(
                "package_number" => $response->package_number,
                "shipment_id" => $response->shipment_id,
                "pcn_pack_id" => $response->pcn_pack_id
            );

            update_post_meta($order->get_id(), '_csc_shipments', $shipments);
        }
    } else {
        $order->add_order_note('FEJL (CoolRunner): ' . $response->message);
    }

    header( 'Content-Type: application/json' );
    echo json_encode( $response );
}

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
                'street' => $entry->address->street,
                'zip_code' => $entry->address->zip_code,
                'city' => $entry->address->city,
                'country' => $entry->address->country_code
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

add_action('woocommerce_checkout_update_order_meta', 'csc_add_order_droppoint', 10, 2);
function csc_add_order_droppoint($order_id, $posted) {
    if (isset($_POST['coolrunner_droppoint'])) {
        update_post_meta($order_id, '_csc_droppoint', json_decode(base64_decode($_POST['coolrunner_droppoint']), true));
    }
}

// Open API endpoints
add_action( 'rest_api_init', function () {
    register_rest_route( 'coolrunner/v1', '/update/(?P<token>\d+)', array(
        'methods' => 'POST',
        'callback' => 'csc_updateproducts',
        'permission_callback' => '__return_true'
    ) );

    register_rest_route( 'coolrunner/v1', '/ping/(?P<token>\d+)', array(
        'methods' => 'get',
        'callback' => 'csc_ping',
        'permission_callback' => '__return_true'
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