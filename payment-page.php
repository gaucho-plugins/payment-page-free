<?php
/*
 * Plugin Name: Payment Page
 * Plugin URI:      https://payment.page
 * Description:     Payment Page is the most powerful way to accept online payments. Connect your payment gateway, choose a template, and get paid!
 * Version:         1.2.7
 * Author:          Gaucho Plugins
 * Author URI:      https://gauchoplugins.com/
 * License:         GPLv3
 * Text Domain:     payment-page
 * Domain Path:     /languages/
 */

defined( 'ABSPATH' ) || exit;

define( "PAYMENT_PAGE_VERSION", '1.2.7' );
define( "PAYMENT_PAGE_BASE_FILE_PATH", __FILE__ );
define( "PAYMENT_PAGE_BASE_PATH", dirname( PAYMENT_PAGE_BASE_FILE_PATH ) );
define( "PAYMENT_PAGE_PLUGIN_IDENTIFIER", ltrim( str_ireplace( dirname( PAYMENT_PAGE_BASE_PATH ), '', PAYMENT_PAGE_BASE_FILE_PATH ), '/' ) );
define( 'PAYMENT_PAGE_ELEMENTOR_TEMPLATES_ENDPOINT', 'https://payment.page/wp-json/payment-page-admin/v1/get-templates');

require_once PAYMENT_PAGE_BASE_PATH . '/autoload.php';
require_once PAYMENT_PAGE_BASE_PATH . '/lib/definitions.php';
require_once PAYMENT_PAGE_BASE_PATH . '/lib/polyfill.php';

PaymentPage\Migration::instance()->setup();

PaymentPage\ThirdPartyIntegration\Freemius::instance();
PaymentPage\ThirdPartyIntegration\Elementor::instance()->setup();

// General Functionality
add_action( 'plugins_loaded', [ PaymentPage\Controller::instance(), 'setup' ], 5 );

if( is_admin() )
  add_action( 'plugins_loaded', [ PaymentPage\AdminController::instance(), 'setup' ], 10 );

// RestAPI
add_action( 'rest_api_init', [ PaymentPage\RestAPI::instance(), 'setup' ] );

// Site Health
add_filter( 'site_status_tests', [ PaymentPage\SiteHealth::instance(), 'tests' ] );

if ( ! function_exists( 'payment_page_fs' ) ) {
  function payment_page_fs() {
    return PaymentPage\ThirdPartyIntegration\Freemius::instance();
  }
}
