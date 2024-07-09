<?php 
namespace bKash\PGW\DC;

use Exception;
use Throwable;
use UnexpectedValueException;

class BkashApi {

	public $debug;
	private $enabled;
	private $integration_product = 'paymentonly';
	private $intent;
	private $sandbox;
	private $api_version;
	private $enable_b2c;
	private $app_key;
	private $app_secret;
	private $username;
	private $password;
	private $constructed_url;
	/**
	 * @var false|mixed|void|null
	 */
	private $token;

	public function __construct() {
		/* Initializing parameters using required fields from calling class */
		$this->getSettingsConfig();

		/* Constructing API URL for later use */
		$this->generatePaymentURL();

		/* Initiate Token Generate Process */
		$this->processToken();
	}

	final public function getSettingsConfig() {
		$this->enabled 	  = $this->getOptions( 'enabled' );
		$this->integration_product = $this->getOptions( 'integration_type', 'paymentonly' );
		$this->intent              = $this->getOptions( 'intent', 'sale' );
		$this->api_version         = $this->getOptions( 'bkash_api_version', 'v1' );
		$this->enable_b2c = $this->getOptions( 'enable_b2c' );
		$this->sandbox    = $this->getOptions( 'sandbox' );
		$this->app_key    = $this->getOptions( 'app_key' );
		$this->app_secret = $this->getOptions( 'app_secret' );
		$this->username   = $this->getOptions( 'username' );
		$this->password   = $this->getOptions( 'password' );
		$this->debug      = $this->getOptions( 'debug', 'no' );
		
	}

	final public function getOptions( $key, $default = null ) {
		$settings = get_option( 'woocommerce_' . BKASH_DC_PLUGIN_SLUG . '_settings' );
		if ( ! is_null( $settings ) ) {
			return $settings[ $key ] ?? $default;
		}

		return $default;
	}

	private function generatePaymentURL() {
		if($this->enabled === 'yes') {
			if($this->sandbox === 'yes') {
				$api_Base_url = "https://sbdynamic.pay.bka.sh/";
			} else {
				$api_Base_url = "https://dynamic.pay.bka.sh/";
			}
			
			$this->constructed_url = $api_Base_url. $this->api_version . "/";
		}
		else {
			wc_add_notice( "bKash Payment Plugin Not Activate!", 'error' );
		}
	}

	final public function httpRequest($api_title,$url,&$http_status,$method = 'POST',$post_data = null,&$header = null,$grantHeader = false
	): string {
		$log  = "\n======== bKash PGW REQUEST LOG ========== \n\nAPI TITLE: $api_title \n";
		$log .= "REQUEST METHOD: $method \n";
		$log .= "REQUEST URL: $url \n";

		$headers                 = array();
		$headers['Accept']       = 'application/json';
		$headers['Content-Type'] = 'application/json';
		if ( $grantHeader ) {
			$headers['username'] = $this->username;
			$headers['password'] = $this->password;
		} else {
			$headers['authorization'] = $this->token;
			$headers['x-app-key']     = $this->app_key;
			$headers['authorization'];
		}
		if ( ! is_null( $header ) ) {
			$headers = array_merge( $headers, $header );
		}

		$log .= 'HEADERS: ' . wp_json_encode( $headers ) . "\n";
		$log .= 'BODY: ' . wp_json_encode( $post_data ) . "\n";

		$response = wp_remote_post(
			$url,
			array(
				'method'      => $method,
				'timeout'     => 29,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => $headers,
				'body'        => strtolower( $method ) === 'get' ? $post_data : wp_json_encode( $post_data ),
			)
		);

		$log .= 'RESPONSE: ' . wp_json_encode( $response ) . "\n\n";

		if ( is_wp_error( $response ) ) {
			$http_status = - 1;
			$body        = $response->get_error_message();

			Log::error( 'CURL Error: = ' . $body );
		} else {
			// parsing http status code
			$http_status = wp_remote_retrieve_response_code( $response );

			if ( $http_status === 401 ) {
				$this->readTokenFromAPI();
			}

			$header = wp_remote_retrieve_headers( $response );
			$body   = wp_remote_retrieve_body( $response );
		}

		Log::debug( $log );

		return $body;
	}

	private function addOrUpdateOption( $key, $value ) {
		if ( ! get_option( $key ) ) {
			add_option( $key, $value );
		} else {
			update_option( $key, $value );
		}
	}

