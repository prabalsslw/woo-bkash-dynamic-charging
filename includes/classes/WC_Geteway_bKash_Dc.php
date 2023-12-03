<?php 

/**
 * Payment Gateway bKash
 *
 * @category    Payment
 * @package     woo-bkash-dynamic-charging
 * @author      bKash Developer <developer@bkash.com>
 * @copyright   Copyright 2023 bKash Limited. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://bkash.com
 */
    
namespace bKash\PGW\DC;

use Exception;
use WC_AJAX;
use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;
use WP_Error;

/**
 * WooCommerce bKash Payment Gateway for Dynamic Charging Product.
 *
 * @class   WC_Geteway_bKash_Dc
 * @extends WC_Payment_Gateway
 * @version 1.0.0
 * @package bKash\PGW
 * @author  Md. Shahnawaz Ahmed
 */

if (!class_exists('WC_Payment_Gateway')) return;

	class WC_Geteway_bKash_Dc extends WC_Payment_Gateway {

    public $log;
    public $refundObj;
    public $refundError;
    private $CALLBACK_URL         = 'bkashdc_payment_process';
    private $SUCCESS_CALLBACK_URL = 'bkashdc_payment_success';
    private $FAILURE_CALLBACK_URL = 'bkashdc_payment_failure';
    private $EXECUTE_URL          = 'bkdc_execute';
    private $PAYMENT_CANCEL_URL   = 'bkdc_cancel';
    private $CANCEL_AGREEMENT_URL = 'bkdc_cancel_agreement';
    private $REVIEW_ORDER_URL     = 'bkdc_review_order';
    private $WEBHOOK_URL          = 'bkashdc_webhook';

    /**
     * @var string|null
     */
    private $siteUrl;
    /**
     * @var string|null
     */
    private $is_webhook;
    private $sandbox;
    /**
     * @var string|null
     */
    private $debug;
    /**
     * @var string|null
     */
    private $password;
    /**
     * @var string|null
     */
    private $username;
    /**
     * @var string|null
     */
    private $app_secret;
    /**
     * @var string|null
     */
    private $app_key;
    /**
     * @var string|null
     */
    private $api_version;
    /**
     * @var string|null
     */
    private $intent;
    /**
     * @var string|null
     */
    private $integration_type;
    /**
     * @var string|null
     */
    private $enable_b2c;

    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */

    public function __construct() {
        
        $this->initiateAdminSettings();
        $this->registerHooks();
    }

    public function initiateAdminSettings() {

        $this->id                   = BKASH_DC_PLUGIN_SLUG; 
        $this->icon                 = apply_filters('woocommerce_payment_gateway_bkash_icon',plugins_url( '../assets/images/logo.png', __DIR__ )); 
        $this->has_fields           = true; 
        $this->method_title         = 'bKash Dynamic Charging';
        $this->method_description   = 'bKash Payment Gateway provides range of payment solutions to merchants of the online sphere'; 
        $this->siteUrl              = get_site_url();

        $this->supports = array(
            'products',
            'refunds',
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled          = $this->get_option( 'enabled' );
        $this->title            = $this->get_option( 'title' );
        $this->description      = $this->get_option( 'description' );
        $this->integration_type = $this->get_option( 'integration_type' );
        $this->intent           = $this->get_option( 'intent' );
        $this->api_version      = $this->get_option( 'bkash_api_version' );
        $this->sandbox          = $this->get_option( 'sandbox' );
        $this->app_key          = $this->get_option( 'app_key' );
        $this->app_secret       = $this->get_option( 'app_secret' );
        $this->username         = $this->get_option( 'username' );
        $this->password         = $this->get_option( 'password' );
        $this->debug            = $this->get_option( 'debug' );
        $this->enable_b2c       = $this->get_option( 'enable_b2c' );
      
        if ( $this->debug === 'yes' ) {
            if ( class_exists( WC_Logger::class ) ) {
                $this->log = new WC_Logger();
            } else {
                global $woocommerce;
                $this->log = isset( $woocommerce ) ? $woocommerce->logger() : null;
            }
        }
        $this->is_webhook = $this->get_option( 'webhook' );
    }

    public function registerHooks() {

        if ( is_admin() ) {
            add_action( 'admin_notices', array( $this, 'checks' ) );
            // add_action( 'admin_notices', array( $this, 'displayFlashNotices' ), 12 );

            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receiptPage' ) );
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array(
                    $this,
                    'process_admin_options',
                )
            );
        }
        // add_action( 'wp_enqueue_scripts', array( $this, 'paymentScripts' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankYouPage' ) );

        // Customer Emails.
        // add_action( 'woocommerce_email_before_order_table', array( $this, 'emailInstructions' ), 10, 3 );

        add_action(
            'woocommerce_order_status_completed',
            array(
                __CLASS__,
                'captureTransactionFromStatus',
            ),
            10,
            2
        );
        // add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'voidTransactionOnCanceled' ), 10, 2 );

        add_action( 'woocommerce_api_' . $this->CALLBACK_URL, array( $this, 'createPaymentCallbackProcess' ) );
        // add_action( 'woocommerce_api_' . $this->SUCCESS_CALLBACK_URL, array( $this, 'paymentSuccess' ) );
        // add_action( 'woocommerce_api_' . $this->FAILURE_CALLBACK_URL, array( $this, 'paymentFailure' ) );
        add_action( 'woocommerce_api_' . $this->EXECUTE_URL, array( $this, 'createPaymentCallbackProcess' ) );
        // add_action( 'woocommerce_api_' . $this->PAYMENT_CANCEL_URL, array( $this, 'cancelPaymentProcess' ) );
        // add_action( 'woocommerce_api_' . $this->CANCEL_AGREEMENT_URL, array( $this, 'cancelAgreementApi' ) );
        // add_action( 'woocommerce_api_' . $this->REVIEW_ORDER_URL, array( $this, 'processReviewOrderPayment' ) );
        // // WebhookModule
        // add_action( 'woocommerce_api_' . $this->WEBHOOK_URL, array( $this, 'webhook' ) );

        // reset token when setting changes
        // add_action( 'update_option', array( $this, 'onUpdateResetToken' ), 10, 3 );

        // You can also register a webhook here
        // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
    }

    public function init_form_fields(){
        $this->form_fields = array(
            'enabled'      => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable bKash PGW',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
            ),
            'title'              => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'bKash Payment Gateway - Dynamic Charging.',
                'desc_tip'    => true,
            ),
            'description'        => array(
                'title'       => 'Description',
                'type'        => 'text',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay with bKash Dynamic Charging Payment Gateway',
                'desc_tip'    => true,
            ),
            'integration_type'   => array(
                'title'       => 'Integration Type',
                'type'        => 'select',
                'description' => 'Payment will be initiated with selected bKash PGW integration type',
                'options'     => array(
                    'paymentonly'    => 'Payment Only (Tokenized Without-Agreement)',
                    'tokenized'      => 'Tokenized (With Agreement)',
                    'tokenized-both' => 'Tokenized (With and without Agreement)',
                ),
                'default'     => 'paymentonly',
                'desc_tip'    => true,
            ),
            'intent'             => array(
                'title'       => 'Intent',
                'type'        => 'select',
                'description' => 'Payment will be initiated with selected bKash PGW integration type',
                'options'     => array(
                    'sale'          => 'Sale',
                    'authorization' => 'Authorized',
                ),
                'default'     => 'checkout',
                'desc_tip'    => true,
            ),
            'bkash_api_version'  => array(
                'title'       => 'API Version',
                'type'        => 'text',
                'description' => 'This api version will be used for calling API to bKash',
                'default'     => 'v1',
                'desc_tip'    => true,
            ),
            'debug'              => array(
                'title'       => 'Debug Log',
                'type'        => 'checkbox',
                'label'       => 'Enable logging',
                'default'     => 'no',
                'description' => sprintf(
                    'Log bKash PGW events inside <code>%s</code>',
                    esc_html( wc_get_log_file_path( $this->id ) )
                ),
            ),
            'enable_b2c'         => array(
                'title'       => 'Enable B2C API',
                'type'        => 'checkbox',
                'label'       => 'Enable B2C API',
                'default'     => 'no',
                'description' => 'Enable B2C Disbursement API',
            ),
            'webhook'            => array(
                'title'       => 'Webhook',
                'type'        => 'checkbox',
                'label'       => 'Enable Webhook listener',
                'default'     => 'no',
                'description' => 'Demo',//sprintf(
                    // 'Share this webhook URL to bKash team - <code>%s</code>',
                    // esc_url( $this->siteUrl . BKASH_FW_WC_API . $this->WEBHOOK_URL )
                // ),
            ),
            'sandbox'            => array(
                'title'       => 'Sandbox',
                'label'       => 'Enable Sandbox Mode',
                'type'        => 'checkbox',
                'description' => 'If Enabled, Sandbox mode will be applied (real payments will not be taken).',
                'default'     => 'yes',
            ),
            'username'           => array(
                'title'       => 'Username',
                'type'        => 'text',
                'description' => 'Get your Username from your bKash PGW account.',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'password'           => array(
                'title'       => 'Password',
                'type'        => 'password',
                'description' => 'Get your password from your bKash PGW account.',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'app_key'            => array(
                'title'       => 'Application Key',
                'type'        => 'text',
                'description' => 'Get your App Key from your bKash PGW account.',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'app_secret'         => array(
                'title'       => 'Application Secret Key',
                'type'        => 'password',
                'description' => 'Get your App Secret from your bKash PGW account.',
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * You will need it if you want your custom credit card form, Step 4 is about it
     */
    public function payment_fields() {
        $description = $this->get_description();

        if ( $this->sandbox === 'yes' ) {
            $description .= ' (IN SANDBOX)';
        }

        if ( ! empty( $description ) ) {
            echo wpautop( wptexturize( trim( $description ) ) );
        }        
    }

    /*
     * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
     */
    public function payment_scripts() {

    }

    /*
     * Fields validation, more in Step 5
     */
    public function validate_fields() {


    }

    /*
     * We're processing the payments here, everything about it is in Step 5
     */
    public function process_payment( $order_id ) {
        $cbURL = get_site_url() . BKASH_DC_WC_API . $this->CALLBACK_URL . '?orderId=' . $order_id;

	    return ( new Payments( $this->integration_type ) )->createPayment( $order_id, $this->intent, $cbURL );     
    }


    /**
 * Check if SSL is enabled and notify the user.
 *
 * @access public
 */
    final public function checks() {
        if ( $this->enabled === 'no' ) {
            return;
        }

        // PHP Version.
        if ( PHP_VERSION_ID < 50300 ) {
            echo wp_kses_post(
                '<div class="error version-error"><p>bKash PGW Error: ' . sprintf(
                    'bKash PGW Error: bKash PGW requires PHP 5.3 and above. You are using version %s.',
                    esc_html( PHP_VERSION )
                ) . '</p></div>'
            );
        } elseif ( ! $this->app_key || ! $this->app_secret ) { // Check required fields.
            echo '<div class="error app-key-error"><p>bKash PGW Error: Please enter your app keys and secrets</p></div>';
        } elseif ( 'BDT' !== get_woocommerce_currency() ) {
            echo '<div class="error currency-error"><p>bKash PGW Error: Only supports BDT as Currency</p></div>';
        } elseif ( ! class_exists( 'WordPressHTTPS' ) && 'no' === get_option( 'woocommerce_force_ssl_checkout' ) && ! is_ssl()
        ) {
            // Show message if enabled and FORCE SSL is disabled and WordPress HTTPS plugin is not detected.
            $admin_checkout_setting_url = esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>
            <div class="error ssl-error"><p>bKash PGW is enabled, but the <a href="
            <?php
                    esc_html_e( $admin_checkout_setting_url, 'bkash-for-woocommerce' );
            ?>
                    ">Force SSL option</a> is
                    disabled;
                    your payment may not be secure! Please enable SSL and ensure your server has a valid SSL
                    certificate -
                    bKash PGW will only work in sandbox mode.</p></div>
            <?php
        }

        // APP KEY APP SECRET CHECK
        if ( empty( $this->app_key ) || empty( $this->app_secret ) || empty( $this->username ) || empty( $this->password ) ) {
            $this->appKeyMissingNotice();
        }
    }

    /**
     * WooCommerce Payment Gateway App key missing Notice.
     *
     * @access public
     */
    final public function appKeyMissingNotice() {
        $notice = '<div class="error woocommerce-message wc-connect">
                <p>Please set bKash PGW credentials for accepting payments!</p></div>';
        add_action( 'admin_notices', $notice );
    }

    final public function createPaymentCallbackProcess() {
        $order_id = Sanitizer::safePostValue( 'orderId' );
        if ( ! $order_id ) {
            $order_id = Sanitizer::safeGetValue( 'orderId' );
        }

        // To receive order id
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $cbURL = get_site_url() . BKASH_DC_WC_API . $this->CALLBACK_URL . '?orderId=' . $order_id;

            $process = new Payments( $this->integration_type );
            Log::debug( wp_json_encode($cbURL) );
            $process->executePayment( $this->get_return_url( $order ), $cbURL );
        } else {
            echo wp_json_encode(
                array(
                    'result'  => 'failure',
                    'message' => 'Order not found',
                )
            );
        }
        die();
    }


    /*
     * In case you need a webhook, like PayPal IPN etc
     */
    public function webhook() {
                
    }
}