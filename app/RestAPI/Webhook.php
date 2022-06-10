<?php

namespace PaymentPage\RestAPI;

use  WP_REST_Server ;
use  WP_REST_Request ;
use  PaymentPage\PaymentGateway as PP_PaymentGateway ;
use  PaymentPage\ThirdPartyIntegration\Freemius as PP_Freemius ;
use  PaymentPage\Model\Log as PP_Model_Log ;
class Webhook
{
    public static function register_routes()
    {
        register_rest_route( PAYMENT_PAGE_REST_API_PREFIX . '/v1', '/webhook/stripe-callback/(?P<mode>[\\w-]+)', [
            'methods'             => WP_REST_Server::ALLMETHODS,
            'callback'            => '\\PaymentPage\\RestAPI\\Webhook::stripe_callback',
            'permission_callback' => function () {
            return true;
        },
        ] );
    }
    
    public static function stripe_callback( WP_REST_Request $request )
    {
        ini_set( 'display_errors', 1 );
        ini_set( 'display_startup_errors', 1 );
        error_reporting( E_ALL );
        
        if ( !isset( $_SERVER["HTTP_STRIPE_SIGNATURE"] ) ) {
            http_response_code( 400 );
            return rest_ensure_response( [
                'message' => sprintf( __( '%s not found', "payment-page" ), 'HTTP_STRIPE_SIGNATURE' ),
            ] );
        }
        
        $mode = $request->get_param( 'mode' );
        if ( !class_exists( '\\Stripe\\Stripe' ) ) {
            require_once PAYMENT_PAGE_BASE_PATH . '/lib/stripe/init.php';
        }
        $payload = @file_get_contents( "php://input" );
        $sig_header = $_SERVER["HTTP_STRIPE_SIGNATURE"];
        try {
            $event = \Stripe\Webhook::constructEvent( $payload, $sig_header, payment_page_setting_get( 'stripe_' . $mode . '_webhook_secret' ) );
        } catch ( \UnexpectedValueException $e ) {
            // Invalid payload
            http_response_code( 400 );
            return rest_ensure_response( [
                'message' => __( "Invalid Payload", "payment-page" ),
            ] );
        }
        
        if ( !isset( $event->data->object->metadata->payment_page_id ) ) {
            http_response_code( 200 );
            return rest_ensure_response( [
                'message' => sprintf( __( "Event not tracked, %s metadata not attached", "payment-page" ), 'payment_page_id' ),
            ] );
        }
        
        
        if ( !isset( $event->data->object->metadata->domain_name ) || $event->data->object->metadata->domain_name !== payment_page_domain_name() ) {
            http_response_code( 200 );
            return rest_ensure_response( [
                'message' => sprintf( __( "Event not tracked, %s metadata does not match", "payment-page" ), 'domain_name' ),
            ] );
        }
        
        
        if ( !in_array( $event->type, [ 'payment_intent.succeeded', 'setup_intent.succeeded' ] ) ) {
            http_response_code( 200 );
            return rest_ensure_response( [
                'message' => __( "Event not tracked", "payment-page" ),
            ] );
        }
        
        $paymentGateway = PP_PaymentGateway::get_integration_from_settings( 'stripe', $mode === 'live' );
        $stripeCustomer = $paymentGateway->stripeClient()->customers->retrieve( $event->data->object->customer );
        $request_data = [
            'gateway'         => 'stripe',
            'mode'            => $mode,
            'name'            => $stripeCustomer->name,
            'email'           => $stripeCustomer->email,
            'amount'          => $event->data->object->amount,
            'amount_received' => $event->data->object->amount_received,
            'currency'        => $event->data->object->currency,
        ];
        foreach ( $event->data->object->metadata->keys() as $k ) {
            $request_data[$k] = $event->data->object->metadata->{$k};
        }
        $page_id = intval( $event->data->object->metadata->payment_page_id );
        $page_settings = get_post_meta( $page_id, '_elementor_data', true );
        
        if ( empty($page_settings) ) {
            http_response_code( 200 );
            return rest_ensure_response( [
                'message' => sprintf( __( "Missing %s for this page.", "payment-page" ), '_elementor_data' ),
            ] );
        }
        
        $page_settings = json_decode( $page_settings, true );
        
        if ( empty($page_settings) ) {
            http_response_code( 200 );
            return rest_ensure_response( [
                'message' => sprintf( __( "Invalid %s for this page.", "payment-page" ), '_elementor_data' ),
            ] );
        }
        
        $paymentPageElements = self::_extractPaymentPageElements( $page_settings );
        foreach ( $paymentPageElements as $paymentPageElement ) {
            if ( !isset( $paymentPageElement['submit_actions'] ) || empty($paymentPageElement['submit_actions']) ) {
                continue;
            }
            
            if ( in_array( "http_request", $paymentPageElement['submit_actions'] ) && isset( $paymentPageElement['http_request_url'] ) && !empty($paymentPageElement['http_request_url']) && !empty($paymentPageElement['http_request_url']['url']) ) {
                $log = new PP_Model_Log( [
                    'post_id'   => $page_id,
                    'namespace' => 'form',
                    'action'    => 'http_request',
                    'content'   => [
                    'request_response' => null,
                    'request_data'     => $request_data,
                ],
                ] );
                $log->save();
                $log->content['request_response'] = wp_remote_post( $paymentPageElement['http_request_url']['url'], [
                    'body' => $request_data,
                ] );
                $log->save();
            }
        
        }
        http_response_code( 201 );
        return rest_ensure_response( [
            'message' => __( "Handled Callback", "payment-page" ),
        ] );
    }
    
    private static function _extractPaymentPageElements( $page_settings )
    {
        $response = [];
        foreach ( $page_settings as $page_setting ) {
            
            if ( isset( $page_setting['elements'] ) && !empty($page_setting['elements']) ) {
                $response += self::_extractPaymentPageElements( $page_setting['elements'] );
                continue;
            }
            
            if ( isset( $page_setting['widgetType'] ) && $page_setting['widgetType'] === 'payment-form' ) {
                $response[$page_setting['id']] = $page_setting['settings'];
            }
        }
        return $response;
    }

}