	final public function resetToken() {
		delete_option( 'bkash_dc_grant_token' );
		delete_option( 'bkash_dc_grant_token_expiry' );
		delete_option( 'bkash_dc_integration_product' );
	}

	private function readTokenFromAPI() {
		if ( empty( $this->app_key ) || empty( $this->app_secret ) ) {
			Log::error( 'App key or secret is not set, required for bKash APIs' );
		} else {
			$get_token = $this->getToken();
			if ( isset( $get_token['status_code'] ) && $get_token['status_code'] === 200 ) {
				$response = json_decode( $get_token['response'], true );
				if ( isset( $response['id_token'] ) ) {
					$this->token = $response['id_token'];
					$expiry      = time() + $response['expires_in'];

					$this->addOrUpdateOption( 'bkash_dc_grant_token', $this->token );
					$this->addOrUpdateOption( 'bkash_dc_grant_token_expiry', $expiry );
					$this->addOrUpdateOption( 'bkash_dc_integration_product', $this->integration_product );
				} else {
					Log::error( 'Cannot read token from server, response ==> ' . wp_json_encode( $get_token ) );
				}
			} else {
				Log::error( 'Cannot get response from get token API, response ==>' . wp_json_encode( $get_token ) );
			}
		}
	}

	private function processToken() {
		try {
			$token   = get_option( 'bkash_dc_grant_token' );
			$expiry  = get_option( 'bkash_dc_grant_token_expiry' );
			$product = get_option( 'bkash_dc_integration_product' );

			// if expiry time in seconds is greater than current time
			if ( $this->integration_product === $product && ! is_null( $token ) && ( $expiry - time() > 0 ) ) {
				$this->token = $token;
			} else {
				$this->readTokenFromAPI();
			}
		} catch ( Exception $e ) {
			Log::debug( $e );
			Log::error( 'bKash PGW ERROR: exception generated while processing token, ' . $e->getMessage() );
		}
	}

