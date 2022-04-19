<?php

namespace PaymentPage\API;

/**
 * Class Features
 * @author Robert Rusu
 */
class Features {

  /**
   * @var Notification|null
   */
  protected static $instance = null;

  public static function instance(): ?Features {
    if (!isset(self::$instance))
      self::$instance = new self();

    return self::$instance;
  }

  public function area( $slug ) {
    $transient = get_transient( 'payment_page_area_' . $slug );

    if( !empty( $transient ) )
      return rest_ensure_response( $this->_prepare_area_details( $transient ) );

    $response = wp_remote_get( PAYMENT_PAGE_FEATURES_API_URL . "area/" . $slug );

    if ( is_wp_error( $response ) )
      return $response;

    $response = wp_remote_retrieve_body( $response );
    $response = json_decode( $response, true );

    if( !is_array( $response ) )
      $response = [];

    if( !empty( $response ) )
      set_transient( 'payment_page_area_' . $slug, $response, HOUR_IN_SECONDS );

    return rest_ensure_response( $this->_prepare_area_details( $response ) );
  }

  private function _prepare_area_details( $response ) {
    if( isset( $response[ 'data' ] ) ) {
      $response[ 'data' ] += [
        'first_name'      => __( "First Name", "payment-page" ),
        'last_name'       => __( "Last Name", "payment-page" ),
        'email_address'   => __( "Email Address", "payment-page" ),

        'current_user'    => [
          'first_name'    => wp_get_current_user()->first_name,
          'last_name'     => wp_get_current_user()->last_name,
          'email_address' => wp_get_current_user()->user_email
        ]
      ];
    }

    return $response;
  }

  public function tag( $tags, $email_address, $details, $area_slug ) {
    $response = wp_remote_post( PAYMENT_PAGE_FEATURES_API_URL . "subscriber/tag", [
      'body' => [
        'tags'          => $tags,
        'email_address' => $email_address,
        'first_name'    => ( $details['first_name'] ?? '' ),
        'last_name'     => ( $details['last_name'] ?? '' ),
        'area_slug'     => $area_slug
      ],
    ] );

    if ( is_wp_error( $response ) )
      return $response;

    $response = wp_remote_retrieve_body( $response );
    $response = json_decode( $response, true );

    if( !is_array( $response ) )
      $response = [];

    return $response;
  }

}