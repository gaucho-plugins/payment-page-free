<?php

namespace PaymentPage\RestAPI;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use PaymentPage\API\PaymentPage as API_PaymentPage;
use PaymentPage\PaymentGateway as InstancePaymentGateway;
use PaymentPage\PaymentGateway\Stripe as PaymentGateway_Stripe;
use PaymentPage\PaymentGateway\PayPal as PaymentGateway_PayPal;
use PaymentPage\Settings as PaymentPage_Settings;

class PaymentGateway {

  public static function register_routes() {
    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/payment-gateway/connect',
      [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => '\PaymentPage\RestAPI\PaymentGateway::connect',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        }
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/payment-gateway/disconnect',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\PaymentGateway::disconnect',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        }
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/payment-gateway/set-mode',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\PaymentGateway::set_mode',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        }
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/payment-gateway/set-payment-methods',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\PaymentGateway::set_payment_methods',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        }
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/payment-gateway/save-webhook-settings',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\PaymentGateway::save_webhook_settings',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        }
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/payment-gateway/connect-callback',
      [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => '\PaymentPage\RestAPI\PaymentGateway::connect_callback',
        'permission_callback' => '__return_true',
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/payment-gateway/save-settings',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\PaymentGateway::save_settings',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        }
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/payment-gateway/save-payment-method-settings',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\PaymentGateway::save_payment_method_settings',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        }
      ]
    );
  }

  public static function connect( WP_REST_Request $request ) {
    foreach( [ 'is_live', 'payment_gateway' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    if( $request->get_param( 'payment_gateway' ) === 'stripe' )
      return rest_ensure_response( PaymentGateway_Stripe::setup_start_connection( [
        'is_live' => $request->get_param( 'is_live' )
      ] ) );

    if( $request->get_param( 'payment_gateway' ) === 'paypal' )
      return rest_ensure_response( PaymentGateway_PayPal::setup_start_connection( [
        'is_live' => $request->get_param( 'is_live' )
      ] ) );

    return new WP_Error(
      'rest_error',
      esc_html( sprintf( __( "Invalid payment gateway %s", "payment-page" ), $request->get_param( 'payment_gateway' ) ) ),
      [
        'status' => 400
      ]
    );
  }

  public static function disconnect( WP_REST_Request $request ) {
    foreach( [ 'is_live', 'payment_gateway' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    $paymentGatewayInstance = InstancePaymentGateway::get_integration_from_settings( $request->get_param( 'payment_gateway' ) );

    if( $paymentGatewayInstance === null )
      return new WP_Error(
        'rest_error',
        esc_html( sprintf( __( "Invalid payment gateway %s", "payment-page" ), $request->get_param( 'payment_gateway' ) ) ),
        [
          'status' => 400
        ]
      );

    $paymentGatewayInstance->delete_settings_credentials( intval( $request->get_param( 'is_live' ) ) );

    return rest_ensure_response( [
      'status' => 'ok'
    ] );
  }

  public static function set_mode( WP_REST_Request $request ) {
    foreach( [ 'is_live', 'payment_gateway' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    $paymentGatewayInstance = InstancePaymentGateway::get_integration_from_settings( $request->get_param( 'payment_gateway' ) );

    if( $paymentGatewayInstance === null )
      return new WP_Error(
        'rest_error',
        esc_html( sprintf( __( "Invalid payment gateway %s", "payment-page" ), $request->get_param( 'payment_gateway' ) ) ),
        [
          'status' => 400
        ]
      );

    PaymentPage_Settings::instance()->update( [
      $request->get_param( 'payment_gateway' ) . '_is_live' => intval( $request->get_param( 'is_live' ) )
    ] );

    if( $request->get_param( 'payment_gateway' ) === 'stripe' )
      if( _payment_page_stripe_payment_methods_background_setup( payment_page_setting_get( 'stripe_payment_methods' ) ) )
        return rest_ensure_response( [
          'status'  => 'ok',
          'refresh' => 1
        ] );

    return rest_ensure_response( [
      'status' => 'ok'
    ] );
  }

  public static function set_payment_methods( WP_REST_Request $request ) {
    foreach( [ 'payment_gateway', 'payment_methods' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    $paymentGatewayInstance = InstancePaymentGateway::get_integration_from_settings( $request->get_param( 'payment_gateway' ) );

    if( $paymentGatewayInstance === null )
      return new WP_Error(
        'rest_error',
        esc_html( sprintf( __( "Invalid payment gateway %s", "payment-page" ), $request->get_param( 'payment_gateway' ) ) ),
        [
          'status' => 400
        ]
      );

    PaymentPage_Settings::instance()->update( [
      $request->get_param( 'payment_gateway' ) . '_payment_methods' => $request->get_param( 'payment_methods' )
    ] );

    if( $request->get_param( 'payment_gateway' ) === 'stripe' )
      if( _payment_page_stripe_payment_methods_background_setup( $request->get_param( 'payment_methods' ) ) )
        return rest_ensure_response( [
          'status'  => 'ok',
          'refresh' => 1
        ] );

    return rest_ensure_response( [
      'status'          => 'ok'
    ] );
  }

  public static function save_webhook_settings( WP_REST_Request $request ) {
    foreach( [ 'is_live', 'payment_gateway' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    $payment_gateway = $request->get_param( 'payment_gateway' );

    $paymentGatewayInstance = InstancePaymentGateway::get_integration_from_settings( $payment_gateway );

    if( empty( $paymentGatewayInstance ) || !method_exists( $paymentGatewayInstance, 'get_webhook_settings_administration' ) )
      return new WP_Error(
        'rest_error',
        __( "Invalid payment gateway", "payment-page" ),
        [
          'status' => 400
        ]
      );

    $webhook_settings_administration = $paymentGatewayInstance->get_webhook_settings_administration();

    $target_items = $webhook_settings_administration[ intval( $request->get_param( 'is_live' ) ) ? 'live_fields' : 'test_fields' ];

    $settings_update_array = [];

    foreach( $target_items as $setting_key => $setting_field_information ) {
      if( !$request->has_param( $setting_key ) )
        continue;

      $settings_update_array[ $setting_key ] = sanitize_text_field( $request->get_param( $setting_key ) );
    }

    if( !empty( $settings_update_array ) )
      PaymentPage_Settings::instance()->update( $settings_update_array );

    return rest_ensure_response( true );
  }

  public static function connect_callback( WP_REST_Request $request ) {
    if( $request->has_param( 'cancel' ) ) {
      payment_page_redirect( admin_url( PAYMENT_PAGE_DEFAULT_URL_PATH ) );
      exit;
    }

    foreach( [ 'payment-gateway', 'credentials' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    $credentials = API_PaymentPage::instance()->decode_response_data( $request->get_param( 'credentials' ) );

    if( !is_array( $credentials ) )
      return new WP_Error(
        'rest_error',
        __( "Invalid Credentials...", "payment-page" ),
        [
          'status' => 400
        ]
      );

    if( $request->get_param( 'payment-gateway' ) === 'stripe' )
      if( !PaymentGateway_Stripe::save_master_credentials_response( $credentials ) )
        return new WP_Error(
          'rest_error',
          __( "Invalid Credentials...", "payment-page" ),
          [
            'status' => 400
          ]
        );

    payment_page_redirect( admin_url( PAYMENT_PAGE_DEFAULT_URL_PATH ) );
    exit;
  }

  public static function save_settings( WP_REST_Request $request ) {
    foreach( [ 'is_live', 'payment_gateway' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    if( $request->get_param( 'payment_gateway' ) === 'paypal' ) {
      if( !PaymentGateway_PayPal::save_master_credentials_response( $request->get_params() ) )
        return new WP_Error(
          'rest_error',
          __( "Invalid Credentials...", "payment-page" ),
          [
            'status' => 400
          ]
        );

      return rest_ensure_response( true );
    }

    return new WP_Error(
      'rest_error',
      __( "Invalid payment gateway", "payment-page" ),
      [
        'status' => 400
      ]
    );
  }

  public static function save_payment_method_settings( WP_REST_Request $request ) {
    foreach( [ 'is_live', 'payment_gateway', 'payment_method' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    $payment_gateway = $request->get_param( 'payment_gateway' );
    $payment_method  = $request->get_param( 'payment_method' );

    $paymentGatewayInstance = InstancePaymentGateway::get_integration_from_settings( $payment_gateway );

    if( empty( $paymentGatewayInstance ) )
      return new WP_Error(
        'rest_error',
        __( "Invalid payment gateway", "payment-page" ),
        [
          'status' => 400
        ]
      );

    $payment_methods_administration = $paymentGatewayInstance->get_payment_methods_administration();

    if( !isset( $payment_methods_administration[ $payment_method ] )
        || !isset( $payment_methods_administration[ $payment_method ][ 'settings' ] ) )
      return new WP_Error(
        'rest_error',
        __( "Invalid payment method", "payment-page" ),
        [
          'status' => 400
        ]
      );

    $settings_update_array = [];

    foreach( $payment_methods_administration[ $payment_method ][ 'settings' ] as $settings_group_key => $settings_group_map ) {
      $target_items = $settings_group_map[ intval( $request->get_param( 'is_live' ) ) ? 'live_fields' : 'test_fields' ];

      foreach( $target_items as $setting_key => $setting_field_information ) {
        if( !$request->has_param( $setting_key ) )
          continue;

        $settings_update_array[ $setting_key ] = sanitize_text_field( $request->get_param( $setting_key ) );
      }
    }

    if( !empty( $settings_update_array ) )
      PaymentPage_Settings::instance()->update( $settings_update_array );

    return rest_ensure_response( true );
  }

}
