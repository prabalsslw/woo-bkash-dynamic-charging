<?php 
namespace bKash\PGW\DC;

use bKash\PGW\DC\BkashApi;
use bKash\PGW\DC\Models\Transaction;

class Payments {

	public $integration_type;
	private $bKashObj;

	public function __construct( string $integration_type ) {
		$this->integration_type = $integration_type;
		$this->bKash_object         = new BkashApi();
	}

	final public function createPayment(
		string $order_id,
		string $intent = 'sale',
		string $callbackURL = '',
		$transaction = null
	): array {
		// $isAgreement = Sanitizer::hasPostField( 'agreement' );
		// if ( ! $isAgreement ) {
		// 	$isAgreement = Sanitizer::hasGetField( 'agreement' );
		// }
		// $agreement_id = Sanitizer::safePostValue( 'agreement_id' );
		// if ( ! $agreement_id ) {
		// 	$agreement_id = Sanitizer::hasGetField( 'agreement_id' );
		// }

		// To receive order id and total
		$order    = wc_get_order( $order_id );
		$amount   = $order->get_total();
		$currency = get_woocommerce_currency();

		// To receive user id and order details
		$merchantCustomerId = $order->get_user_id();
		$merchantOrderId    = $order->get_order_number();

		if ( $this->integration_type === 'paymentonly' ) {
			$mode = '1011';
			$payment_payload = array(
				'mode'                  => $mode,
				'payerReference'        => uniqid( 'bKash_', false ) . '_' . $merchantCustomerId,
				'callbackURL'           => $callbackURL,
				'amount'                => $amount,
				'currency'              => $currency,
				'intent'                => $intent,
				'merchantInvoiceNumber' => uniqid( 'bdc_', false ) . '_' . $merchantOrderId,
			);
		}


		// if transaction is not prepared yet
		if ( empty( $transaction ) ) {
			/* Store Transaction in Database */
			$trx = new Transaction();
			$trx->setOrderID( $order_id );
			$trx->setAmount( $amount );
			$trx->setIntegrationType( $this->integration_type );
			$trx->setIntent( $intent );
			$trx->setCurrency( $currency );
			$trx->setMode( $mode ?? '' );
			$trx->setStatus( 'Initiated' );

			if ( ! empty( $payment_payload['merchantInvoiceNumber'] ) ) {
				$trx->setInvoiceID( $payment_payload['merchantInvoiceNumber'] );
			}

			$trxSaved = $trx->save();
		} else {
			$trxSaved = $transaction;
		}

		if ( $trxSaved ) {
			if ( isset( $payment_payload['callbackURL'] ) ) {
				$payment_payload['callbackURL'] .= '&invoiceID=' . $payment_payload['merchantInvoiceNumber'];
			}

			$createResponse = $this->bKash_object->makePayment( $payment_payload );

			if ( isset( $createResponse['status_code'] ) && $createResponse['status_code'] === 200 ){
				$response = array();
				if ( isset( $createResponse['response'] ) && is_string( $createResponse['response'] ) ) {
					$response = json_decode( $createResponse['response'], true );
				}

				if ( $response ) {
					// If any error for tokenized
					if ( isset( $response['statusMessage'] ) && $response['statusMessage'] !== 'Successful' ) {
						$message = $response['statusMessage'];
					} elseif ( isset( $response['errorCode'] ) ) { // If any error for checkout
						$message = $response['errorMessage'] ?? '';
					} elseif ( isset( $response['paymentId'] ) && ! empty( $response['paymentId'] ) ) {
						// Remove items from cart.
						WC()->cart->empty_cart();
						if ( isset( $this->log ) && $this->log ) {
							$this->log->add( $this->id, 'Cart emptied.' );
						}

						$updated = $trxSaved->update( array( 'payment_id' => $response['paymentId'] ) );
						if ( $updated ) {
							return array(
								'result'   => 'success',
								'redirect' => $response['bkashURL'],
							);
						}

						$message = $this->processResponse(
							'Cannot process this payment right now, payment ID issue'
						);
					} else {
						$message = $this->processResponse(
							'Cannot process this payment right now, unknown error message'
						);
					}
				} else {
					$message = $this->processResponse( 'Cannot process this payment right now, not a valid response' );
				}
			}
			else {
				$message = $this->processResponse( 'Cannot process this payment right now, error in communication' );
			}
		} else {
			$message = $trx->errorMessage;
		}

		wc_add_notice( $message, 'error' );

		return array(
			'result'  => 'failure',
			'message' => $message,
		);
	}

