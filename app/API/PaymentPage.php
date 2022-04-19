<?php

namespace PaymentPage\API;

use PaymentPage\ThirdPartyIntegration\Freemius as PP_Freemius;

/**
 * Class PaymentPage
 * @author Robert Rusu
 */
class PaymentPage {

  /**
   * @var PaymentPage|null
   */
  protected static $instance = null;

  public static function instance(): ?PaymentPage {
    if (!isset(self::$instance))
      self::$instance = new self();

    return self::$instance;
  }

  public $_defaultPlanScore = 0;
  public $_planToPlanScore = [
    '9891'  => 0, // Free
    '11498' => 1, // Personal
    '11499' => 2, // Pro
    '11500' => 3, // Agency
    '11559' => 3, // Lifetime as agency
    '12982' => 1  // Lifetime app sumo as personal
  ];

  public function get_template_plan_score(): int {
    $plan = PP_Freemius::instance()->get_plan();

    return ( isset( $plan->id ) && isset( $this->_planToPlanScore[ $plan->id ] ) ? $this->_planToPlanScore[ $plan->id ] : $this->_defaultPlanScore );
  }

  public function get_import_template_list() {
    $response = get_transient( PAYMENT_PAGE_ALIAS . '_template_list_' . $this->get_template_plan_score() );

    if( !empty( $response ) )
      return $response;

    $response = wp_remote_get( PAYMENT_PAGE_ELEMENTOR_TEMPLATES_ENDPOINT . "?plan_score=" . $this->get_template_plan_score() );

    if ( is_wp_error( $response ) )
      return [];

    $response = wp_remote_retrieve_body( $response );
    $response = json_decode( $response, true );

    if( !is_array( $response ) || !isset( $response[ 'data' ] ) || !isset( $response[ 'data' ][ 'templates' ] ) )
      return [];

    set_transient( PAYMENT_PAGE_ALIAS . '_template_list_' . $this->get_template_plan_score(), $response[ 'data' ][ 'templates' ], HOUR_IN_SECONDS );

    return $response[ 'data' ][ 'templates' ];
  }

  public function get_import_template_content( $template_id ) {
    $api_templates = "https://payment.page/wp-json/wp/v2/pages/" . intval( $template_id );

    $response = wp_remote_get($api_templates);

    if( is_wp_error($response) )
      return null;

    $response = wp_remote_retrieve_body( $response );
    $response = json_decode( $response, true );

    if( !is_array( $response ) )
      return null;

    return $response;
  }

  public function request( $path, $request_params, $method = 'POST' ) {
    $request_args = [
      'body' => PP_Freemius::api_request_details() + $request_params,
    ];

    if( $method === 'POST' )
      $response = wp_remote_post( PAYMENT_PAGE_EXTERNAL_API_URL . $path, $request_args );
    else
      $response = wp_remote_get( PAYMENT_PAGE_EXTERNAL_API_URL . $path, $request_args );

    $response = wp_remote_retrieve_body( $response );

    $response = json_decode( $response, true );

    return is_array( $response ) ? $response : null;
  }

  public function decode_response_data( $data ) {
    $response = json_decode( payment_page_decrypt( $data, PP_Freemius::instance()->get_anonymous_id(), md5( get_site_url() ) ), true );

    if( !is_array( $response ) )
      return null;

    return $response;
  }

}