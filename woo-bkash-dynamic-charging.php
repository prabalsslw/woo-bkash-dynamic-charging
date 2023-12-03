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

use bKash\PGW\DC\Admin\AdminDashboard;

if ( ! class_exists( 'WooCommerceBkashDC' ) ) {
    add_action( 'plugins_loaded', array( WooCommerceBkashDC::class, 'getInstance' ), 1 );
}


if ( ! function_exists( 'WooCommerceBkashDC' ) ) {
    function WooCommerceBkashDC(): WooCommerceBkashDC {
        return WooCommerceBkashDC::getInstance();
    }
}

/**
 * Adding menus to wp admin menu and generating tables for this plugin
 */
$dashboard = new AdminDashboard();
$dashboard->initiate();