<?php

namespace PaymentPage\PaymentGateway;

use  PaymentPage\API\PaymentPage as API_PaymentPage ;
use  PaymentPage\Settings as API_Settings ;
use  Stripe\Stripe as Stripe_Library ;
use  Stripe\StripeClient ;
class Stripe extends Skeleton
{
    protected  $_stripeClient ;
    public static function setup_start_connection( $options ) : array
    {
        $response = API_PaymentPage::instance()->request( 'stripe/connect', [
            'is_live' => ( isset( $options['is_live'] ) ? intval( $options['is_live'] ) : 0 ),
        ], 'POST' );
        if ( !isset( $response['url'] ) ) {
            return [
                'status' => 'error',
                'error'  => __( "Could not determine the connection URL, please try again later.", "payment-page" ),
            ];
        }
        return [
            'status' => 'ok',
            'type'   => 'redirect',
            'url'    => $response['url'],
        ];
    }
    
    public static function save_master_credentials_response( $credentials ) : bool
    {
        if ( !is_array( $credentials ) ) {
            return false;
        }
        if ( !isset( $credentials['access_token'] ) || !isset( $credentials['stripe_publishable_key'] ) || !isset( $credentials['stripe_user_id'] ) || !isset( $credentials['is_live'] ) ) {
            return false;
        }
        API_Settings::instance()->update( [
            'stripe_is_live'                                                                      => intval( $credentials['is_live'] ),
            'stripe_' . (( intval( $credentials['is_live'] ) ? 'live' : 'test' )) . '_user_id'    => $credentials['stripe_user_id'],
            'stripe_' . (( intval( $credentials['is_live'] ) ? 'live' : 'test' )) . '_public_key' => $credentials['stripe_publishable_key'],
            'stripe_' . (( intval( $credentials['is_live'] ) ? 'live' : 'test' )) . '_secret_key' => $credentials['access_token'],
        ] );
        _payment_page_stripe_payment_methods_background_setup( false );
        return true;
    }
    
    protected  $_account_id ;
    protected  $_public_key ;
    protected  $_secret_key ;
    protected  $_is_live ;
    public function get_account_id()
    {
        return $this->_account_id;
    }
    
    public function get_public_key()
    {
        return $this->_public_key;
    }
    
    public function get_secret_key()
    {
        return $this->_secret_key;
    }
    
    public function is_live()
    {
        return $this->_is_live;
    }
    
    public function stripeClient()
    {
        if ( $this->_stripeClient !== null ) {
            return $this->_stripeClient;
        }
        if ( !class_exists( '\\Stripe\\StripeClient' ) ) {
            require_once PAYMENT_PAGE_BASE_PATH . "/lib/stripe/init.php";
        }
        Stripe_Library::setAppInfo( 'Payment Page WordPress Plugin', PAYMENT_PAGE_VERSION, 'https://payment.page' );
        $this->_stripeClient = new StripeClient( $this->_secret_key );
        return $this->_stripeClient;
    }
    
    /**
     * @return $this
     */
    public function attach_settings_credentials( $is_live = null )
    {
        if ( $is_live === null ) {
            $is_live = intval( API_Settings::instance()->get( 'stripe_is_live' ) );
        }
        
        if ( $is_live ) {
            $this->_account_id = API_Settings::instance()->get( 'stripe_live_user_id' );
            $this->_public_key = API_Settings::instance()->get( 'stripe_live_public_key' );
            $this->_secret_key = API_Settings::instance()->get( 'stripe_live_secret_key' );
            $this->_is_live = 1;
        } else {
            $this->_account_id = API_Settings::instance()->get( 'stripe_test_user_id' );
            $this->_public_key = API_Settings::instance()->get( 'stripe_test_public_key' );
            $this->_secret_key = API_Settings::instance()->get( 'stripe_test_secret_key' );
            $this->_is_live = 0;
        }
        
        $this->_stripeClient = null;
        return $this;
    }
    
