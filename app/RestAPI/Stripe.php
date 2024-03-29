<?php

namespace PaymentPage\RestAPI;

use  PaymentPage\ThirdPartyIntegration\Freemius as PP_Freemius ;
use  WP_Error ;
use  WP_REST_Server ;
use  WP_REST_Request ;
use  PaymentPage\API\PaymentPage as API_PaymentPage ;
use  PaymentPage\PaymentGateway ;
use  PaymentPage\Model\StripeCustomers as Stripe_Customer ;
use  PaymentPage\Model\StripeProducts as Stripe_Products ;
use  PaymentPage\Model\StripePrices as Stripe_Prices ;
class Stripe
{
    public static function register_routes()
    {
        register_rest_route( PAYMENT_PAGE_REST_API_PREFIX . '/v1', '/stripe/payment-intent-or-setup', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => '\\PaymentPage\\RestAPI\\Stripe::payment_intent_or_setup',
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( PAYMENT_PAGE_REST_API_PREFIX . '/v1', '/stripe/checkout', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => '\\PaymentPage\\RestAPI\\Stripe::checkout',
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( PAYMENT_PAGE_REST_API_PREFIX . '/v1', '/stripe/plaid-link-token', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => '\\PaymentPage\\RestAPI\\Stripe::plaid_link_token',
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( PAYMENT_PAGE_REST_API_PREFIX . '/v1', '/stripe/plaid-link-confirm', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => '\\PaymentPage\\RestAPI\\Stripe::plaid_link_confirm',
            'permission_callback' => '__return_true',
        ] );
    }
    
    public static function payment_intent_or_setup( WP_REST_Request $request )
    {
        foreach ( [ 'post_id', 'payment_method' ] as $required_param ) {
            if ( !$request->has_param( $required_param ) ) {
                return new WP_Error( 'rest_error', esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ), [
                    'status' => 400,
                ] );
            }
        }
        $stripe_customer_id = self::_getCustomerID( $request );
        if ( is_wp_error( $stripe_customer_id ) ) {
            return $stripe_customer_id;
        }
        $stripePrice = self::_getStripePrice( $request );
        if ( is_wp_error( $stripePrice ) ) {
            return $stripePrice;
        }
        return rest_ensure_response( API_PaymentPage::instance()->request( 'stripe/payment-intent-or-setup', [
            'account_id'                  => PaymentGateway::get_integration_from_settings( 'stripe' )->get_account_id(),
            'customer_id'                 => $stripe_customer_id,
            'payment_method'              => $request->get_param( 'payment_method' ),
            'payment_method_id'           => ( $request->has_param( 'stripe_payment_method_id' ) ? $request->get_param( 'stripe_payment_method_id' ) : '' ),
            'price_information'           => [
            'is_recurring' => ( $stripePrice->recurring === null ? 0 : 1 ),
            'amount'       => $stripePrice->unit_amount,
            'currency'     => $stripePrice->currency,
        ],
            'price_description'           => self::_getDescription( $request ),
            'price_statement'             => self::_getStatementDescriptor( $request ),
            'is_live'                     => intval( PaymentGateway::get_integration_from_settings( 'stripe' )->is_live() ),
            'meta_data'                   => self::_getGeneralMetaData( $request ),
            'mandate_customer_acceptance' => [
            'ip_address' => payment_page_http_ip_address(),
            'user_agent' => payment_page_http_user_agent(),
        ],
            'secret_key'                  => payment_page_encrypt( PaymentGateway::get_integration_from_settings( 'stripe' )->get_secret_key(), PP_Freemius::instance()->get_anonymous_id(), md5( get_site_url() ) ),
        ], 'POST' ) );
    }
    
    public static function checkout( WP_REST_Request $request )
    {
        foreach ( [ 'post_id' ] as $required_param ) {
            if ( !$request->has_param( $required_param ) ) {
                return new WP_Error( 'rest_error', esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ), [
                    'status' => 400,
                ] );
            }
        }
        $stripe_customer_id = self::_getCustomerID( $request );
        if ( is_wp_error( $stripe_customer_id ) ) {
            return $stripe_customer_id;
        }
        try {
            $stripePrice = self::_getStripePrice( $request );
            if ( is_wp_error( $stripePrice ) ) {
                return $stripePrice;
            }
            
            if ( $stripePrice->recurring ) {
                $subscription = self::_setupStripeSubscriptionUsingSetupIntent( $request, $stripe_customer_id, $stripePrice );
                if ( is_wp_error( $subscription ) ) {
                    return $subscription;
                }
            } else {
                $invoice = self::_chargeStripeOneTime( $request, $stripe_customer_id, $stripePrice );
                if ( is_wp_error( $invoice ) ) {
                    return $invoice;
                }
            }
        
        } catch ( \Exception $e ) {
            return new WP_Error( 'rest_error', esc_html( $e->getMessage() ), [
                'status' => 400,
            ] );
        }
        return rest_ensure_response( [
            'status' => 'ok',
        ] );
    }
    
