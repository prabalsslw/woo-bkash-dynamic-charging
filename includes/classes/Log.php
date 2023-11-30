<?php 

namespace bKash\PGW\DC;

use WC_Logger;

class Log {
	public static function debug( $str ) {
		self::writeLog( 'DEBUG: ' );
		self::writeLog( $str );
	}

	public static function writeLog( $str ) {
		if ( self::isDebug() === 'yes' ) {
			global $woocommerce;

			$logger = null;
			if ( class_exists( WC_Logger::class ) ) {
				$logger = new WC_Logger();
			} elseif ( ! empty( $woocommerce ) ) {
				$logger = $woocommerce->logger();
			}

			if ( $logger ) {
				$logger->add( 'bKash_DC_API_LOG', print_r( $str, true ) );
			} elseif ( true === WP_DEBUG ) {
				if ( is_array( $str ) || is_object( $str ) ) {
					error_log( print_r( $str, true ) );
				} else {
					error_log( $str );
				}
			}
		}
	}

	public static function isDebug() {
		$is_debug  = 'no';
		$plugin_id = BKASH_DC_PLUGIN_SLUG;
		$settings  = get_option( 'woocommerce_' . $plugin_id . '_settings' );
		if ( ! is_null( $settings ) ) {
			$is_debug = $settings['debug'] ?? 'no';
		}

		return $is_debug;
	}

	public static function info( $str ) {
		self::writeLog( 'INFO: ' );
		self::writeLog( $str );
	}

	public static function error( $str ) {
		self::writeLog( 'ERROR: ' );
		self::writeLog( $str );
	}
}