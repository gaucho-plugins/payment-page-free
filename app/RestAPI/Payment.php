<?php

namespace PaymentPage\RestAPI;

use PaymentPage\PaymentGateway as PaymentGateway;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use PaymentPage\Model\Payments as Model_Payments;

class Payment {

  public static function register_routes() {
    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/payment/sync-details',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\Payment::sync_details',
        'permission_callback' => '__return_true',
      ]
    );
  }

  public static function sync_details( WP_REST_Request $request ) {
    foreach( [ 'first_name', 'last_name', 'email_address', 'payment_gateway', 'payment_method', 'post_id' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    if( $request->has_param( '_current_id' ) && $request->has_param( '_current_secret' ) ) {
      if( md5( self::_salt() . $request->get_param( '_current_id' ) ) === $request->get_param( '_current_secret' ) ) {
        try {
          $model = Model_Payments::findOrFail( [ 'id' => intval( $request->get_param( '_current_id' ) ) ] );
        } catch( \Exception $e ) {
          $model = new Model_Payments( [] );
        }
      } else {
        return new WP_Error(
          'rest_error',
          esc_html( __( "Invalid id & secret combination", "payment-page" ) ),
          [
            'status' => 400
          ]
        );
      }
    } else {
      $model = new Model_Payments( [] );
    }

    if( $model->exists() && intval( $model->is_paid ) )
      return new WP_Error(
        'rest_error',
        esc_html( __( "Already paid, cannot update details %s", "payment-page" ) ),
        [
          'status' => 400
        ]
      );

    $model->populate( [
      'post_id'          => $request->get_param( 'post_id' ),
      'user_id'          => get_current_user_id(),
      'email_address'    => $request->get_param( 'email_address' ),
      'first_name'       => $request->get_param( 'first_name' ),
      'last_name'        => $request->get_param( 'last_name' ),
      'payment_gateway'  => $request->get_param( 'payment_gateway' ),
      'payment_method'   => $request->get_param( 'payment_method' ),
      'metadata_json'    => _payment_page_rest_api_custom_fields( $request ),
      'amount'           => intval( floatval( $request->get_param( 'price' ) ) * 100 ),
      'currency'         => $request->get_param( 'currency' ),
      'is_live'          => ( PaymentGateway::get_integration_from_settings( $request->get_param( 'payment_gateway' ) )->is_live() ? 1 : 0 )
    ] );
    $model = $model->save();

    return rest_ensure_response( [
      'id'         => $model->id,
      'secret'     => md5( self::_salt() . $model->id )
    ] );
  }

  private static function _salt() {
    return defined( "NONCE_SALT" ) ? NONCE_SALT : "if_the_site_is_flawed_do_not_fail";
  }

}