<?php
/**
 * Plugin Name: CoolRunner - SmartCheckout
 * Plugin URI: https://coolrunner.dk/customer/integrations
 * Description: With CoolRunner SmartCheckout you will be able to setup rules, conditions and much more for your shipping so you are able to provide the best possible solution for your customer.
 * Version: 0.5.0
 * Author: CoolRunner
 * Author URI: https://coolrunner.dk
 * Developer: Kevin Steen Hansen / CoolRunner
 * Developer URI: https://coolrunner.dk
 * Text Domain: csc_textdomain
 * Domain Path: /languages
 *
 * WC requires at least: 3.4.2
 * WC tested up to: 3.4.2
 *
 * Copyright: © 2018- CoolRunner.dk
 * License: MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'CSC_WOOCOMMERCE_VERSION', '1.1' );
define( 'CSC_PLUGIN_FILE', __FILE__ );
define( 'CSC_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'CSC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSC_PLUGIN_VERSION', '0.5.0' );

// Check if WooCommerce is installed - This is required for this plugin
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	add_action( 'admin_notices', function () {
		?>
        <div class="notice notice-warning">
            <p><?php _e( 'CoolRunner SmartCheckout requires WooCommerce to be installed and active.', 'csc_textdomain' ); ?></p>
            <p><?php _e( 'You download WooCommerce here:', 'csc_textdomain' ); ?><?php echo sprintf( '<a href="%s/wp-admin/plugin-install.php?s=WooCommerce&tab=search&type=term">Download</a>', get_site_url() ) ?></p>
        </div>
		<?php
	} );

	return;
} else {
    // Add translations to plugin
	add_action( 'plugins_loaded', 'csc_load_textdomain' );
	function csc_load_textdomain() {
		load_plugin_textdomain( 'csc_textdomain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	// Add links on plugins overview page
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'crship_action_links' );
	function crship_action_links( $links ) {
		$links[] = '<a href="' . admin_url( 'admin.php?page=csc-options' ) . '">' . __( 'Settings', 'csc_textdomain' ) . '</a>';
		$links[] = '<a href="https://coolrunner.dk/om-coolrunner/" target="_blank">' . __( 'Read more about CoolRunner', 'csc_textdomain' ) . '</a>';

		return $links;
	}

	// Add CSS to checkout
	add_action( 'wp_enqueue_scripts', function () {
		if ( is_checkout() ) {
			wp_enqueue_style( 'csc', plugins_url( '/assets/css/csc.css', __FILE__ ), array(), CSC_WOOCOMMERCE_VERSION );
			wp_enqueue_script( 'csc', plugins_url( '/assets/js/csc.js', __FILE__ ), array( 'jquery' ), CSC_WOOCOMMERCE_VERSION, true );

			wp_localize_script( 'csc', 'csc', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'lang'     => array(
					'droppoint_searching' => __( 'Searching for droppoints!', 'csc_textdomain' )
				)
			) );
		}
	} );

	// Add CSS to admin
	add_action( 'admin_enqueue_scripts', function () {
		wp_enqueue_style( 'csc', plugins_url( '/assets/css/admin-csc.css', __FILE__ ), array(), CSC_WOOCOMMERCE_VERSION );
		wp_enqueue_script( 'csc', plugins_url( '/assets/js/admin.js', __FILE__ ), array( 'jquery' ), CSC_WOOCOMMERCE_VERSION, true );
	});

	// Add JS to checkout
    add_action( 'wp_enqueue_scripts', function () {
        if ( is_checkout() ) {
            wp_enqueue_script( 'csc', plugins_url( '/assets/js/csc.js', __FILE__ ), array( 'jquery' ), CSC_WOOCOMMERCE_VERSION, true );

            wp_localize_script( 'csc', 'csc', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'lang'     => array(
                    'droppoint_searching' => __( 'Searching for droppoints!', 'csc_textdomain' )
                )
            ) );
        }
    } );

    // Handle rates
    add_action('woocommerce_shipping_init', function () {
        if(class_exists('SmartCheckoutRates')) {
            return;
        }
        require_once 'SmartCheckoutRates.php';
    });

    add_filter('woocommerce_shipping_methods', function ($methods) {
        $methods['CSC'] = 'SmartCheckoutRates';
        return $methods;
    });

    include( CSC_PLUGIN_DIR . 'includes/functions.php' );
    include( CSC_PLUGIN_DIR . 'includes/class-csc.php' );
    include( CSC_PLUGIN_DIR . 'includes/smartcheckout/includes.php' );
}