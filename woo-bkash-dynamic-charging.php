<?php
/**
 * Plugin Name:       bKash WooCommerce - Dynamic Charging
 * Description:       A bKash Dynamic Charging payment gateway plugin for WooCommerce.
 * Version:           1.0.0
 * Author:            bKash Limited
 * Author URI:        http://developer.bka.sh
 * Requires at least: 5.1
 * Tested up to:      6.4.1
 * Text Domain:       woo-bkash-dynamic-charging
 * Domain Path:       languages
 * Network:           false
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/bKash-developer/bKash-for-woocommerce
 * 
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package  woo-bkash-dynamic-charging
 * @author   bKash Limited
 * @category Payment
**/

namespace bKash\PGW\DC;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BKASH_DC_BASE_PATH', plugin_dir_path( __FILE__ ) );
define( 'BKASH_DC_BASE_URL', plugin_dir_url( __FILE__ ) );
define( 'BKASH_DC_PLUGIN_SLUG', 'woo-bkash-dynamic-charging' );
define( 'BKASH_DC_PLUGIN_VERSION', '1.0.0' );
define( 'BKASH_DC_PLUGIN_BASEPATH', plugin_basename( __FILE__ ) );

define( 'BKASH_DC_WC_API', '/wc-api/' );
define( 'BKASH_DC_COMPLETED_STATUS', 'Completed' );
define( 'BKASH_DC_CANCELLED_STATUS', 'Cancelled' );

require BKASH_DC_BASE_PATH . 'vendor/autoload.php';

if ( ! class_exists( 'WooCommerceBkashDC' ) ) {
    /**
     * WooCommerce bKash Payment Gateway main class.
     *
     * @class WooCommerceBkashDC
     */
    // echo "<pre>";
    // print_r(WooCommerceBkashDC::class);
        // exit;
    add_action( 'plugins_loaded', array( WooCommerceBkashDC::class, 'getInstance' ), 1 );
} // end if class exists.


if ( ! function_exists( 'WooCommerceBkashDC' ) ) {
    
     /** Returns the main instance of WooCommerceBkashDC to prevent the need to use globals.
     *
     * @return WooCommerceBkashDC
     */
     
    function WooCommerceBkashDC(): WooCommerceBkashDC {
        // print_r(WooCommerceBkashDC::getInstance());
        // exit;
        return WooCommerceBkashDC::getInstance();
    }
}


// register_activation_hook( __FILE__, 'WcBkashActivator' );

// function WcBkashActivator() {
//     $installed_version = get_option( BKASH_DC_PLUGIN_SLUG."_version" );
//     if ( $installed_version == BKASH_DC_PLUGIN_VERSION ) {
//         return true;
//     }
//     update_option( BKASH_DC_PLUGIN_SLUG.'_version', BKASH_DC_PLUGIN_VERSION );
// }




//  * The class itself, please note that it is inside plugins_loaded action hook
 
// add_action( 'plugins_loaded', 'bkashdc_init_gateway_class' );

// function bkashdc_init_gateway_class() {
//     require_once( BKASH_DC_BASE_PATH . 'includes/classes/WC_Geteway_bKash_Dc.php' );
// }

// add_filter( 'woocommerce_payment_gateways', 'bkashdc_add_gateway_class' );

// function bkashdc_add_gateway_class( $gateways ) {
//     $gateways[] = 'WC_Geteway_bKash_Dc'; 
//     return $gateways;
// }