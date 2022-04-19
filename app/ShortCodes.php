<?php

namespace PaymentPage;

class ShortCodes {

  /**
   * Register the shortcode
   */
  public static function register(){
    add_shortcode('payment-page-success-details', '\PaymentPage\ShortCodes::success_details' );
  }

  /**
   * Render the content of the shortcode
   */
  public static function success_details(): string {
    return Template::get_template( 'shortcode-payment-success.php', [
      'title'          => ( isset( $_REQUEST[ 'title' ] ) ? urldecode( $_REQUEST[ 'title' ] ) : '' ),
      'message'        => ( isset( $_REQUEST[ 'message' ] ) ? urldecode( $_REQUEST[ 'message' ] ) : '' ),
      'item'           => ( isset( $_REQUEST[ 'item' ] ) ? urldecode( $_REQUEST[ 'item' ] ) : '' ),
      'purchased_from' => ( isset( $_REQUEST[ 'purchased_from' ] ) ? urldecode( $_REQUEST[ 'purchased_from' ] ) : '' ),
      'payment_date'   => ( isset( $_REQUEST[ 'payment_date' ] ) ? urldecode( $_REQUEST[ 'payment_date' ] ) : '' ),
      'currency'       => ( isset( $_REQUEST[ 'currency' ] ) ? urldecode( $_REQUEST[ 'currency' ] ) : '' ),
      'payment_amount' => ( isset( $_REQUEST[ 'amount' ] ) ? urldecode( $_REQUEST[ 'amount' ] ) : '' ),
      'customer_name'  => ( isset( $_REQUEST[ 'customer_name' ] ) ? urldecode( $_REQUEST[ 'customer_name' ] ) : '' ),
      'customer_email' => ( isset( $_REQUEST[ 'email' ] ) ? urldecode( $_REQUEST[ 'email' ] ) : '' ),
    ] );
  }

}