	final public function executePayment( string $orderPageURL, string $callbackURL = '' ) {
		$message = '';

		if ( Sanitizer::hasGetField( 'orderId' ) ) {
			$order_id   = Sanitizer::safeGetValue( 'orderId' );
			$payment_id = Sanitizer::safeGetValue( 'paymentId' );
			$invoice_id = Sanitizer::safeGetValue( 'invoiceID' );
			$status     = Sanitizer::safeGetValue( 'status' );
		} else {
			$order_id   = Sanitizer::safePostValue( 'orderId' );
			$payment_id = Sanitizer::safePostValue( 'paymentId' );
			$invoice_id = Sanitizer::safePostValue( 'invoiceID' );
			$status     = Sanitizer::safePostValue( 'status' );
		}

		// To receive order id
		$order       = wc_get_order( $order_id );
		$trx         = new Transaction();
		$transaction = $trx->getTransaction( $invoice_id );
		// print_r($order);die();

		if ( $status === 'success' ) {

			if ( $transaction && $transaction->getPaymentID() === $payment_id ) {
				$transaction->update(
					array(
						'status' => 'CALLBACK_REACHED',
					)
				);

				// EXECUTE OPERATION
				$response = $this->bKash_object->executePayment( $payment_id );
				if ( isset( $response['status_code'] ) && $response['status_code'] === 200 ) {
					$mode = $transaction->getMode();

					if ( $mode === '0000' ) 
					{
						$agreementResp = Operations::processResponse( $response, 'agreementID' );

						if ( is_array( $agreementResp ) ) {
							if ( $agreementResp['agreementStatus'] === 'Completed' ) {
								$agreementObj = new Agreement();
								$agreementObj->setAgreementID( $agreementResp['agreementID'] ?? '' );
								$agreementObj->setMobileNo( $agreementResp['customerMsisdn'] ?? '' );
								$agreementObj->setDateTime( $agreementResp['agreementExecuteTime'] ?? '' );
								$agreementObj->setUserID( $order->get_user_id() );
								$stored = $agreementObj->save();

								if ( $stored ) {
									$transaction->update(
										array( 'mode' => '0001' ),
										array( 'payment_id' => $transaction->getPaymentID() )
									);
									add_post_meta( $order->get_id(), '_bkmode', '0001', true );

									$createResp = $this->createPayment(
										$transaction->getOrderID(),
										$transaction->getIntent(),
										$callbackURL,
										$transaction
									);

									if ( isset( $createResp['redirect'] ) ) {
										wp_safe_redirect( $createResp['redirect'] );
										die();
									}

									echo wp_json_encode( $createResp );
								} else {
									$message = 'Agreement cannot be done right now, cannot store in db, try again. ' . $agreementObj->errorMessage;
									$message = $this->processResponse( $message );
								}
							} else {
								$message = $this->processResponse( 'Agreement cannot be done right now, try again' );
							}
						} else {
							$message = is_string( $agreementResp ) ? $agreementResp : '';
							$message = $this->processResponse( $message );
						}
					}
					else {
						// GET TRXID FROM BKASH RESPONSE
						$paymentResp = Operations::processResponse( $response, 'trxID' );

						if ( is_array( $paymentResp ) ) {
							// PAYMENT IS DONE SUCCESSFULLY, NOW START REST OF THE PROCESS TO UPDATE WC ORDER

							// Updating transaction status
							$status  = $paymentResp['transactionStatus'] ?? 'NO_STATUS_EXECUTE';
							$updated = $transaction->update(
								array(
									'status' => $status,
									'trx_id' => $paymentResp['trxID'] ?? '',
									'serviceFee' => $paymentResp['serviceFee'] ?? ''
								)
							);

							if ( $updated && isset( $paymentResp['trxID'] ) && ! empty( $paymentResp['trxID'] ) ) {
								// Payment complete.
								$transaction_id = $paymentResp['trxID'] ?? '';
								if ( $paymentResp['transactionStatus'] === 'Authorized' ) {
									$order->update_status( 'on-hold' );
									$order->set_transaction_id($transaction_id);
									$order->save();
								} elseif ( $paymentResp['transactionStatus'] === 'Completed' ) {
									$order->payment_complete();
									$order->set_transaction_id($transaction_id);
									$order->save();
							
								} else {
									$order->update_status( 'pending' );
								}

								// Store the transaction ID for WC 2.2 or later.
								add_post_meta( $order->get_id(), '_transaction_id', $paymentResp['trxID'], true );

								// Add order note.
								$order->add_order_note(
									sprintf( 'bKash PGW payment approved (ID: %s)', $paymentResp['trxID'] )
								);

								if ( isset( $this->log ) && $this->log ) {
									$this->log->add(
										$this->id,
										'bKash PGW payment approved (ID: ' . $response['trxID'] . ')'
									);
								}

								// Reduce stock levels.
								wc_reduce_stock_levels( $order_id );

								if ( isset( $this->log ) && $this->log ) {
									$this->log->add( $this->id, 'Stocked reduced.' );
								}

								// Return thank you page redirect.
								wp_safe_redirect( $orderPageURL );
								die();
							}

							if ( $updated && isset( $paymentResp['paymentId'] ) && ! empty( $paymentResp['paymentId'] ) ) {
								$msg = 'Transaction was not successful, last transaction status: ' . $paymentResp['transactionStatus'] ?? 'NO_STATUS_EXECUTE';

								wc_add_notice( $msg, 'error' );
								wp_safe_redirect( wc_get_checkout_url() );
								die();
							}
							$message = 'Could not get transaction status';
						} else {
							$message = is_string( $paymentResp ) ? $paymentResp : '';
						}

						$transaction->update(
							array(
								'status' => 'Failed',
							)
						);
						$order->add_order_note( 'bKash Payment: ' . $message );

						$message = $this->processResponse( $message );
					}
				}

				else {
					$message = $this->processResponse( 'Communication issue with payment gateway' );
				}
			}
		} else {
			// transaction failed/cancelled.
			$status = str_replace( array( 'cancel', 'failure' ), array( 'Cancelled', 'Failed' ), $status );
			if ( $transaction->getStatus() !== 'Completed' ) {
				$transaction->update(
					array(
						'status' => esc_html( $status ),
					)
				);
				$order->add_order_note( 'bKash Payment is not successful. Status => ' . esc_html( $status ) );
			} else {
				$order->add_order_note(
					'bKash Payment is already in Completed state. Tried to change Status to => '
					. esc_html( $status )
				);
			}

			$message = $this->processResponse( 'Transaction is ' . $status );
		}

		$order->add_order_note( 'bKash PGW payment declined (' . $message . ')' );

		wc_add_notice( $message, 'error' );
		wp_safe_redirect( wc_get_cart_url() );
		
		// Return message to customer.
		die();
	}


	final public function processResponse( string $message ): string {
		return "<h3 style='color:#fff;font-weight:bold;margin:0;font-size:20px;line-height: 14px;'>Payment Failed</h3>" . $message;
	}

	final public function cancelPayment( string $order_id ): array {
		// global $woocommerce;
		// To receive order id
		$order = wc_get_order( $order_id );
		if ( $order ) {
			if ( $order->get_status() === 'pending' ) {
				$trx         = new Transaction();
				$transaction = $trx->getTransactionByOrderId( $order_id );
				if ( $transaction ) {
					$transaction->update(
						array(
							'status' => 'Cancelled',
						)
					);
					$order->add_order_note( 'bKash Payment has been cancelled, either failed or customer cancelled' );
					$order->update_status( 'cancelled', 'Payment has been cancelled!' );

					return array(
						'result'   => 'success',
						'redirect' => null,
						'response' => 'Order cancelled!',
					);
				}

				return array(
					'result'  => 'failure',
					'message' => 'Transaction not found in bKash database',
				);
			}

			return array(
				'result'  => 'failure',
				'message' => 'Order is not in pending status to cancel the payment',
			);
		}

		return array(
			'result'  => 'failure',
			'message' => 'Order not found',
		);
	}
}