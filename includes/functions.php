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
                        <button id="create_label" name="create_label"><?php echo __('Create label', 'csc_textdomain'); ?></button>
                    </div>
                </div>
            </div>
            <div class="csc-shipment-line box-sizes">
                <div class="selectbox-fullwidth">
                    <select name="box-sizes" id="box-sizes">
                        <option><?php echo __('Pick a box size..', 'csc_textdomain'); ?></option>
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
                    <label for="csc_width"><?php echo __('Width:', 'csc_textdomain'); ?></label>
                    <input name="csc_width" id="csc_width" type="text" placeholder="ex. 10 cm">
                </div>
                <div>
                    <label for="csc_length"><?php echo __('Length:', 'csc_textdomain'); ?></label>
                    <input name="csc_length" id="csc_length" type="text" placeholder="ex. 10 cm">
                </div>
                <div>
                    <label for="csc_height"><?php echo __('Height:', 'csc_textdomain'); ?></label>
                    <input name="csc_height" id="csc_height" type="text" placeholder="ex. 10 cm">
                </div>
                <div>
                    <label for="csc_weight"><?php echo __('Weight:', 'csc_textdomain'); ?></label>
                    <input name="csc_weight" id="csc_weight" value="<?php echo get_order_weight($order->get_id()) ?>" type="text" placeholder="ex. 10 g">
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
                                    <div><a target="_blank" href="https://tracking.coolrunner.dk/?shipment=<?php echo $shipment['package_number']; ?>"><?php echo __('Track this shipment', 'csc_textdomain'); ?></a></div>
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
                                    <div><?php echo 'SID: ' . $shipment['shipment_id'] . ' - PID: ' . $shipment['pcn_pack_id'];  ?></div>
                                    <div><a target="_blank" href="https://tracking.coolrunner.dk/?shipment=<?php echo $shipment['package_number']; ?>"><?php echo __('Track this shipment', 'csc_textdomain'); ?></a></div>
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

// HS Codes
function product_shipping_options() {
    global $post_id, $post;

    if(empty($post_id)) {
        $post_id = $post->ID;
    }

    $countries = new WC_Countries();
    $select_options = $countries->__get('countries');

    woocommerce_wp_select([
        'id' => '_coolrunner_origin_country',
        'label' => __('Country of origin', 'csc_textdomain'),
        'description' => __('Country of origin for the product', 'csc_textdomain'),
        'desc_tip' => 'true',
        'options' => array(
                '' => __('Select country', 'csc_textdomain')
            ) + $select_options
    ]);

    woocommerce_wp_text_input([
        'id' => '_coolrunner_hs_code',
        'label' => __('HS Tariff code', 'csc_textdomain'),
        'description' => __('Harmonized tariff code.', 'csc_textdomain'),
        'desc_tip' => 'true',
        'placeholder' => __('Example 8774787', 'csc_textdomain')
    ]);

}
add_action('woocommerce_product_options_shipping', 'product_shipping_options', 8);

function save_product_shipping_options($post_id) {
    // Country of origin
    if (isset($_POST['_coolrunner_origin_country'])) {
        update_post_meta($post_id, '_coolrunner_origin_country', $_POST['_coolrunner_origin_country']);
    }

    // HS Code
    if (isset($_POST['_coolrunner_hs_code'])) {
        update_post_meta($post_id, '_coolrunner_hs_code', $_POST['_coolrunner_hs_code']);
    }
}
add_action('woocommerce_process_product_meta', 'save_product_shipping_options');

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

