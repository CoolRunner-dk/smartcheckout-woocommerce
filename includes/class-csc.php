<?php

class CSC {
	/**
	 * @param int $order_id
	 *
	 * @return CoolRunner_Carrier|false
	 */
	public static function getCoolRunnerShippingMethod( $order_id ) {
		$order       = new WC_Order( $order_id );
		$config_name = '';
		foreach ( $order->get_shipping_methods() as $shipping_method ) {
			if ( $shipping_method->get_method_id() === 'coolrunner' ) {
				$config_name = implode( '_', array(
					'woocommerce',
					'coolrunner',
					$shipping_method->get_instance_id(),
					'settings'
				) );
				break;
			}
		}

		return $config_name ? new CoolRunner_Carrier( get_option( $config_name ), $shipping_method ) : false;
	}

	/**
	 * Get all carriers
	 *
	 * @return array
	 */
	public static function getCarriers( $key = null ) {
		$carriers = array(
            'dao'        => 'DAO',
            'pdk'        => 'Postnord',
            'gls'        => 'GLS',
            'coolrunner' => 'CoolRunner',
            'posti'      => 'Posti',
            'dhl'        => 'DHL',
            'helthjem'   => 'Helt Hjem',
            'bring'      => 'Bring'
		);

		return ! is_null( $key ) ? ( isset( $carriers[ $key ] ) ? $carriers[ $key ] : null ) : $carriers;
	}

	public static function getVersion() {
		return get_plugin_data( __DIR__ . '/../woocommerce_coolrunner.php' )['Version'];
	}

	public static function showDebugNotice( $contents ) {
		if ( self::debugEnabled() ) {
			$contents = ! is_array( $contents ) ? [ $contents ] : $contents;
			add_action( 'admin_notices', function () use ( $contents ) {
				?>
                <div class="notice notice-warning">
                    <h4 style="margin: .5em 0;">CoolRunner Debug Notice:</h4>
	                <?php foreach ( $contents as $content ) : ?>
                        <p>
			                <?php echo $content ?>
                        </p>
	                <?php endforeach; ?>
                    <p>
                        Disable CoolRunner Debug Notices:
	                    <?php echo sprintf( '<a href="/wp-admin/admin.php?page=csc-options">CoolRunner Settings</a>', get_site_url() ) ?>
                    </p>
                </div>
				<?php
			} );
		}
	}

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

	public static function debugEnabled() {
		return get_option( 'coolrunner_debug_mode' ) === '1';
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


	public static function defaultEmail() {
		ob_start(); ?>
        <table style='width:100%;height: 100vh'>
            <tr>
                <td align='center' style='background-color:#f7f7f7'>
                    <table style='width:600px'>
                        <tr>
                            <td style='background-color:#2B97D6;padding: 15px'>
                                <h1 style="color:#ffffff;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left">CoolRunner</h1>
                            </td>
                        </tr>
                        <tr>
                            <td style='background-color:#ffffff;padding: 15px'>
                                Hej {first_name}
                                <hr>
                                Vedrørende din ordre med nr. #{order_no} fra CoolRunner, så kan du følge den via dette link <a href="https://coolrunner.dk/">https://coolrunner.dk/</a><br>
                                Klik på fanen spor en pakke og indsæt dette
                                track and trace nummer:
                            </td>
                        </tr>
                        <tr>
                            <td align="center" style="background-color:#ffffff;padding: 15px;border-top: 2px solid #2B97D6;border-bottom: 2px solid #2B97D6">
                                <h3>{package_number}</h3>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" style="background-color: #ffffff">
                                <small>
                                    OBS! Denne e-mail kan ikke besvares!
                                </small>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
		<?php
		return ob_get_clean();
	}

	public static function getEmail() {
		return get_option( 'coolrunner_tracking_email', self::defaultEmail() );
	}

	public static function updateEmail( $content ) {
		return update_option( 'coolrunner_tracking_email', str_replace( [ '\\"', "\\'" ], [ '"', "'" ], $content ) );
	}
}

class CoolRunner_Carrier {
	protected $_carrier,
		$_product,
		$_service,
		$_title;

	/**
	 * CoolRunner_Carrier constructor.
	 *
	 * @param $arr
	 * @param $order_item WC_Order_Item_Shipping
	 */
	public function __construct( $arr, $order_item ) {

		$product = explode( '_', $arr['product'], 3 );

		$this->_carrier = $order_item->get_meta( 'carrier' );
		$this->_product = $order_item->get_meta( 'product' );
		$this->_service = $order_item->get_meta( 'service' );
		$this->_title   = $arr['title'];
	}

	/**
	 * @return mixed
	 */
	public function getProduct() {
		return $this->_product;
	}

	/**
	 * @return mixed
	 */
	public function getCarrier() {
		return $this->_carrier;
	}

	/**
 	 * @return mixed
	 */
	public function getTitle() {
		return $this->_title;
	}

	/**
	 * @return mixed
	 */
	public function getService() {
		return $this->_service;
	}
}