    private static function _getCustomerID( WP_REST_Request $request )
    {
        foreach ( [
            'first_name',
            'last_name',
            'email_address',
            'price_currency',
            'price_frequency'
        ] as $required_param ) {
            if ( !$request->has_param( $required_param ) ) {
                return new WP_Error( 'rest_error', esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ), [
                    'status' => 400,
                ] );
            }
        }
        if ( !is_email( $request->get_param( 'email_address' ) ) ) {
            return new WP_Error( 'rest_error', esc_html( sprintf( __( "Invalid email address provided : %s", "payment-page" ), 'email_address' ) ), [
                'status' => 400,
            ] );
        }
        $stripeCustomer = Stripe_Customer::findOrCreate( [
            'is_live'               => ( PaymentGateway::get_integration_from_settings( 'stripe' )->is_live() ? 1 : 0 ),
            'email_address'         => $request->get_param( 'email_address' ),
            'stripe_account_id'     => PaymentGateway::get_integration_from_settings( 'stripe' )->get_account_id(),
            'subscription_currency' => ( $request->get_param( 'price_frequency' ) === 'one-time' ? 'none' : strtoupper( $request->get_param( 'price_currency' ) ) ),
        ] );
        $stripeClient = PaymentGateway::get_integration_from_settings( 'stripe' )->stripeClient();
        
        if ( $stripeCustomer->stripe_id === '' ) {
            $response = $stripeClient->customers->create( [
                'name'  => $request->get_param( 'first_name' ) . ' ' . $request->get_param( 'last_name' ),
                'email' => $stripeCustomer->email_address,
            ] );
            $stripeCustomer->stripe_id = $response->id;
            $stripeCustomer->save();
        }
        