add_action( 'add_meta_boxes', function () {
    global $post, $post_type;
    if ( $post_type !== 'shop_order' ) {
        return;
    }

    $order = wc_get_order( $post->ID );
    $shipping_method = CSC::getCSCShippingMethod($order->get_id());

    if ( $shipping_method['method_id'] != 'smartcheckout_shipping' ) {
        return;
    }

    add_meta_box( 'csc_tracking', __( 'Tracking History', 'csc_textdomain' ), function () {
        global $post;
        $order       = new WC_Order( $post->ID );
        $cr_api = new \SmartCheckoutSDK\CoolRunnerAPI\API(get_option('csc_integration_email'), get_option('csc_integration_token'));
        $shipments = get_post_meta($order->get_id(), '_csc_shipments')[0];

        foreach ($shipments as $shipment): ?>
            <?php $tracking = json_decode($cr_api->get_tracking($shipment['package_number'])); ?>

            <div class="tracking_package">
                <div class="tracking_number">
                    <div><?php echo $tracking->package_number; ?></div>
                </div>
                <?php $events = array_reverse($tracking->events); ?>
                <?php foreach ($events as $event): ?>
                    <div class="tracking_event">
                        <div class="tracking_event_time"><?php echo $event->timestamp; ?></div>
                        <div class="tracking_event_text"><?php echo $event->title; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach;
    }, 'shop_order', 'side', 'core' );
}, 2 );

add_action( 'wp_ajax_csc_print_label', function () {
    if ( isset( $_POST['package_number'] ) ) {
        $cr_api = new SmartCheckoutSDK\CoolRunnerAPI\API(get_option('csc_integration_email'), get_option('csc_integration_token'));
        $label = $cr_api->get_label($_POST['package_number']);

        echo base64_encode($label);
    }

    wp_die();
} );

// Handle auto PCN  when order is on hold and the order got no shipments
function csc_handle_auto_pcn( $order_id ) {
    $handle_pcn = get_option('csc_pcn_auto');

    if($handle_pcn == 'yes' AND !isset(get_post_meta($order_id, '_csc_shipments')[0])) {
        $box_sizes = CSC::getBoxSizes();
        $box_primary = array();
        foreach ($box_sizes as $box_size) {
            if($box_size['primary'] == 1) {
                $box_primary = $box_size;
            }
        }
        $box_primary['weight'] = get_order_weight($order_id);
        csc_create_shipment($order_id, $box_primary);
    }
};
add_action( 'woocommerce_order_status_processing', 'csc_handle_auto_pcn');

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
        foreach ($order->get_items() as $item) {
            $prod = new WC_Order_Item_Product($item->get_id());
            if (!$prod->get_product()->is_virtual()) {
                $productArray = array(
                    'item_number' => $prod->get_product()->get_sku(),
                    'qty' => $item->get_quantity()
                );

                if(!empty(get_post_meta($item['product_id'], '_coolrunner_origin_country', true))) {

                    $tariff = get_post_meta($item['product_id'], '_coolrunner_origin_country', true);
                    $origin_country = get_post_meta($item['product_id'], '_coolrunner_hs_code', true);

                    $productArray['customs'] = array(
                        "description" => $prod->get_product()->get_name(),
                        "total_price" => $prod->get_subtotal(),
                        "currency_code" => "DKK",
                        "sender_tariff" => $tariff,
                        "receiver_tariff" => $tariff,
                        "weight" => $prod->get_product()->get_weight(),
                        "origin_country" => $origin_country
                    );
                }

                $array['order_lines'][] = $productArray;
            }
        }
    } elseif ($warehouse = "pcn") {
        $shipment_data = array(
            "order_number" => (string) $order->get_id(),
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

                if(!empty(get_post_meta($item['product_id'], '_coolrunner_origin_country', true))) {

                    $tariff = get_post_meta($item['product_id'], '_coolrunner_origin_country', true);
                    $origin_country = get_post_meta($item['product_id'], '_coolrunner_hs_code', true);

                    $productArray['customs'] = array(
                        "description" => $prod->get_product()->get_name(),
                        "total_price" => $prod->get_subtotal(),
                        "currency_code" => "DKK",
                        "sender_tariff" => $tariff,
                        "receiver_tariff" => $tariff,
                        "weight" => $prod->get_product()->get_weight(),
                        "origin_country" => $origin_country
                    );
                }

                $shipment_data['order_lines'][] = $productArray;
            }
        }
    }

    $cr_api = new SmartCheckoutSDK\CoolRunnerAPI\API(get_option('csc_integration_email'), get_option('csc_integration_token'));
    $response = $cr_api->create_shipment($shipment_data, $warehouse);
    $response = json_decode($response);

    if(isset($response->package_number)) {
        if($warehouse == 'normal') {
            if(empty(get_post_meta($order->get_id(), '_csc_shipments')[0])) {
                $shipments = array();
            } else {
                $shipments = get_post_meta($order->get_id(), '_csc_shipments')[0];
            }

            $old_tz = date_default_timezone_get();

            date_default_timezone_set('Europe/Copenhagen');
            $time = time();
            date_default_timezone_set($old_tz);

            if ( function_exists( 'wc_st_add_tracking_number' ) ) {
                wc_st_add_tracking_number( $order_id, $response->package_number, 'CoolRunner', $time, 'https://tracking.coolrunner.dk/?shipment='.$response->package_number );
            }

            $shipments[] = array(
                "package_number" => $response->package_number,
                "labelless_code" => (isset($response->labelless_code)) ? $response->labelless_code : '',
                "price_incl_tax" => $response->price->incl_tax,
                "price_excl_tax" => $response->price->excl_tax,
                "label" => $response->_links->label,
                "tracking" => $response->_links->tracking
            );

            if(!empty($shipments)) {
                update_post_meta($order->get_id(), '_csc_shipments', $shipments);
            }
        } elseif ($warehouse == 'pcn') {
            if(!get_post_meta($order->get_id(), '_csc_shipments')) {
                $shipments = array();
            } else {
                $shipments = get_post_meta($order->get_id(), '_csc_shipments');
            }

            $old_tz = date_default_timezone_get();

            date_default_timezone_set('Europe/Copenhagen');
            $time = time();
            date_default_timezone_set($old_tz);

            if ( function_exists( 'wc_st_add_tracking_number' ) ) {
                wc_st_add_tracking_number( $order_id, $response->package_number, 'CoolRunner', $time, 'https://tracking.coolrunner.dk/?shipment='.$response->package_number );
            }

            $shipments[] = array(
                "package_number" => $response->package_number,
                "shipment_id" => $response->shipment_id,
                "pcn_pack_id" => $response->pcn_pack_id
            );

            if(!empty($shipments)) {
                update_post_meta($order->get_id(), '_csc_shipments', $shipments);
            }
        }
    } else {
        $order->add_order_note('FEJL (CoolRunner): ' . $response->message);
    }

    return json_encode( $response );
}

