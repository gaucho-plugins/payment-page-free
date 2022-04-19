<?php

namespace PaymentPage\RestAPI;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use PaymentPage\API\Features as API_Features;

class Tagging {

  public static function register_routes() {
    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/tagging/area/(?P<slug>[\w-]+)',
      [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => '\PaymentPage\RestAPI\Tagging::area',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        },
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/tagging/apply',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\Tagging::apply',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        },
      ]
    );
  }

  public static function area( WP_REST_Request $request ) {
    return rest_ensure_response( API_Features::instance()->area( $request->get_param( 'slug' ) ) );
  }

  public static function apply( WP_REST_Request $request ) {
    foreach( [ 'first_name', 'last_name', 'email_address', 'tags', 'area_slug' ] as $required_param )
      if( !$request->has_param( $required_param ) )
        return new WP_Error(
          'rest_error',
          esc_html( sprintf( __( "Missing request param %s", "payment-page" ), $required_param ) ),
          [
            'status' => 400
          ]
        );

    if( !is_email( $request->get_param( 'email_address' ) ) )
      return new WP_Error(
        'rest_error',
        esc_html( sprintf( __( "Invalid email address provided.", "payment-page" ), $required_param ) ),
        [
          'status' => 400
        ]
      );

    $response = API_Features::instance()->tag(
      $request->get_param( 'tags' ),
      $request->get_param( 'email_address' ),
      [
        'first_name'  => $request->get_param( 'first_name' ),
        'last_name'   => $request->get_param( 'last_name' )
    ], $request->get_param( 'area_slug' ) );

    if( is_wp_error( $response ) )
      return $response;

    if( empty( $response ) )
      return new WP_Error(
        'rest_error',
        esc_html( __( "An unexpected error has happened, please try again", "payment-page" ) ),
        [
          'status' => 400
        ]
      );

    return rest_ensure_response( $response );
  }

}