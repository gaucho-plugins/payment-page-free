<?php

namespace PaymentPage\RestAPI;

use  WP_Error ;
use  WP_REST_Server ;
use  WP_REST_Request ;
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
        
        if ( $stripePrice->recurring === null ) {
            $paymentIntentInformation = [
                'amount'   => $stripePrice->unit_amount,
                'currency' => strtoupper( $stripePrice->currency ),
                'customer' => $stripe_customer_id,
                'metadata' => self::_getGeneralMetaData( $request ),
            ];
            if ( $request->get_param( 'payment_method' ) === 'alipay' ) {
                $paymentIntentInformation['payment_method_types'] = [ 'alipay' ];
            }
            if ( $request->get_param( 'payment_method' ) === 'wechat' ) {
                $paymentIntentInformation['payment_method_types'] = [ 'wechat_pay' ];
            }
            
            if ( $request->has_param( 'stripe_payment_method_id' ) && !empty($request->get_param( 'stripe_payment_method_id' )) ) {
                $paymentIntentInformation['setup_future_usage'] = 'off_session';
                $paymentIntentInformation['confirm'] = true;
                $paymentIntentInformation['payment_method'] = $request->get_param( 'stripe_payment_method_id' );
                if ( !isset( $paymentIntentInformation['capture_method'] ) ) {
                    $paymentIntentInformation['capture_method'] = 'manual';
                }
            }
            
            try {
                $paymentIntent = PaymentGateway::get_integration_from_settings( 'stripe' )->stripeClient()->paymentIntents->create( $paymentIntentInformation );
            } catch ( \Exception $e ) {
                return new WP_Error( 'rest_error', esc_html( $e->getMessage() ), [
                    'status' => 400,
                ] );
            }
            return rest_ensure_response( [
                'payment_intent_id'              => $paymentIntent->id,
                'payment_intent_secret'          => $paymentIntent->client_secret,
                'payment_intent_requires_action' => $paymentIntent->status == 'requires_action' && $paymentIntent->next_action->type == 'use_stripe_sdk',
            ] );
        }
        
        foreach ( [ 'stripe_payment_method_id' ] as $required_param ) {
            if ( !$request->has_param( $required_param ) ) {
                return new WP_Error( 'rest_error', esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ), [
                    'status' => 400,
                ] );
            }
        }
        try {
            $setupIntentInformation = [
                'customer'       => $stripe_customer_id,
                'payment_method' => $request->get_param( 'stripe_payment_method_id' ),
                'metadata'       => self::_getGeneralMetaData( $request ),
            ];
            $setupIntent = PaymentGateway::get_integration_from_settings( 'stripe' )->stripeClient()->setupIntents->create( $setupIntentInformation );
        } catch ( \Exception $e ) {
            return new WP_Error( 'rest_error', esc_html( $e->getMessage() ), [
                'status' => 400,
            ] );
        }
        return rest_ensure_response( [
            'setup_intent_id'     => $setupIntent->id,
            'setup_intent_secret' => $setupIntent->client_secret,
        ] );
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
     * @return \Stripe\Price|WP_Error
     * @throws \Stripe\Exception\ApiErrorException
     */
    private static function _getStripePrice( WP_REST_Request $request )
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
            'price'             => intval( floatval( $request->get_param( 'price_amount' ) ) * 100 ),
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
    
    /**
     * @param WP_REST_Request $request
     * @return array
     */
    private static function _getGeneralMetaData( WP_REST_Request $request ) : array
    {
        $response = [];
        
        if ( $request->has_param( 'custom_fields' ) ) {
            $custom_fields = $request->get_param( 'custom_fields' );
            if ( is_array( $custom_fields ) && !empty($custom_fields) ) {
                foreach ( $custom_fields as $custom_field_key => $custom_field_value ) {
                    $response[payment_page_label_to_alias( sanitize_text_field( $custom_field_key ) )] = sanitize_text_field( $custom_field_value );
                }
            }
        }
        
        $response['frequency'] = ( $request->has_param( 'price_frequency' ) ? $request->get_param( 'price_frequency' ) : 'one-time' );
        $response['payment_page_url'] = get_the_permalink( $request->get_param( 'post_id' ) );
        $response['payment_page_id'] = intval( $request->get_param( 'post_id' ) );
        $response['domain_name'] = payment_page_domain_name();
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
        $subscription = $stripeClient->subscriptions->create( [
            'default_payment_method' => $payment_method->id,
            'customer'               => $stripe_customer_id,
            'items'                  => [ [
            'price' => $price->id,
        ] ],
            'metadata'               => self::_getGeneralMetaData( $request ),
        ] );
        return $subscription;
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
                $subscription = $paymentGatewayStripe->stripeClient()->subscriptions->create( [
                    'default_source' => $payment_source_id,
                    'customer'       => $stripeCustomer->id,
                    'items'          => [ [
                    'price' => $stripePrice->id,
                ] ],
                    'metadata'       => self::_getGeneralMetaData( $request ),
                ] );
            } else {
                $paymentGatewayStripe->stripeClient()->charges->create( [
                    'amount'   => $stripePrice->unit_amount,
                    'currency' => strtoupper( $stripePrice->currency ),
                    'customer' => $stripeCustomer->id,
                    'source'   => $payment_source_id,
                    'metadata' => self::_getGeneralMetaData( $request ),
                    'capture'  => true,
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

}