<?php

namespace PaymentPage\API;

use PaymentPage\ThirdPartyIntegration\Freemius as PP_Freemius;

/**
 * Class Notification
 * @author Robert Rusu
 */
class Notification {

  /**
   * @var Notification|null
   */
  protected static $instance = null;

  public static function instance(): ?Notification {
    if (!isset(self::$instance))
      self::$instance = new self();

    return self::$instance;
  }

  public function get_latest_notification() {
       $plan = PP_Freemius::instance()->get_plan();
    $plan_id = ( $plan->id ?? 0 );

    $response = get_transient( PAYMENT_PAGE_ALIAS . '_latest_notification' . $plan_id );

    if( !empty( $response ) )
      return $response;

    $response = wp_remote_get( PAYMENT_PAGE_NOTIFICATION_API_URL . 'notifications/latest?segment=' . $plan_id );

    if( is_wp_error( $response ) )
      return null;

    $response = wp_remote_retrieve_body( $response );

    if( empty( $response ) )
      return null;

    $response = json_decode( $response, true );

    if( empty( $response ) || !is_array( $response ) || !isset( $response[ 'data' ] ) )
      return null;

    set_transient( PAYMENT_PAGE_ALIAS . '_latest_notification' . $plan_id, $response[ 'data' ], HOUR_IN_SECONDS );

    return $response[ 'data' ];
  }

}