    public function is_configured() : bool
    {
        return $this->get_public_key() !== '';
    }
    
    public function delete_settings_credentials( $is_live = true )
    {
        
        if ( $is_live ) {
            API_Settings::instance()->update( [
                'stripe_live_user_id'    => '',
                'stripe_live_public_key' => '',
                'stripe_live_secret_key' => '',
            ] );
        } else {
            API_Settings::instance()->update( [
                'stripe_test_user_id'    => '',
                'stripe_test_public_key' => '',
                'stripe_test_secret_key' => '',
            ] );
        }
        
        delete_transient( PAYMENT_PAGE_ALIAS . '_stripe_apple_pay_domain' );
    }
    
    public function attach_credentials( $credentials )
    {
        $this->_account_id = $credentials['account_id'];
        $this->_public_key = $credentials['public_key'];
        $this->_secret_key = $credentials['private_key'];
        $this->_is_live = $credentials['is_live'];
        $this->_stripeClient = null;
    }
    
    public function get_name() : string
    {
        return __( "Stripe", "payment-page" );
    }
    
    public function get_logo_url() : string
    {
        return plugins_url( 'interface/img/payment-gateway/logo-stripe.png', PAYMENT_PAGE_BASE_FILE_PATH );
    }
    
    public function get_description() : string
    {
        $response = __( "Stripe is one of the best payment gateways to accept one-time and recurring payments, with multiple payment methods and currencies.", "payment-page" );
        
        if ( payment_page_fs()->is_free_plan() ) {
            $response .= ' ' . __( "Note: The free version of Payment Page charges a small 2% fee on Stripe transactions in order to help us continue providing great features to the community.", "payment-page" );
            $response .= ' ' . sprintf( __( "To remove the fee and get access to useful tools like recurring subscription payments, automations, and more, please %s", "payment-page" ), '<a target="_blank" data-payment-page-component-admin-dashboard-trigger="upgrade" href="' . payment_page_fs()->get_upgrade_url() . '">Upgrade ></a>' );
        }
        
        return $response;
    }
    
    public function get_account_name() : string
    {
        if ( empty($this->get_account_id()) ) {
            return '';
        }
        $account_name = get_transient( PAYMENT_PAGE_ALIAS . '_stripe_account_name_' . $this->get_account_id() );
        
        if ( empty($account_name) ) {
            $account_information = $this->stripeClient()->accounts->retrieve( $this->get_account_id() );
            $account_name = sanitize_text_field( $account_information->settings->dashboard->display_name );
            set_transient( PAYMENT_PAGE_ALIAS . '_stripe_account_name_' . $this->get_account_id(), $account_name, DAY_IN_SECONDS );
        }
        
        return $account_name;
    }
    
    /**
     * @todo Implement this properly, business name is not present in the retrieve account information, need to figure out a work-around
     * @return string
     */
    public function get_business_name() : string
    {
        return $this->get_account_name();
    }
    
    public function get_account_country_code() : string
    {
        if ( empty($this->get_account_id()) ) {
            return '';
        }
        $country = get_transient( PAYMENT_PAGE_ALIAS . '_stripe_account_country_' . $this->get_account_id() );
        
        if ( empty($country) ) {
            $account_information = $this->stripeClient()->accounts->retrieve( $this->get_account_id() );
            $country = sanitize_text_field( $account_information->country );
            set_transient( PAYMENT_PAGE_ALIAS . '_stripe_account_country_' . $this->get_account_id(), $country, DAY_IN_SECONDS );
        }
        
        return $country;
    }
    
