<?php
/**
 * Table Generation
 *
 * @category    Database
 * @package     woo-bkash-dynamic-charging
 * @author      bKash Developer <developer@bkash.com>
 * @copyright   Copyright 2023 bKash Limited. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://bkash.com
 */

namespace bKash\PGW\DC;

define( 'BKASH_UPGRADE_FILE', 'wp-admin/includes/upgrade.php' );

class TablesGenerator {
	/**
	 * @return void
	 */
	final public function createTransactionTable() {
		global $wpdb;
		$table_name             = $wpdb->prefix . 'bkash_dc_transactions';
		$my_products_db_version = BKASH_DC_PGW_VERSION;
		$charset_collate        = $wpdb->get_charset_collate();

		if ( $wpdb->get_var( $this->prepareQuery( $table_name ) ) !== $table_name ) {
			$sql = "CREATE TABLE $table_name (
                    ID bigint NOT NULL AUTO_INCREMENT,
                    `order_id_wcdc` VARCHAR(100) NOT NULL,
                    `trx_id` VARCHAR(50) NULL ,
                    `invoice_id` VARCHAR(100) NOT NULL UNIQUE,
                    `payment_id` VARCHAR(50) NULL ,
                    `integration_type` VARCHAR(50) NOT NULL,
                    `mode` VARCHAR(10) NULL,
                    `intent` VARCHAR(20) NULL,
                    `amount` decimal(15,2) NOT NULL,
                    `serviceFee` decimal(15,2) NULL,
                    `currency` VARCHAR(10) NOT NULL,
                    `refund_id` VARCHAR(50) NULL,
                    `refund_amount` decimal(15,2) NULL,
                    `status` VARCHAR(50) NULL,
                    `datetime` timestamp NULL,
                    PRIMARY KEY  (ID)
            ) $charset_collate;";

			require_once ABSPATH . BKASH_UPGRADE_FILE;
			dbDelta( $sql );
			add_option( 'bkash_dc_transaction_table_version', $my_products_db_version );
		}
	}

	/**
	 * Prepare Query
	 *
	 * @param $tableName
	 *
	 * @return string|void
	 */
	private function prepareQuery( $tableName ) {
		global $wpdb;

		return $wpdb->prepare( 'SHOW TABLES LIKE %s', $tableName );
	}

	/**
	 * @return void
	 */
	final public function createWebhookTable() {
		global $wpdb;
		$table_name             = $wpdb->prefix . 'bkash_dc_webhooks';
		$my_products_db_version = BKASH_DC_PGW_VERSION;
		$charset_collate        = $wpdb->get_charset_collate();

		if ( $wpdb->get_var( $this->prepareQuery( $table_name ) ) !== $table_name ) {
			$sql = "CREATE TABLE $table_name (
                    ID bigint NOT NULL AUTO_INCREMENT,
                    `sender` VARCHAR(20) NOT NULL,
                    `receiver` VARCHAR(20) NOT NULL,
                    `receiver_name` VARCHAR(100) NULL,
                    `trx_id` VARCHAR(50) NOT NULL UNIQUE,
                    `coupon_trx` VARCHAR(50) NULL UNIQUE,
                    `status` VARCHAR(30) NOT NULL,
                    `type` VARCHAR(50) NOT NULL,
                    `amount` decimal(15,2) NOT NULL,
                    `currency` VARCHAR(10) NULL,
                    `invoice` VARCHAR(100) NULL,
                    `reference` VARCHAR(100) NULL,
                    `datetime` timestamp NULL,
                    PRIMARY KEY  (ID)
            ) $charset_collate;";

			require_once ABSPATH . BKASH_UPGRADE_FILE;
			dbDelta( $sql );
			add_option( 'bkash_dc_webhook_table_version', $my_products_db_version );
		}
	}

	/**
	 * @return void
	 */
	final public function createAgreementMappingTable() {
		global $wpdb;
		$table_name             = $wpdb->prefix . 'bkash_dc_agreement_mapping';
		$my_products_db_version = BKASH_DC_PGW_VERSION;
		$charset_collate        = $wpdb->get_charset_collate();

		if ( $wpdb->get_var( $this->prepareQuery( $table_name ) ) !== $table_name ) {
			$sql = "CREATE TABLE $table_name (
                    ID bigint NOT NULL AUTO_INCREMENT,
                    `phone` VARCHAR(20) NOT NULL,
                    `user_id` bigint NOT NULL,
                    `agreement_token` VARCHAR(300) NOT NULL,
                    `datetime` timestamp NOT NULL,
                    PRIMARY KEY  (ID)
            ) $charset_collate;";

			require_once ABSPATH . BKASH_UPGRADE_FILE;
			dbDelta( $sql );
			add_option( 'bkash_dc_agreement_mapping_table_version', $my_products_db_version );
		}
	}

	/**
	 * @return void
	 */
	final public function createTransferHistoryTable() {
		global $wpdb;
		$table_name             = $wpdb->prefix . 'bkash_dc_transfers';
		$my_products_db_version = BKASH_DC_PGW_VERSION;
		$charset_collate        = $wpdb->get_charset_collate();

		if ( $wpdb->get_var( $this->prepareQuery( $table_name ) ) !== $table_name ) {
			$sql = "CREATE TABLE $table_name (
                    id bigint NOT NULL AUTO_INCREMENT,
                    `receiver` VARCHAR(20) NOT NULL,
                    `amount` decimal(15,2) NOT NULL,
                    `currency` VARCHAR(3) NOT NULL,
                    `trx_id` VARCHAR(50) NOT NULL,
                    `merchant_invoice_no` VARCHAR(80) NOT NULL,
                    `transactionStatus` VARCHAR(30) NOT NULL,
                    `b2cFee` VARCHAR(40) NULL,
                    `initiationTime` timestamp NULL,
                    `completedTime` timestamp NULL,
                    PRIMARY KEY (id)
            ) $charset_collate;";

			require_once ABSPATH . BKASH_UPGRADE_FILE;
			dbDelta( $sql );
			add_option( 'bkash_dc_transfer_table_version', $my_products_db_version );
		}
	}
}
