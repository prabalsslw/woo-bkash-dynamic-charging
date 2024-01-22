<?php
/**
 * Transaction Module
 *
 * @category    Module
 * @package     bkash-for-woocommerce
 * @author      bKash Developer <developer@bkash.com>
 * @copyright   Copyright 2023 bKash Limited. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://bkash.com
 */

namespace bKash\PGW\DC\Admin\Module;

use bKash\PGW\DC\Admin\AdminUtility;
use bKash\PGW\DC\BkashApi;
use bKash\PGW\DC\Models\Transaction;
use bKash\PGW\DC\WooCommerceBkashDC;
use bKash\PGW\DC\Sanitizer;
use Exception;

class TransactionModule {
	/**
	 * @return void
	 */
	public static function transactionList() {
		AdminUtility::loadTable(
			'All bKash Transaction',
			'bkash_dc_transactions',
			array(
				'ORDER ID'         => 'order_id_wcdc',
				'INVOICE ID'       => 'invoice_id',
				'PAYMENT ID'       => 'payment_id',
				'TRANSACTION ID'   => 'trx_id',
				'AMOUNT'           => 'amount',
				'INTEGRATION TYPE' => 'integration_type',
				'INTENT'           => 'intent',
				'MODE'             => 'mode',
				'REFUND'           => array( 'refund_id', 'refund_amount' ),
				'STATUS'           => 'status',
				'DATETIME'         => 'datetime',
			),
			array(
				'trx_id'     => 'Transaction ID',
				'invoice_id' => 'Invoice ID',
				'status'     => 'Status',
			)
		);
	}

	/**
	 * @return void
	 */
	public static function transactionSearch() {
		try {
			$trx_id = Sanitizer::safePostValue( 'trxid' );

			if ( ! empty( $trx_id ) ) {
				$api  = new ApiComm();
				$call = $api->searchTransaction( $trx_id );

				if ( isset( $call['status_code'] ) && $call['status_code'] === 200 ) {
					$trx = array();
					if ( isset( $call['response'] ) && is_string( $call['response'] ) ) {
						$trx = json_decode( $call['response'], true );
					}

					// If any error
					if ( isset( $trx['statusMessage'] ) && $trx['statusMessage'] !== 'Successful' ) {
						$trx = $trx['statusMessage'];
					}
					if ( isset( $trx['errorMessage'] ) && ! empty( $trx['errorMessage'] ) ) {
						$trx = $trx['errorMessage'];
					}
				} else {
					$trx = 'Cannot find the transaction from bKash server right now, try again';
				}
			}
		} catch ( Exception $ex ) {
			$trx = $ex->getMessage();
		}

		include_once BKASH_FW_BASE_PATH . '/includes/classes/Admin/pages/transaction_search.php';
	}


	public static function refundATransaction() {
		$trx           = '';
		$trx_id        = Sanitizer::safePostValue( 'trxid' ) ?? '';
		$fill_trx_id   = Sanitizer::safePostValue( 'fill_trx_id' ) ?? '';
		$reason        = Sanitizer::safePostValue( 'reason' ) ?? '';
		$amount        = Sanitizer::safePostValue( 'amount' ) ?? '';
		$isRefund      = ! empty( Sanitizer::hasPostField( 'refund' ) );
		$isRefundCheck = ! empty( Sanitizer::hasPostField( 'check' ) );

		if ( ! empty( $trx_id ) ) {
			$trxObject   = new Transaction();
			$transaction = $trxObject->getTransaction( '', $trx_id );
			if ( $transaction ) {
				if ( $isRefund ) {
					if ( $amount > 0 ) {
						if ( $amount <= $transaction->getAmount() ) {
							try {
								$wcB    = new PaymentGatewayBkash();
								$refund = $wcB->process_refund( $transaction->getOrderID(), $amount, $reason );
								if ( $refund ) {
									$trx = $wcB->refundObj;
								} else {
									$trx = 'Refund is not successful, ' . ( $wcB->refundError ?? '' );
								}
							} catch ( Exception $exception ) {
								$trx = 'Refund is not successful, ' . ( $exception->getMessage() ?? '' );
							}
						} else {
							$trx = 'Refund amount cannot be greater than transaction amount';
						}
					} else {
						$trx = 'Refund amount should be greater than zero';
					}
				} elseif ( $isRefundCheck ) {
					try {
						$wcB    = new PaymentGatewayBkash();
						$refund = $wcB->queryRefund( $transaction->getOrderID() );
						if ( $refund ) {
							$trx = $refund;
						} else {
							$trx = 'Refund status not found, ' . ( $wcB->refundError ?? '' );
						}
					} catch ( Exception $exception ) {
						$trx = 'Refund status not found, ' . ( $exception->getMessage() ?? '' );
					}
				} else {
					$trx = 'Unknown refund operation';
				}
			} else {
				$trx = 'Cannot find the transaction to refund in your database, try again';
			}
		}

		include_once BKASH_FW_BASE_PATH . '/includes/classes/Admin/pages/refund_transaction.php';
	}
}
