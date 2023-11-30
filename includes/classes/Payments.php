<?php 
namespace bKash\PGW\DC;

use bKash\PGW\DC\BkashApi;

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

			if ( isset( $payment_payload['callbackURL'] ) ) {
				$payment_payload['callbackURL'] .= '&invoiceID=' . $payment_payload['merchantInvoiceNumber'];
			}

			$createResponse = $this->bKash_object->makePayment( $payment_payload );

			if ( isset( $createResponse['status_code'] ) && $createResponse['status_code'] === 200 ){
				$response = array();
				if ( isset( $createResponse['response'] ) && is_string( $createResponse['response'] ) ) {
					$response = json_decode( $createResponse['response'], true );
				}

				return array(
					'result'   => 'success',
					'redirect' => $response['bkashURL']
				);
			}
		}
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
		// $trx         = new Transaction();
		// $transaction = $trx->getTransaction( $invoice_id );

		if ( $status === 'success' ) {
			// if ( $transaction && $transaction->getPaymentID() === $payment_id ) {
			// 	$transaction->update(
			// 		array(
			// 			'status' => 'CALLBACK_REACHED',
			// 		)
			// 	);

				// EXECUTE OPERATION
				$response = $this->bKash_object->executePayment( $payment_id );

				if ( isset( $response['status_code'] ) && $response['status_code'] === 200 ) {
					$order->payment_complete();
				}
			// }
		}
	}
}