        return $stripeCustomer->stripe_id;
    }
    
    /**
     * @param WP_REST_Request $request
     * @param string $type
     * @return \Stripe\Price|WP_Error
     * @throws \Stripe\Exception\ApiErrorException
     */
    private static function _getStripePrice( WP_REST_Request $request, $type = 'default' )
    {
        foreach ( [
            'product_title',
            'price_amount',
            'price_currency',
            'price_frequency'
        ] as $required_param ) {
            if ( !$request->has_param( $required_param ) ) {
                return new WP_Error( 'rest_error', esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ), [
                    'status' => 400,
                ] );
            }
        }
        $stripeClient = PaymentGateway::get_integration_from_settings( 'stripe' )->stripeClient();
        $stripeProduct = Stripe_Products::findOrCreate( [
            'title'             => sanitize_text_field( $request->get_param( 'product_title' ) ),
            'is_live'           => ( PaymentGateway::get_integration_from_settings( 'stripe' )->is_live() ? 1 : 0 ),
            'stripe_account_id' => PaymentGateway::get_integration_from_settings( 'stripe' )->get_account_id(),
        ] );
        
        if ( $stripeProduct->stripe_id === '' ) {
            $product = $stripeClient->products->create( [
                "name"     => $stripeProduct->title,
                "type"     => "service",
                "metadata" => [
                'plan_id' => $request->get_param( 'plan_id' ),
                'post_id' => intval( $request->get_param( 'post_id' ) ),
            ],
            ] );
            $stripeProduct->stripe_id = $product->id;
            $stripeProduct->save();
        } else {
            $product = $stripeClient->products->retrieve( $stripeProduct->stripe_id );
        }
        
        $stripePrice = Stripe_Prices::findOrCreate( [
            'product_id'        => $stripeProduct->id,
            'price'             => intval( floatval( ( $type === 'setup' ? $request->get_param( 'setup_price_amount' ) : $request->get_param( 'price_amount' ) ) ) * 100 ),
            'currency'          => strtoupper( $request->get_param( 'price_currency' ) ),
            'frequency'         => ( $request->has_param( 'price_frequency' ) ? $request->get_param( 'price_frequency' ) : 'one-time' ),
            'is_live'           => ( PaymentGateway::get_integration_from_settings( 'stripe' )->is_live() ? 1 : 0 ),
            'stripe_account_id' => PaymentGateway::get_integration_from_settings( 'stripe' )->get_account_id(),
        ] );
        
        if ( $stripePrice->stripe_id === '' ) {
            $recurring = false;
            $price = $stripeClient->prices->create( [
                "product"     => $product->id,
                "currency"    => strtoupper( $stripePrice->currency ),
                "unit_amount" => intval( $stripePrice->price ),
            ] + (( $recurring ? [
                'recurring' => $recurring,
            ] : [] )) );
            $stripePrice->stripe_product_id = $product->id;
            $stripePrice->stripe_id = $price->id;
            $stripePrice->save();
        } else {
            $price = $stripeClient->prices->retrieve( $stripePrice->stripe_id );
        }
        
        return $price;
    }
    
    private static function _getStatementDescriptor( WP_REST_Request $request, $stripePrice = null )
    {
        return substr( self::_getDescription( $request ), 0, 21 );
    }
    
    private static function _getDescription( WP_REST_Request $request, $stripePrice = null )
    {
        if ( $request->has_param( 'product_title' ) ) {
            return sanitize_text_field( $request->get_param( 'product_title' ) );
        }
        return PaymentGateway::get_integration_from_settings( 'stripe' )->get_business_name();
    }
    
    /**
     * @param WP_REST_Request $request
     * @return array
     */
    private static function _getGeneralMetaData( WP_REST_Request $request ) : array
    {
        $response = _payment_page_rest_api_custom_fields( $request );
        $response = _payment_page_payment_identification_fields( $request->get_param( 'post_id' ), $request->get_param( 'payment_id' ) ) + $response;
        $response['frequency'] = ( $request->has_param( 'price_frequency' ) ? $request->get_param( 'price_frequency' ) : 'one-time' );
        $response['user_id'] = intval( get_current_user_id() );
        return $response;
    }
    
    /**
     * @param WP_REST_Request $request
     * @param $stripe_customer_id
     * @param \Stripe\Price $price
     * @return \Stripe\Subscription|WP_Error
     * @throws \Stripe\Exception\ApiErrorException
     */
    private static function _setupStripeSubscriptionUsingSetupIntent( WP_REST_Request $request, $stripe_customer_id, \Stripe\Price $price )
    {
        foreach ( [ 'stripe_payment_method_id', 'stripe_payment_intent_or_setup_id' ] as $required_param ) {
            if ( !$request->has_param( $required_param ) ) {
                return new WP_Error( 'rest_error', esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ), [
                    'status' => 400,
                ] );
            }
        }
        $stripeClient = PaymentGateway::get_integration_from_settings( 'stripe' )->stripeClient();
        $payment_method = $stripeClient->paymentMethods->retrieve( $request->get_param( 'stripe_payment_method_id' ) );
        $payment_method->attach( [
            'customer' => $stripe_customer_id,
        ] );
        $stripeClient->customers->update( $stripe_customer_id, [
            'invoice_settings' => [
            'default_payment_method' => $payment_method->id,
        ],
        ] );
        return self::_subscriptionCreateHelper(
            [
            'description'            => self::_getDescription( $request ),
            'default_payment_method' => $payment_method->id,
            'customer'               => $stripe_customer_id,
            'items'                  => [ [
            'price' => $price->id,
        ] ],
            'metadata'               => self::_getGeneralMetaData( $request ),
        ],
            $price,
            $request,
            $stripeClient
        );
    }
    
    private static function _chargeStripeOneTime( WP_REST_Request $request, $stripe_customer_id, \Stripe\Price $price )
    {
        foreach ( [ 'stripe_payment_method_id', 'stripe_payment_intent_or_setup_id' ] as $required_param ) {
            if ( !$request->has_param( $required_param ) ) {
                return new WP_Error( 'rest_error', esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ), [
                    'status' => 400,
                ] );
            }
        }
        $stripeClient = PaymentGateway::get_integration_from_settings( 'stripe' )->stripeClient();
        $paymentIntent = $stripeClient->paymentIntents->retrieve( $request->get_param( 'stripe_payment_intent_or_setup_id' ) );
        
        if ( !in_array( 'sepa_debit', $paymentIntent->payment_method_types ) ) {
            $paymentIntent->capture();
            if ( $paymentIntent->status !== 'succeeded' ) {
                return new WP_Error( 'rest_error', __( "Payment failed. Please try again.", "payment-page" ), [
                    'status' => 400,
                ] );
            }
        }
        
        return true;
    }
    
    public static function plaid_link_token( WP_REST_Request $request )
    {
        $stripeCustomerID = self::_getCustomerID( $request );
        if ( is_wp_error( $stripeCustomerID ) ) {
            return $stripeCustomerID;
        }
        return rest_ensure_response( self::_plaidRequest( 'link/token/create', [
            'client_name'   => PaymentGateway::get_integration_from_settings( 'stripe' )->get_business_name(),
            'user'          => [
            'client_user_id' => $stripeCustomerID,
            'email_address'  => $request->get_param( 'email_address' ),
        ],
            'products'      => [ 'auth' ],
            'country_codes' => [ 'US' ],
            'language'      => 'en',
        ] ) );
    }
    
    public static function plaid_link_confirm( WP_REST_Request $request )
    {
        $stripeCustomerID = self::_getCustomerID( $request );
        if ( is_wp_error( $stripeCustomerID ) ) {
            return $stripeCustomerID;
        }
        foreach ( [ 'plaid_public_token', 'plaid_bank_account_id' ] as $required_param ) {
            if ( !$request->has_param( $required_param ) ) {
                return new WP_Error( 'rest_error', esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ), [
                    'status' => 400,
                ] );
            }
        }
        $stripeCustomerID = self::_getCustomerID( $request );
        if ( is_wp_error( $stripeCustomerID ) ) {
            return $stripeCustomerID;
        }
        $public_token_exchange = self::_plaidRequest( 'item/public_token/exchange', [
            'public_token' => $request->get_param( 'plaid_public_token' ),
        ] );
        if ( !isset( $public_token_exchange['access_token'] ) ) {
            return new WP_Error( 'rest_error', esc_html( ( isset( $public_token_exchange['data']['display_message'] ) && !empty($public_token_exchange['data']['display_message']) ? $public_token_exchange['data']['display_message'] : __( "Invalid Request", "payment-page" ) ) ), [
                'status' => 400,
            ] );
        }
        $bank_account_create = self::_plaidRequest( 'processor/stripe/bank_account_token/create', [
            'access_token' => $public_token_exchange['access_token'],
            'account_id'   => $request->get_param( 'plaid_bank_account_id' ),
        ] );
        if ( !isset( $bank_account_create['stripe_bank_account_token'] ) || empty($bank_account_create['stripe_bank_account_token']) ) {
            return new WP_Error( 'rest_error', esc_html( __( "Failed to connect bank account, please try again", "payment-page" ) ), [
                'status' => 400,
            ] );
        }
        $paymentGatewayStripe = PaymentGateway::get_integration_from_settings( 'stripe' );
        try {
            $stripeCustomer = $paymentGatewayStripe->stripeClient()->customers->update( $stripeCustomerID, [
                'source' => $bank_account_create['stripe_bank_account_token'],
            ] );
        } catch ( \Exception $e ) {
            return new WP_Error( 'rest_error', esc_html( __( "Failed to connect bank account, please try again", "payment-page" ) ), [
                'status' => 400,
            ] );
        }
        $payment_source_id = $stripeCustomer->default_source;
        try {
            $stripePrice = self::_getStripePrice( $request );
            if ( is_wp_error( $stripePrice ) ) {
                return $stripePrice;
            }
            
            if ( $stripePrice->recurring ) {
                $subscription_create_args = [
                    'default_source' => $payment_source_id,
                    'customer'       => $stripeCustomer->id,
                    'items'          => [ [
                    'price' => $stripePrice->id,
                ] ],
                    'metadata'       => self::_getGeneralMetaData( $request ),
                ];
                $subscription = self::_subscriptionCreateHelper(
                    $subscription_create_args,
                    $stripePrice,
                    $request,
                    $paymentGatewayStripe->stripeClient()
                );
            } else {
                $paymentGatewayStripe->stripeClient()->charges->create( [
                    'amount'      => $stripePrice->unit_amount,
                    'currency'    => strtoupper( $stripePrice->currency ),
                    'customer'    => $stripeCustomer->id,
                    'source'      => $payment_source_id,
                    'metadata'    => self::_getGeneralMetaData( $request ),
                    'capture'     => true,
                    'description' => self::_getDescription( $request, $stripePrice ),
                ] );
            }
        
        } catch ( \Exception $e ) {
            return new WP_Error( 'rest_error', esc_html( $e->getMessage() ), [
                'status' => 400,
            ] );
        }
        return rest_ensure_response( [
            'status' => 'ok',
        ] );
    }
    
    private static function _plaidRequest( $path, $args )
    {
        $paymentGatewayStripe = PaymentGateway::get_integration_from_settings( 'stripe' );
        $endpoint_sub_domain = ( $paymentGatewayStripe->is_live() ? ( payment_page_setting_get( 'stripe_live_plaid_environment' ) === 'production' ? 'production' : 'development' ) : 'sandbox' );
        
        if ( $paymentGatewayStripe->is_live() ) {
            if ( payment_page_setting_get( 'stripe_live_plaid_client_id' ) === '' || payment_page_setting_get( 'stripe_live_plaid_secret' ) === '' ) {
                return new WP_Error( 'rest_error', esc_html( __( "Plaid is not configured", "payment-page" ) ), [
                    'status' => 400,
                ] );
            }
            $args['client_id'] = payment_page_setting_get( 'stripe_live_plaid_client_id' );
            $args['secret'] = payment_page_setting_get( 'stripe_live_plaid_secret' );
        } else {
            if ( payment_page_setting_get( 'stripe_test_plaid_client_id' ) === '' || payment_page_setting_get( 'stripe_test_plaid_secret' ) === '' ) {
                return new WP_Error( 'rest_error', esc_html( __( "Plaid is not configured", "payment-page" ) ), [
                    'status' => 400,
                ] );
            }
            $args['client_id'] = payment_page_setting_get( 'stripe_test_plaid_client_id' );
            $args['secret'] = payment_page_setting_get( 'stripe_test_plaid_secret' );
        }
        
        $request_args = [
            'timeout'     => 15,
            'redirection' => 3,
            'method'      => 'POST',
            'headers'     => [
            'Content-Type' => 'application/json',
        ],
            'body'        => json_encode( $args ),
        ];
        $response = wp_remote_request( 'https://' . $endpoint_sub_domain . '.plaid.com/' . $path, $request_args );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        return json_decode( wp_remote_retrieve_body( $response ), true );
    }
    
    private static function _subscriptionCreateHelper(
        $subscription_create_args,
        $price,
        $request,
        $stripeClient
    )
    {
        $has_setup_price = $request->has_param( 'setup_price_amount' ) && !empty($request->get_param( 'setup_price_amount' )) && isset( $price->recurring ) && !empty($price->recurring);
        
        if ( $has_setup_price ) {
            $setupPrice = self::_getStripePrice( $request, 'setup' );
            if ( is_wp_error( $setupPrice ) ) {
                return $setupPrice;
            }
            $subscription_create_args['items'] = [ [
                'price' => $setupPrice->id,
            ] ];
        }
        
        $subscription = $stripeClient->subscriptions->create( $subscription_create_args );
        
        if ( $has_setup_price ) {
            sleep( 1 );
            $subscription = $stripeClient->subscriptions->update( $subscription->id, [
                'proration_behavior' => 'none',
                'items'              => [ [
                'id'      => $subscription->items->data[0]->id,
                'deleted' => true,
            ], [
                'price' => $price->id,
            ] ],
            ] );
        }
        
        return $subscription;
    }

}