	final public function executeCompleteOrCaptureVoid( string $payment_id, string $type ): array {
		switch ( $type ) {
			case 'execute':
				$api_path     = 'payment/execute';
				$extra_in_url = '';
				break;
			case 'capture':
				$api_path     = 'payment/confirm';
				$extra_in_url = '';
				break;
			case 'void':
				$api_path     = 'payment/confirm';
				$extra_in_url = '';
				break;
			default:
				$api_path     = '';
				$extra_in_url = '';
		}

		
		$url      = $this->constructed_url . $api_path . $extra_in_url;
		$apiTitle = 'DC ' . ucwords( $type ) . ' Payment';
		if($type == 'capture' || $type == 'void') {
			$body     = array( 
				'paymentId' => $payment_id,
				'confirmationType' => $type
			);
		} else {
			$body     = array( 'paymentId' => $payment_id );
		}

		$response = $this->httpRequest($apiTitle,$url,$http_status,'POST',$body,$header);

		// QUERY PAYMENT IN CASE OF ANY NETWORK OR NO RESPONSE OR TIMED OUT ISSUE
		$decoded_response = isset( $response['response'] ) && is_string( $response['response'] ) ?
			json_decode( $response['response'], true ) : array();

		if ( $http_status !== 200 || isset( $decoded_response['message'] ) ) {
			return $this->queryPayment( $payment_id );
		}

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);
	}

	final public function getToken(): array {
		$url = $this->constructed_url . 'auth/grant-token';

		$body = array(
			'app_key'    => $this->app_key,
			'app_secret' => $this->app_secret,
		);

		$response = $this->httpRequest( 'Grant Token', $url, $http_status, 'POST', $body, $header, true );

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);
	}

	final public function makePayment( array $params ): array {
		$url = $this->constructed_url . 'payment/create';

		$body = array(
			'mode'                    => $params['mode'] ?? '',
			'payerReference'          => $params['payerReference'] ?? '',
			'callbackURL'             => $params['callbackURL'] ?? '',
			'agreementID'             => $params['agreementID'] ?? '',
			'amount'                  => $params['amount'] ?? '',
			'currency'                => $params['currency'] ?? '',
			'intent'                  => $params['intent'] ?? '',
			'merchantInvoiceNumber'   => $params['merchantInvoiceNumber'] ?? '',
			'merchantAssociationInfo' => $params['merchantAssociationInfo'] ?? '',
		);

		$response = $this->httpRequest( 'Create Payment', $url, $http_status, 'POST', $body, $header );

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);
	}

	final public function executePayment( string $payment_id ): array {
		return $this->executeCompleteOrCaptureVoid( $payment_id, 'execute' );
	}

	final public function queryPayment( string $payment_id ): array {
		$url = $this->constructed_url . 'query/payment';

		$body = array(
			'paymentID' => $payment_id,
		);

		$response = $this->httpRequest( 'Query Payment', $url, $http_status, 'POST', $body, $header );

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);
	}

	final public function searchTransaction( string $trx_id ): array {
		$url = $this->constructed_url.'search/transaction';

		if ( !empty($trx_id) ) {
			$body = array(
				'trxID' => $trx_id,
			);

			$response = $this->httpRequest( 'DC Search Transaction', $url, $http_status, 'POST', $body, $header );
		}

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);
	}

	final public function refund( $amount, $paymentID, $trxID, $SKU, $reason ): array {
		$url = $this->constructed_url . 'payment/refund';

		$body = array(
			'amount'    => $amount,
			'paymentId' => $paymentID,
			'trxID'     => $trxID,
			'sku'       => $SKU,
			'reason'    => $reason,
		);

		$response = $this->httpRequest( 'Refund', $url, $http_status, 'POST', $body, $header );

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);
	}

	final public function refundStatus( $paymentID, $trxID ): array {
		$url = $this->constructed_url . 'query/refund';

		$body = array(
			'paymentId' => $paymentID,
			'trxID'     => $trxID,
		);

		$response = $this->httpRequest( 'Query Refund', $url, $http_status, 'POST', $body, $header );

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);
	}

	final public function capturePayment( string $payment_id ): array {
		echo $payment_id;
		return $this->executeCompleteOrCaptureVoid( $payment_id, 'capture' );
	}

	final public function voidPayment( string $payment_id ): array {
		return $this->executeCompleteOrCaptureVoid( $payment_id, 'void' );
	}

	final public function checkBalances(): array {
		$url = $this->constructed_url . 'query/organization/balance';

		$response = $this->httpRequest( 'Query Organization Balance', $url, $http_status, 'GET', null, $header );

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);

		// throw new UnexpectedValueException( 'Query organization balance is only available in Checkout integration' );
	}

	final public function initiatePayout( $type ): array {
		$url = $this->constructed_url . 'payout/initiate';

		$body = array(
			'type' => $type,
    		'reference' => $type  
		);

		$response = $this->httpRequest( 'Initiate Payout', $url, $http_status, 'POST', $body, $header );

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);
		// throw new UnexpectedValueException( 'Intra Account Transfer is only available in Checkout integration' );
	}

	final public function intraAccountTransfer( $amount, string $transferType ): array {
		$url = $this->constructed_url . 'payout/intra-account/transfer';
		
		$initiatePayoutResponse = $this->initiatePayout("INTRA");
		$arrayResponse = json_decode($initiatePayoutResponse['response'], true);
		$payoutId = $arrayResponse['payoutID'];

		$body = array(
			'payoutID' => $payoutId,
		    'amount' => $amount,
		    'currency' => 'BDT',
		    'transferType' => $transferType
		);

		$response = $this->httpRequest( 'Intra Account Transfer', $url, $http_status, 'POST', $body, $header );

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);
		// throw new UnexpectedValueException( 'Intra Account Transfer is only available in Checkout integration' );
	}

	final public function b2cPayout( $amount, string $invoiceNumber, string $receiver ): array {
		$url = $this->constructed_url . 'payout/b2c';

		$initiatePayoutResponse = $this->initiatePayout("B2C");
		$arrayResponse = json_decode($initiatePayoutResponse['response'], true);
		$payoutId = $arrayResponse['payoutID'];

		$body = array(
			'payoutID' 				=> $payoutId,
		    'amount' 				=> $amount,
		    'currency' 				=> "BDT",
		    'merchantInvoiceNumber' => $invoiceNumber,
		    'receiverMSISDN' 		=> $receiver
		);

		$response = $this->httpRequest( 'B2C Payout', $url, $http_status, 'POST', $body, $header );

		return array(
			'status_code' => $http_status,
			'header'      => $header,
			'response'    => $response,
		);

		// throw  new UnexpectedValueException( 'B2C Payout is only available in Checkout integration' );
	}
}