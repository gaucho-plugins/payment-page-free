<?php

namespace PaymentPage;

use WP_Query;

class Controller {

  /**
   * @var null|Controller;
   */
  protected static $_instance = null;

  /**
   * @return Controller
   */
  public static function instance(): Controller {
    if( self::$_instance === null )
      self::$_instance = new self();

    return self::$_instance;
  }

  public function __construct() {
    require_once PAYMENT_PAGE_BASE_PATH . '/app/_functions/administration.php';
    require_once PAYMENT_PAGE_BASE_PATH . '/app/_functions/elementor.php';
    require_once PAYMENT_PAGE_BASE_PATH . '/app/_functions/general.php';
    require_once PAYMENT_PAGE_BASE_PATH . '/app/_functions/utilities.php';
  }

  public function setup() {
    if( !defined( "PAYMENT_PAGE_EXTERNAL_API_URL" ) )
      define( "PAYMENT_PAGE_EXTERNAL_API_URL", "https://api.payment.page/wp-json/payment-page-api/v1/" );

    if( !defined( "NOTIFICATION_SYSTEM_API_URL" ) )
      define( "PAYMENT_PAGE_NOTIFICATION_API_URL", "https://api.payment.page/wp-json/notification-system-api/v1/" );
    else
      define( "PAYMENT_PAGE_NOTIFICATION_API_URL", NOTIFICATION_SYSTEM_API_URL );

    if( !defined( "PAYMENT_PAGE_FEATURES_API_URL" ) )
      define( "PAYMENT_PAGE_FEATURES_API_URL", "https://api.payment.page/wp-json/features-api/v1/" );

    load_plugin_textdomain("payment-page", false, PAYMENT_PAGE_LANGUAGE_DIRECTORY );

    add_action( 'wp_enqueue_scripts', 'payment_page_register_universal_interface', 5 );
    add_action( 'admin_bar_menu', [ $this, '_admin_bar_menu' ], 999 );

    ShortCodes::register();

    if( PAYMENT_PAGE_STRIPE_APPLE_PAY_VERIFICATION_TYPE_AUTO === Settings::instance()->get( 'stripe_apple_pay_verification_type_test' )
        || PAYMENT_PAGE_STRIPE_APPLE_PAY_VERIFICATION_TYPE_AUTO === Settings::instance()->get( 'stripe_apple_pay_verification_type_live' ) ) {
      add_action( "init", [ $this, '_maybe_stripe_apple_pay_domain_association' ], -1000 );
    }
  }

  public function _admin_bar_menu( $admin_bar ) {
    if( !current_user_can( PAYMENT_PAGE_ADMIN_CAP ) )
      return;

    $map = [
      'live' => 0,
      'test' => 0,
      'none' => 0
    ];

    $integrations = [
      'stripe' => PaymentGateway::get_integration_from_settings( 'stripe' ),
      'paypal' => PaymentGateway::get_integration_from_settings( 'paypal' )
    ];

    foreach( $integrations as $integration ) {
      if( !$integration->is_configured() ) {
        $map[ 'none' ]++;
        continue;
      }

      if( $integration->is_live() )
        $map[ 'live' ]++;
      else
        $map[ 'test' ]++;
    }

    if( $map[ 'live' ] === 0 && $map[ 'test' ] === 0 )
      return;

    $admin_bar->add_node( [
      'parent' => 'top-secondary',
      'id'     => PAYMENT_PAGE_ALIAS,
      'title'  => sprintf( __( "Payment Page %s Mode", "payment-page" ), ( $map[ 'live' ] > 0 && $map[ 'test' ] > 0 ? 'Mixed' : ( $map[ 'test' ] === 0 ? 'Live' : 'Test' ) ) ),
      'href'   => esc_url( admin_url( 'admin.php?page=' . PAYMENT_PAGE_MENU_SLUG ) ) . '#payment-gateways',
      'meta'   => [
        'class' => 'payment-page-top-bar-item ' . ( $map[ 'live' ] > 0 && $map[ 'test' ] > 0 ? 'is-mixed' : ( $map[ 'test' ] === 0 ? 'is-live' : 'is-test' ) )
      ]
    ] );

    foreach( $integrations as $integration_alias => $integration ) {
      if( !$integration->is_configured() )
        return;

      $admin_bar->add_node( [
        'parent' => PAYMENT_PAGE_ALIAS,
        'id'     => PAYMENT_PAGE_ALIAS . '_' . $integration_alias,
        'title'  => $integration->get_name() . ' ' . ( $integration->is_live() ? 'Live' : 'Test' ),
        'href'   => esc_url( admin_url( 'admin.php?page=' . PAYMENT_PAGE_MENU_SLUG ) ) . '#payment-gateways',
        'meta'   => [
          'class' => 'payment-page-child-bar-item ' . ( $integration->is_live() ? 'is-live' : 'is-test' )
        ]
      ] );
    }
  }

  public function _maybe_stripe_apple_pay_domain_association() {
    $current_url = Request::instance()->get_current_url();

    if( $current_url !== rtrim( get_site_url() , '/' ) . '/.well-known/apple-developer-merchantid-domain-association' )
      return;

    $file = PAYMENT_PAGE_BASE_PATH . '/lib/apple-developer-merchantid-domain-association';

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
  }

}