// Handle droppoint
function csc_pickup_to_checkout() {
    ?>
    <div class="coolrunner_select_shop" data-carrier="" name="coolrunner_select_shop">
        <h3><?php echo __( 'Choose package shop', 'csc_textdomain' ); ?></h3>
        <p><?php echo __( 'Choose where you want your package to be dropped off', 'csc_textdomain' ); ?></p>

        <input type="hidden" name="coolrunner_carrier" id="coolrunner_carrier">
        <label for="coolrunner_zip_code_search" class=""><?php echo __( 'Input Zip Code', 'csc_textdomain' ); ?></label>
        <div class="zip-row">
            <div>
                <input class="input-text" type="text" id="coolrunner_zip_code_search" name="coolrunner_zip_code_search">
            </div>
            <div>
                <button style="width: 100%;" type="button" id="coolrunner_search_droppoints" name="coolrunner_search_droppoints">
                    <?php echo __( 'Search for package shop', 'csc_textdomain' ); ?>
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

    $coolrunner_api = new \SmartCheckoutSDK\CoolRunnerAPI\API(get_option('csc_integration_email'), get_option('csc_integration_token'));
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
                                <?php echo __( 'Distance', 'csc_textdomain' ) ?>: <?php echo number_format( intval( $entry->distance ) / 1000, 2 ) ?>km
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
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
    register_rest_route( 'smartcheckout/v1', '/update/(?P<token>\d+)', array(
        'methods' => 'POST',
        'callback' => 'csc_updateproducts',
        'permission_callback' => '__return_true'
    ));

    register_rest_route( 'smartcheckout/v1', '/ping/(?P<token>\d+)', array(
        'methods' => 'get',
        'callback' => 'csc_ping',
        'permission_callback' => '__return_true'
    ));
});

