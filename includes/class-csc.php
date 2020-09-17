<?php

class CSC {
	public static function showNotice( $contents, $type = 'error', $show_link = true ) {
		$contents = ! is_array( $contents ) ? [ $contents ] : $contents;

		add_action( 'admin_notices', function () use ( $contents, $type, $show_link ) {
			?>
            <div class="notice notice-<?php echo $type ?>">
                <h4 style="margin: .5em 0;">CoolRunner:</h4>
	            <?php foreach ( $contents as $content ) : ?>
                    <p>
			            <?php echo $content ?>
                    </p>
	            <?php endforeach; ?>
	            <?php if ( $show_link ) : ?>
                    <p>
			            <?php echo sprintf( '<a href="%s/wp-admin/admin.php?page=coolrunner-options">CoolRunner Settings</a>', get_site_url() ) ?>
                    </p>
	            <?php endif; ?>
            </div>
			<?php
		} );
	}

    public static function getCSCShippingMethod($order_id) {
        $order = new WC_Order($order_id);
        $config_name = '';
        foreach ($order->get_shipping_methods() as $shipping_method) {
            if($shipping_method->get_method_id() == "smartcheckout_shipping") {
                $shipping_data = array(
                    "name" => $shipping_method->get_name(),
                    "method_id" => $shipping_method->get_method_id(),
                    "method_price" => $shipping_method->get_total(),
                    "method_meta" => array(
                         "carrier" => $shipping_method->get_meta_data()[0]->get_data()['value'],
                         "product" => $shipping_method->get_meta_data()[1]->get_data()['value'],
                         "service" => $shipping_method->get_meta_data()[2]->get_data()['value']
                    )
                );

                if($shipping_data['method_meta']['service'] == 'droppoint') {
                    $droppoint_data = get_post_meta($order_id, '_csc_droppoint');

                    $shipping_data['droppoint_data'] = array(
                         "id" => $droppoint_data[0]['id'],
                         "name" => $droppoint_data[0]['name'],
                         "street" => $droppoint_data[0]['street'],
                         "city" => $droppoint_data[0]['city'],
                         "zip_code" => $droppoint_data[0]['zip_code'],
                         "country" => $droppoint_data[0]['country']
                    );
                }

                return $shipping_data;
            }
        }

        return false;
    }

	public static function formatUrl( $params = [] ) {
		$url = $_SERVER['QUERY_STRING'];

		$attrs = [];
		foreach ( explode( '&', $url ) as $part ) {
			list( $key, $value ) = explode( '=', $part );

			$attrs[ $key ] = $value;
		}

		foreach ( $params as $key => $value ) {
			$attrs[ $key ] = $value;
		}

		return http_build_query( $attrs );
	}

	public static function getBoxSizes() {
		return get_option( 'coolrunner_box_sizes', false ) !== false ? unserialize( get_option( 'coolrunner_box_sizes' ) ) : [];
	}

	public static function saveBoxSizes( array $sizes ) {
		update_option( 'coolrunner_box_sizes', serialize( $sizes ) );
	}

}