    public function get_payment_methods_administration() : array
    {
        $apple_pay_description = '<p>' . '<span>' . __( "Apple Pay", 'payment-page' ) . '</span>' . '<img alt="apple pay" src="' . plugins_url( 'interface/img/payment-gateway/payment-method-apple-pay.svg', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '</p>' . '<p>' . __( "Apple Pay enables frictionless card payments and eliminates the need to manually type card or shipping details.", 'payment-page' ) . '</p>';
        $verified_domains = get_transient( PAYMENT_PAGE_ALIAS . '_stripe_apple_pay_domain' );
        $current_domain = payment_page_domain_name();
        
        if ( $verified_domains === false ) {
            $verified_domains = [
                'live' => 0,
                'test' => 0,
            ];
            $stripeLive = new self();
            $stripeTest = new self();
            $stripeLive->attach_settings_credentials( 1 );
            $stripeTest->attach_settings_credentials( 0 );
            
            if ( !empty($stripeLive->get_account_id()) ) {
                $liveAppleDomains = $stripeLive->stripeClient()->applePayDomains->all();
                foreach ( $liveAppleDomains['data'] as $current_live_apple_domain ) {
                    if ( $current_live_apple_domain['domain_name'] === $current_domain ) {
                        $verified_domains['live'] = 1;
                    }
                }
            }
            
            
            if ( !empty($stripeTest->get_account_id()) ) {
                $testAppleDomains = $stripeTest->stripeClient()->applePayDomains->all();
                foreach ( $testAppleDomains['data'] as $current_test_apple_domain ) {
                    if ( $current_test_apple_domain['domain_name'] === $current_domain ) {
                        $verified_domains['test'] = 1;
                    }
                }
            }
            
            set_transient( PAYMENT_PAGE_ALIAS . '_stripe_apple_pay_domain', $verified_domains, DAY_IN_SECONDS );
        }
        
        if ( $verified_domains['live'] || $verified_domains['test'] ) {
            $apple_pay_description .= '<p>' . sprintf( __( "Apple Domain Verified for %s", "payment-page" ), implode( ',', ( $verified_domains['live'] && $verified_domains['test'] ? [ '<span data-payment-page-mode="live">Live</span>', '<span data-payment-page-mode="test">Test</span>' ] : (( $verified_domains['live'] ? [ '<span data-payment-page-mode="live">Live</span>' ] : [ '<span data-payment-page-mode="test">Test</span>' ] )) ) ) ) . '</p>';
        }
        $apple_pay_description .= '<p><a href="https://dashboard.stripe.com/settings/payments/apple_pay" target="_blank">' . __( "Manage in Stripe", "payment-page" ) . '</a></p>';
        $response = [
            'ccard'            => [
            'name'                     => __( "Credit Cards", 'payment-page' ),
            'alias'                    => 'ccard',
            'is_available'             => 1,
            'is_upgradeable_recurring' => ( payment_page_fs()->is_free_plan() ? 1 : 0 ),
            'description'              => '<p>' . '<span>' . __( "Credit Cards", 'payment-page' ) . '</span>' . '<img alt="visa" src="' . plugins_url( 'interface/img/payment-gateway/logo-method-visa.png', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '<img alt="mastercard" src="' . plugins_url( 'interface/img/payment-gateway/logo-method-mastercard.png', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '<img alt="american express" src="' . plugins_url( 'interface/img/payment-gateway/logo-method-american-express.png', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '<img alt="diners club" src="' . plugins_url( 'interface/img/payment-gateway/logo-method-diners-club.png', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '<img alt="jcb" src="' . plugins_url( 'interface/img/payment-gateway/logo-method-jcb.png', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '</p>' . '<p>' . __( "Accept Visa, Mastercard, American Express, Discover, Diners Club, and JCB payments from customers worldwide.", 'payment-page' ) . '</p>',
        ],
            'ach_direct_debit' => [
            'name'                     => __( "ACH Direct Debit", 'payment-page' ),
            'upgrade_name'             => __( "ACH payments with the Stripe + Plaid integration", "payment-page" ),
            'alias'                    => 'ach_direct_debit',
            'is_available'             => ( payment_page_fs()->is_free_plan() ? 0 : 1 ),
            'is_upgradeable_recurring' => ( payment_page_fs()->is_free_plan() ? 1 : 0 ),
            'description'              => '<p>' . '<span>' . __( "ACH Direct Debit", 'payment-page' ) . '</span>' . '<img alt="ach direct debit" src="' . plugins_url( 'interface/img/payment-gateway/payment-method-ach-direct-debit.svg', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '</p>' . '<p>' . __( "Plaid provides the quickest way to collect and verify your customer’s banking information. Using the Stripe + Plaid integration, you can instantly receive a verified bank account, which allows for immediate charging.", 'payment-page' ) . '</p>',
            'settings'                 => [
            'plaid' => [
            'title'                   => __( "Plaid Settings %s", "payment-page" ),
            'test_configured'         => ( payment_page_setting_get( 'stripe_test_plaid_client_id' ) !== '' && payment_page_setting_get( 'stripe_test_plaid_secret' ) !== '' ? 1 : 0 ),
            'test_fields_description' => sprintf( __( "Enter your Client ID and Secret Sandbox key from the %s", "payment-page" ), '<a href="https://dashboard.plaid.com/team/keys" target="_blank">' . __( "Plaid Dashboard", "payment-page" ) . '</a>' ),
            'test_fields'             => [
            'stripe_test_plaid_client_id' => [
            'label' => __( "Client ID", "payment-page" ),
            'type'  => 'text',
            'name'  => 'stripe_test_plaid_client_id',
            'order' => 1,
            'value' => payment_page_setting_get( 'stripe_test_plaid_client_id' ),
        ],
            'stripe_test_plaid_secret'    => [
            'label' => __( "Secret", "payment-page" ),
            'type'  => 'text',
            'name'  => 'stripe_test_plaid_secret',
            'order' => 2,
            'value' => payment_page_setting_get( 'stripe_test_plaid_secret' ),
        ],
        ],
            'live_configured'         => ( payment_page_setting_get( 'stripe_live_plaid_client_id' ) !== '' && payment_page_setting_get( 'stripe_live_plaid_secret' ) !== '' ? 1 : 0 ),
            'live_fields_description' => sprintf( __( "Enter your Client ID and Development or Production key from the %s", "payment-page" ), '<a href="https://dashboard.plaid.com/team/keys" target="_blank">' . __( "Plaid Dashboard", "payment-page" ) . '</a>' ),
            'live_fields'             => [
            'stripe_live_plaid_client_id'   => [
            'label' => __( "Client ID", "payment-page" ),
            'type'  => 'text',
            'name'  => 'stripe_live_plaid_client_id',
            'order' => 1,
            'value' => payment_page_setting_get( 'stripe_live_plaid_client_id' ),
        ],
            'stripe_live_plaid_environment' => [
            'label'       => __( "Environment", "payment-page" ),
            'type'        => 'select',
            'options'     => [
            'development' => "Development",
            'production'  => "Production",
        ],
            'description' => '<p>' . sprintf( __( "%s - Build out your app with up to 100 live credentials", "payment-page" ), '<strong>Development</strong>' ) . '</p>' . '<p>' . sprintf( __( "%s - Launch your app with unlimited live credentials", "payment-page" ), '<strong>Production</strong>' ) . '</p>',
            'name'        => 'stripe_live_plaid_environment',
            'order'       => 2,
            'value'       => payment_page_setting_get( 'stripe_live_plaid_environment' ),
        ],
            'stripe_live_plaid_secret'      => [
            'label' => __( "Secret", "payment-page" ),
            'type'  => 'text',
            'name'  => 'stripe_live_plaid_secret',
            'order' => 3,
            'value' => payment_page_setting_get( 'stripe_live_plaid_secret' ),
        ],
        ],
        ],
        ],
        ],
            'sepa'             => [
            'name'                     => __( "SEPA Direct Debit", 'payment-page' ),
            'alias'                    => 'sepa',
            'is_available'             => 1,
            'is_upgradeable_recurring' => ( payment_page_fs()->is_free_plan() ? 1 : 0 ),
            'description'              => '<p>' . '<span>' . __( "SEPA Direct Debit", 'payment-page' ) . '</span>' . '<img alt="sepa" src="' . plugins_url( 'interface/img/payment-gateway/payment-method-sepa.svg', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '</p>' . '<p>' . __( "SEPA Direct Debit enables customers in the Single Euro Payments Area (SEPA) to pay by providing their bank account details. Customers must accept a mandate authorising you to debit their account.", 'payment-page' ) . '</p>',
        ],
            'apple_pay'        => [
            'name'                     => __( "Apple Pay", 'payment-page' ),
            'alias'                    => 'apple_pay',
            'is_available'             => 1,
            'is_upgradeable_recurring' => ( payment_page_fs()->is_free_plan() ? 1 : 0 ),
            'requires_https'           => 1,
            'description'              => $apple_pay_description,
        ],
            'google_pay'       => [
            'name'                     => __( "Google Pay", 'payment-page' ),
            'alias'                    => 'google_pay',
            'is_available'             => 1,
            'is_upgradeable_recurring' => ( payment_page_fs()->is_free_plan() ? 1 : 0 ),
            'requires_https'           => 1,
            'description'              => '<p>' . '<span>' . __( "Google Pay", 'payment-page' ) . '</span>' . '<img alt="google pay" src="' . plugins_url( 'interface/img/payment-gateway/payment-method-google-pay.svg', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '</p>' . '<p>' . __( "Google Pay allows customers to make payments in your app or website using any credit or debit card saved to their Google Account, including those from Google Play, YouTube, Chrome, or an Android device.", 'payment-page' ) . '</p>',
        ],
            'microsoft_pay'    => [
            'name'                     => __( "Microsoft Pay", 'payment-page' ),
            'alias'                    => 'microsoft_pay',
            'is_available'             => 1,
            'is_upgradeable_recurring' => ( payment_page_fs()->is_free_plan() ? 1 : 0 ),
            'requires_https'           => 1,
            'description'              => '<p>' . '<span>' . __( "Microsoft Pay", 'payment-page' ) . '</span>' . '<img alt="microsoft pay" src="' . plugins_url( 'interface/img/payment-gateway/payment-method-microsoft-pay.svg', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '</p>' . '<p>' . __( "Microsoft Pay (previously Microsoft Wallet) is a mobile payment and digital wallet service by Microsoft that lets users make payments and store loyalty cards on certain devices. Making payments is currently supported on the Microsoft Edge browser.", 'payment-page' ) . '</p>',
        ],
            'alipay'           => [
            'name'                     => __( "Alipay", 'payment-page' ),
            'alias'                    => 'alipay',
            'is_available'             => 1,
            'is_upgradeable_recurring' => 0,
            'description'              => '<p>' . '<span>' . __( "Alipay", 'payment-page' ) . '</span>' . '<img alt="alipay" src="' . plugins_url( 'interface/img/payment-gateway/payment-method-alipay.svg', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '</p>' . '<p>' . __( "Alipay enables Chinese consumers to pay directly via online transfer from their bank account. Customers are redirected to Alipay's payment page to log in and approve payments.", 'payment-page' ) . '</p>',
        ],
            'wechat'           => [
            'name'                     => __( "WeChat Pay", 'payment-page' ),
            'alias'                    => 'wechat',
            'is_available'             => 1,
            'is_upgradeable_recurring' => 0,
            'description'              => '<p>' . '<span>' . __( "WeChat Pay", 'payment-page' ) . '</span>' . '<img alt="wechat" src="' . plugins_url( 'interface/img/payment-gateway/payment-method-wechat-pay.svg', PAYMENT_PAGE_BASE_FILE_PATH ) . '"/>' . '</p>' . '<p>' . __( "WeChat Pay enables Chinese consumers to pay directly via online transfer from their account. Customers are given a QR Code to scan using their WeChat mobile application to approve payments.", 'payment-page' ) . '</p>',
        ],
        ];
        return apply_filters( 'payment_page_stripe_payment_methods_administration', $response );
    }
    
    public function get_payment_methods_frontend( $active_payment_methods ) : array
    {
        $response = [];
        if ( in_array( 'ccard', $active_payment_methods ) ) {
            $response[] = [
                'id'                    => 'ccard',
                'name'                  => __( "Credit Card", "payment-page" ),
                'payment_method'        => 'ccard',
                'has_recurring_support' => ( !payment_page_fs()->is_free_plan() ? 1 : 0 ),
                'image'                 => plugins_url( 'interface/img/payment-gateway/payment-method-credit-card.svg', PAYMENT_PAGE_BASE_FILE_PATH ),
            ];
        }
        
        if ( in_array( 'sepa', $active_payment_methods ) ) {
            $disclaimer_text = __( 'By providing your payment information and confirming this payment, you authorise (A) %s and Stripe, our payment service provider and/or PPRO, its local service provider, to send instructions to your bank to debit your account and (B) your bank to debit your account in accordance with those instructions. As part of your rights, you are entitled to a refund from your bank under the terms and conditions of your agreement with your bank. A refund must be claimed within 8 weeks starting from the date on which your account was debited. Your rights are explained in a statement that you can obtain from your bank. You agree to receive notifications for future debits up to 2 days before they occur.', 'payment-page' );
            $response[] = [
                'id'                    => 'sepa',
                'name'                  => __( "SEPA Direct Debit", "payment-page" ),
                'payment_method'        => 'sepa',
                'has_recurring_support' => ( !payment_page_fs()->is_free_plan() ? 1 : 0 ),
                'image'                 => plugins_url( 'interface/img/payment-gateway/payment-method-sepa.svg', PAYMENT_PAGE_BASE_FILE_PATH ),
                'disclaimer'            => sprintf( $disclaimer_text, $this->get_business_name() ),
                'currencies'            => [ 'eur' ],
            ];
        }
        
        if ( in_array( 'apple_pay', $active_payment_methods ) ) {
            $response[] = [
                'id'                    => 'apple_pay',
                'name'                  => __( "Apple Pay", 'payment-page' ),
                'payment_method'        => 'apple_pay',
                'has_recurring_support' => ( !payment_page_fs()->is_free_plan() ? 1 : 0 ),
                'image'                 => plugins_url( 'interface/img/payment-gateway/payment-method-apple-pay.svg', PAYMENT_PAGE_BASE_FILE_PATH ),
            ];
        }
        if ( in_array( 'google_pay', $active_payment_methods ) ) {
            $response[] = [
                'id'                    => 'google_pay',
                'name'                  => __( "Google Pay", 'payment-page' ),
                'payment_method'        => 'google_pay',
                'has_recurring_support' => ( !payment_page_fs()->is_free_plan() ? 1 : 0 ),
                'image'                 => plugins_url( 'interface/img/payment-gateway/payment-method-google-pay.svg', PAYMENT_PAGE_BASE_FILE_PATH ),
            ];
        }
        if ( in_array( 'microsoft_pay', $active_payment_methods ) ) {
            $response[] = [
                'id'                    => 'microsoft_pay',
                'name'                  => __( "Microsoft Pay", 'payment-page' ),
                'payment_method'        => 'microsoft_pay',
                'has_recurring_support' => ( !payment_page_fs()->is_free_plan() ? 1 : 0 ),
                'image'                 => plugins_url( 'interface/img/payment-gateway/payment-method-microsoft-pay.svg', PAYMENT_PAGE_BASE_FILE_PATH ),
            ];
        }
        if ( in_array( 'alipay', $active_payment_methods ) ) {
            $response[] = [
                'id'                    => 'alipay',
                'name'                  => __( "Alipay", "payment-page" ),
                'payment_method'        => 'alipay',
                'has_recurring_support' => 0,
                'image'                 => plugins_url( 'interface/img/payment-gateway/payment-method-alipay.svg', PAYMENT_PAGE_BASE_FILE_PATH ),
            ];
        }
        if ( in_array( 'wechat', $active_payment_methods ) ) {
            $response[] = [
                'id'                    => 'wechat',
                'name'                  => __( "WeChat Pay", "payment-page" ),
                'payment_method'        => 'wechat',
                'has_recurring_support' => 0,
                'image'                 => plugins_url( 'interface/img/payment-gateway/payment-method-wechat-pay.svg', PAYMENT_PAGE_BASE_FILE_PATH ),
            ];
        }
        return apply_filters( 'payment_page_stripe_payment_methods_frontend', $response, $active_payment_methods );
    }
    
    public function get_webhook_settings_administration() : array
    {
        $live_fields_description = sprintf( __( "Create a webhook in the %s, events to send : %s", "payment-page" ), '<a href="https://dashboard.stripe.com/webhooks" target="_blank">' . __( "Stripe Webhooks Settings", "payment-page" ) . '</a>', '<strong>payment_intent.succeeded</strong> & <strong>setup_intent.succeeded</strong>' . '<p>' . sprintf( __( "Our %s covers how to configure Webhooks properly.", "payment-page" ), '<a href="https://docs.payment.page/" target="_blank">' . __( "Documentation", "payment-page" ) . '</a>' ) . '</p>' );
        return [
            'title'                   => __( "Webhook Settings (Recommended)", "payment-page" ),
            'title_popup'             => __( "Webhook Settings", "payment-page" ),
            'test_configured'         => ( payment_page_setting_get( 'stripe_test_webhook_secret' ) !== '' ? 1 : 0 ),
            'test_available'          => payment_page_setting_get( 'stripe_test_public_key' ) !== '',
            'test_fields_description' => '<p>' . sprintf( __( "Create an Endpoint in the %s, to send the events : %s", "payment-page" ), '<a href="https://dashboard.stripe.com/test/webhooks" target="_blank">' . __( "Stripe Webhooks Settings", "payment-page" ) . '</a>', '<strong>payment_intent.succeeded</strong> & <strong>setup_intent.succeeded</strong>' ) . '</p>' . '<p>' . sprintf( __( "Our %s covers how to configure Webhooks properly.", "payment-page" ), '<a href="https://docs.payment.page/" target="_blank">' . __( "Documentation", "payment-page" ) . '</a>' ) . '</p>',
            'test_fields'             => [
            'stripe_test_webhook_url'    => [
            'label' => __( "Webhook URL", "payment-page" ),
            'type'  => 'textarea_disabled',
            'name'  => 'stripe_test_webhook_url',
            'order' => 1,
            'value' => rest_url() . PAYMENT_PAGE_REST_API_PREFIX . '/v1/webhook/stripe-callback/test',
        ],
            'stripe_test_webhook_secret' => [
            'label' => __( "Webhook Signing Secret", "payment-page" ),
            'type'  => 'text',
            'name'  => 'stripe_test_webhook_secret',
            'order' => 2,
            'value' => payment_page_setting_get( 'stripe_test_webhook_secret' ),
        ],
        ],
            'live_configured'         => ( payment_page_setting_get( 'stripe_live_webhook_secret' ) !== '' ? 1 : 0 ),
            'live_available'          => payment_page_setting_get( 'stripe_live_public_key' ) !== '',
            'live_fields_description' => $live_fields_description,
            'live_fields'             => [
            'stripe_live_webhook_url'    => [
            'label' => __( "Webhook URL", "payment-page" ),
            'type'  => 'textarea_disabled',
            'name'  => 'stripe_live_webhook_url',
            'order' => 1,
            'value' => rest_url() . PAYMENT_PAGE_REST_API_PREFIX . '/v1/webhook/stripe-callback/live',
        ],
            'stripe_live_webhook_secret' => [
            'label' => __( "Webhook Signing Secret", "payment-page" ),
            'type'  => 'text',
            'name'  => 'stripe_live_webhook_secret',
            'order' => 2,
            'value' => payment_page_setting_get( 'stripe_live_webhook_secret' ),
        ],
        ],
        ];
    }

}