// Add column in order overview
function csc_status_custom_column( $columns ) {
    $offset = 8;
    foreach ( array_keys( $columns ) as $key => $value ) {
        if ( $value === 'order_total' ) {
            $offset = $key;
            break;
        }
    }
    $updated_columns = array_slice( $columns, 0, $offset, true ) +
        array(
            'csc_status' => esc_html__( 'CoolRunner', 'csc_textdomain' )
        ) +
        array_slice( $columns, $offset, null, true );

    return $updated_columns;
}
add_filter( 'manage_edit-shop_order_columns', 'csc_status_custom_column', 20 );

// Add label to column status
function csc_status_column( $column ) {
    global $post;

    if ( $column == 'csc_status' ) {
        if(isset(get_post_meta($post->ID, '_csc_shipments')[0])) {
            echo '<mark class="order-status status-completed coolrunner"><img src="/wp-content/plugins/coolrunner_smartcheckout/assets/images/print.png" height="20px" style="margin-top: 7px; margin-left: 7px; filter: brightness(0) invert(1);"><span>'.__('Sent', 'csc_textdomain'). ' ( '.count(get_post_meta($post->ID, '_csc_shipments')[0]).' labels )</span></mark>';
        } elseif(check_if_error($post->ID)) {
            echo '<mark class="order-status status-failed coolrunner"><img src="/wp-content/plugins/coolrunner_smartcheckout/assets/images/print.png" height="20px" style="margin-top: 7px; margin-left: 7px; filter: brightness(0) invert(1);"><span>'.__('Error creating shipment', 'csc_textdomain'). '</span></mark>';
        } else {
            echo '<mark class="order-status coolrunner"><img src="/wp-content/plugins/coolrunner_smartcheckout/assets/images/print.png" height="20px" style="margin-top: 7px; margin-left: 7px; filter: brightness(0) invert(1);"><span>'.__('No shipments', 'csc_textdomain'). '</span></mark>';
        }
    }
}
add_action( 'manage_shop_order_posts_custom_column', 'csc_status_column', 2 );

// Adding bulk action to print all orders
function csc_bulk_actions( $actions ) {
    $actions['print_all_orders'] = __( 'CoolRunner: Print labels', 'csc_textdomain' );
    $actions['create_shipments_orders'] = __( 'CoolRunner: Create labels', 'csc_textdomain' );
    return $actions;
}
add_filter( 'bulk_actions-edit-shop_order', 'csc_bulk_actions', 20, 1 );

// Merge PDFs and show these
function csc_bulk_print( $redirect_to, $action, $post_ids ) {
    include 'vendor/autoload.php';

    if ( $action !== 'print_all_orders' )
        return $redirect_to; // Exit

    $time = time();
    $count = 0;
    $pdf = new Clegginabox\PDFMerger\PDFMerger;

    foreach ( $post_ids as $post_id ) {
        $orderShipments = get_post_meta($post_id, '_csc_shipments')[0];

        foreach ($orderShipments as $shipment) {
            $cr_api = new SmartCheckoutSDK\CoolRunnerAPI\API(get_option('csc_integration_email'), get_option('csc_integration_token'));
            $shipmentLabel = $cr_api->get_label($shipment['package_number']);

            $file = CSC_PLUGIN_DIR . '/pdfs/' . $count . '-' . $post_id . '-' . $time . '.pdf';

            file_put_contents($file, $shipmentLabel);

            $pdf->addPDF($file);
            $count++;
        }
    }

    $merge = CSC_PLUGIN_DIR . '/pdfs/' . $time . '-merge' . '.pdf';
    $pdf->merge('browser', $merge, 'P');
}
add_filter( 'handle_bulk_actions-edit-shop_order', 'csc_bulk_print', 10, 3 );

