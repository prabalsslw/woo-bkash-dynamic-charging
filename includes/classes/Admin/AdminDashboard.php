<?php
/**
 * Admin Dashboard
 *
 * @category    Admin
 * @package     woo-bkash-dynamic-charging
 * @author      bKash Developer <developer@bkash.com>
 * @copyright   Copyright 2023 bKash Limited. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://bkash.com
 */

namespace bKash\PGW\DC\Admin;

// use bKash\PGW\DC\Admin\Module\AgreementModule;
use bKash\PGW\DC\Admin\Module\TransactionModule;
// use bKash\PGW\DC\Admin\Module\TransferModule;
// use bKash\PGW\DC\Admin\Module\WebhookModule;
use bKash\PGW\DC\TablesGenerator;

define( "BKASH_DC_PGW_VERSION", "v1" );
define( "BKASH_DC_TABLE_LIMIT", 10 );
define( "BKASH_DC_ADMIN_PAGE_SLUG", 'bkash_admin_menu_v1' );

class AdminDashboard {
	private static $instance;

	final public static function getInstance(): AdminDashboard {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return void
	 * */
	final public function pluginMenu() {
		/* Adding menu and sub-menu to the admin portal */
		$this->AddMainMenu();
		$this->AddSubMenus();
	}

	/**
	 * Add menu for bKash PGW in WP Admin
	 */
	private function addMainMenu() {
		add_menu_page(
			'Woocommerce Payment Gateway - bKash',
			'bKash',
			'manage_options',
			BKASH_DC_ADMIN_PAGE_SLUG,
			array( TransactionModule::class, 'transactionList' ),
			plugins_url( '../../assets/images/bkash_favicon_0.ico', __DIR__ )
		);
	}

	/**
	 * Add submenu for bKash PGW in WP Admin
	 */
	private function addSubMenus() {
		$pid                = BKASH_DC_PLUGIN_SLUG;
		$is_b2c_enabled     = AdminUtility::getBKashOptions( $pid, 'enable_b2c' );
		$is_webhook_enabled = AdminUtility::getBKashOptions( $pid, 'webhook' );
		$integration_type   = AdminUtility::getBKashOptions( $pid, 'integration_type' );

		$sub_menus = array(
			array(
				'title'      => 'All Transaction',
				'menu_title' => 'Transaction',
				'route'      => '',
				'function'   => array( TransactionModule::class, 'transactionList' ),
				'show'       => true
			),
			array(
				'title'      => 'Search a bKash Transaction',
				'menu_title' => 'Search',
				'route'      => '/search',
				'function'   => array( TransactionModule::class, 'transactionSearch' ),
				'show'       => true
			),
			array(
				'title'      => 'Refund a bKash Transaction',
				'menu_title' => 'Refund',
				'route'      => '/refund',
				'function'   => array( TransactionModule::class, 'refundATransaction' ),
				'show'       => true
			),
			// array(
			// 	'title'      => 'Webhook notifications',
			// 	'menu_title' => 'Webhook',
			// 	'route'      => '/webhooks',
			// 	'function'   => array( WebhookModule::class, 'webhooks' ),
			// 	'show'       => $is_webhook_enabled
			// ),
			// array(
			// 	'title'      => 'Check Balances',
			// 	'menu_title' => 'Check Balances',
			// 	'route'      => '/balances',
			// 	'function'   => array( TransferModule::class, 'checkBalances' ),
			// 	'show'       => $integration_type === 'checkout'
			// ),
			// array(
			// 	'title'      => 'Intra account transfer',
			// 	'menu_title' => 'Intra Account Transfer',
			// 	'route'      => '/intra_account',
			// 	'function'   => array( TransferModule::class, 'transferBalance' ),
			// 	'show'       => $integration_type === 'checkout'
			// ),
			// array(
			// 	'title'      => 'B2C Payout - Disbursement',
			// 	'menu_title' => 'Disburse Money (B2C)',
			// 	'route'      => '/b2c_payout',
			// 	'function'   => array( TransferModule::class, 'disburseMoney' ),
			// 	'show'       => $integration_type === 'checkout' && $is_b2c_enabled
			// ),
			// array(
			// 	'title'      => 'Transfer History',
			// 	'menu_title' => 'Transfer History',
			// 	'route'      => '/transfers',
			// 	'function'   => array( TransferModule::class, 'transferHistory' ),
			// 	'show'       => $integration_type === 'checkout'
			// ),
			// array(
			// 	'title'      => 'Agreements',
			// 	'menu_title' => 'Agreements',
			// 	'route'      => '/agreements',
			// 	'function'   => array( AgreementModule::class, 'agreementList' ),
			// 	'show'       => strpos( $integration_type, 'tokenized' ) === 0
			// ),
			array(
				'title'      => 'Payment Settings',
				'menu_title' => 'Settings',
				'route'      => esc_url(
					admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . BKASH_DC_PLUGIN_SLUG )
				),
				'show'       => 'link'
			)
		);

		foreach ( $sub_menus as $sub_menu ) {
			if ( isset( $sub_menu['show'] ) && $sub_menu["show"] === true ) {
				$sub_page = add_submenu_page(
					BKASH_DC_ADMIN_PAGE_SLUG,
					$sub_menu['title'],
					$sub_menu['menu_title'],
					'manage_options',
					BKASH_DC_ADMIN_PAGE_SLUG . $sub_menu['route'],
					$sub_menu['function']
				);
				add_action( 'admin_print_styles-' . $sub_page, array( $this, "adminStyles" ) );
			} elseif ( $sub_menu["show"] === "link" ) {
				global $submenu;
				$submenu[ BKASH_DC_ADMIN_PAGE_SLUG ][] = array(
					$sub_menu['title'],
					'manage_options',
					$sub_menu['route']
				);
			}
		}
	}

	/**
	 * Outputs styles used for the bKash gateway admin in wp.
	 *
	 * @access final public
	 * @return void
	 */
	final public function adminStyles() {
		wp_enqueue_style( 'bfw-admin-css', plugins_url( '../../../assets/css/admin.css', __FILE__ ) );
	}

	/**
	 * @return void
	 */
	final public function initiate() {
		add_action( 'admin_menu', array( $this, 'PluginMenu' ) );
	}

	/**
	 * @return void
	 */
	final public function beginInstall() {
		$tableGenerator = new TablesGenerator();
		$tableGenerator->createTransactionTable();
		// $tableGenerator->createWebhookTable();
		// $tableGenerator->createAgreementMappingTable();
		// $tableGenerator->createTransferHistoryTable();
	}
}