// Create shipments using bulk action
function csc_bulk_create( $redirect_to, $action, $post_ids ) {
    if ( $action !== 'create_shipments_orders' )
        return $redirect_to; // Exit

    $processed_ids = array();

    // Find primary box and use that size
    $box_sizes = CSC::getBoxSizes();
    $box_primary = array();
    foreach ($box_sizes as $box_size) {
        if($box_size['primary'] == 1) {
            $box_primary = $box_size;
        }
    }

    // Create order for each post_id found using primary box
    if(!empty($box_primary)) {
        foreach ($post_ids as $post_id) {
            $box_primary['weight'] = get_order_weight($post_id);
            csc_create_shipment($post_id, $box_primary);
            $processed_ids[] = $post_id;
        }

        return $redirect_to = add_query_arg(array(
            'orders_created' => 1,
            'processed_count' => count($processed_ids),
            'processed_ids' => implode(',', $processed_ids),
        ), $redirect_to);
    } else {
        return $redirect_to = add_query_arg(array(
            'orders_created' => 0,
            'processed_count' => count($processed_ids),
            'processed_ids' => implode(',', $processed_ids),
            'no_primary_box' => 1
        ), $redirect_to);
    }
}
add_filter( 'handle_bulk_actions-edit-shop_order', 'csc_bulk_create', 10, 3 );

// Extra functions
function check_if_error($post_id) {
    $args = array(
        'post_id' => $post_id,
        'orderby' => 'comment_ID',
        'order'   => 'DESC',
        'approve' => 'approve',
        'type'    => 'order_note',
        'number'  => 10,
        'user_id' => 0
    );

    remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );
    $notes = get_comments( $args );
    add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

    $is_error = false;
    foreach ($notes as $note) {
        if (strpos($note->comment_content, 'FEJL (CoolRunner)') !== false && !isset(get_post_meta($post_id, '_csc_shipments')[0])) {
            $is_error = true;
        }
    }

    return $is_error;
}

function get_order_weight($post_id) {
    $order = new WC_Order($post_id);
    $total_weight = 0;

    foreach( $order->get_items() as $item_id => $product_item ){
        $quantity = $product_item->get_quantity(); // get quantity
        $product = $product_item->get_product(); // get the WC_Product object
        $product_weight = $product->get_weight(); // get the product weight

        if($product_weight == 0 OR $product_weight == '') {
            $product_weight = 1;
        }

        // Add the line item weight to the total weight calculation
        $total_weight += $product_weight * $quantity;
    }

    if($total_weight == 0) {
        $total_weight = 1;
    }

    $total_weight = $total_weight * 1000;

    return $total_weight;
}

// Cronjob to cleanup pdfs folder
function csc_cron_interval( $schedules ) {
    $schedules['everyweek'] = array(
        'interval' => 604800,
        'display' => esc_html__('Every week')
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'csc_cron_interval' );

function csc_cleanpdfs() {
    $dir = CSC_PLUGIN_DIR . 'pdfs/';

    foreach (glob($dir.'*.pdf') as $file) {
        unlink($file);
    }
}
add_action('wp_scheduled_csc_cleanpdfs', 'csc_cleanpdfs');

// Handle activation of plugin
register_activation_hook(CSC_PLUGIN_FILE, 'csc_activate');
function csc_activate() {
    wp_schedule_event(time(), 'everyweek', 'wp_scheduled_csc_cleanpdfs');
}

// Handle deactivation of plugin
register_deactivation_hook(CSC_PLUGIN_FILE, 'csc_deactivate');
function csc_deactivate() {
    wp_clear_scheduled_hook('wp_scheduled_csc_cleanpdfs');
}

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

function csc_install( $new_token ) {
    global $woocommerce;

    // Get shop data
    $shop_data = array(
        'activation_code' => $new_token,
        'name' => get_option("csc_storename"),
        'platform' => "WooCommerce",
        'version' => $woocommerce->version,
        'shop_url' => get_site_url(),
        'pingback_url' => get_site_url() . "/wp-json/smartcheckout/v1/ping/" . $new_token
    );

    // Connect to CoolRunner
    $smart_checkout = new \SmartCheckoutSDK\Connect();
    $connectResponse = json_decode($smart_checkout->connect($new_token, $shop_data));

    if($connectResponse->status == "ok") {
        update_option('csc_shop_token', $connectResponse->shop_info->shop_token);
        update_option('csc_integration_email', $connectResponse->shop_info->integration_email);
        update_option('csc_integration_token', $connectResponse->shop_info->integration_token);
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
