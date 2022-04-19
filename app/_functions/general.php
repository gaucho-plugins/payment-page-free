<?php

function payment_page_setting_get( $option_name ) {
  return PaymentPage\Settings::instance()->get( $option_name );
}

function payment_page_template_load( string $template_name, array $args = [], string $template_path = '', string $default_path = '' ) {
  PaymentPage\Template::load_template( $template_name, $args, $template_path, $default_path );
}

function payment_page_encrypt( $string, $secret_key, $secret_iv ): string {
  $key = hash('sha256',$secret_key);
  $iv = substr(hash('sha256',$secret_iv),0,16);

  return base64_encode(openssl_encrypt($string,"AES-256-CBC",$key,0,$iv));;
}

function payment_page_decrypt( $string, $secret_key, $secret_iv ) :string {
  $key = hash('sha256',$secret_key);
  $iv = substr(hash('sha256',$secret_iv),0,16);

  return openssl_decrypt(base64_decode($string),"AES-256-CBC",$key,0,$iv);
}

function payment_page_domain_name() {
  return str_replace( [ 'https://www.', 'https://', 'http://www.', 'http://' ], '', rtrim( get_site_url(), '/' ) );
}

/**
 * @return wpdb
 */
function payment_page_wpdb(): wpdb {
  global $wpdb;

  return $wpdb;
}

/**
 * @param $query
 * @return array
 */
function payment_page_dbDelta( $query ) {
  if( !function_exists( 'dbDelta' ) )
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

  return dbDelta( $query );
}

/**
 * Can be later abstracted to return a different version number for WP_DEBUG or setting.
 * @return string
 */
function payment_page_frontend_file_version() :string {
  return ( defined( 'WP_DEBUG' ) && WP_DEBUG ? '' . time() : PAYMENT_PAGE_VERSION );
}

function payment_page_frontend_configuration() :array {
  return [
    'user_id'               => get_current_user_id(),
    'is_user_logged_in'     => ( is_user_logged_in() ? 1 : 0 ),
    'is_user_administrator' => ( current_user_can( PAYMENT_PAGE_ADMIN_CAP ) ? 1 : 0 ),
    'is_https'              => ( wp_is_using_https() ? 1 : 0 ),
    'domain_url'            => esc_url( ( isset( $_SERVER['HTTPS'] ) ? "https" : "http" ) . "://" . $_SERVER["HTTP_HOST"] ),
    'site_url'              => get_site_url(),
    'rest_url'              => esc_url_raw( rest_url() ),
    'rest_nonce'            => wp_create_nonce( 'wp_rest' ),
    'file_version'          => payment_page_frontend_file_version(),
    'library_url'           => plugin_dir_url( PAYMENT_PAGE_BASE_FILE_PATH ) . 'interface/app/',
    'component_injection'   => [],
    'template_extra'        => [],
    'logo'                  => plugins_url( 'interface/img/logo.gif', PAYMENT_PAGE_BASE_FILE_PATH ),
    'loader_icon'           => plugins_url( 'interface/img/loader-icon.gif', PAYMENT_PAGE_BASE_FILE_PATH ),
    'libraries'             => [
      'inputmask' => plugins_url( 'interface/app/third-party/jquery-inputmask/jquery.inputmask.min.js', PAYMENT_PAGE_BASE_FILE_PATH )
    ],
  ];
}

function payment_page_frontend_language() :array {
  return [
    'no_results_response'  => __( "No results found.", "payment-page" ),
    'cancelled_request'    => __( "Request cancelled", "payment-page" ),
    'asset_failed_fetch'   => __( "Failed to fetch asset required to display this section, please refresh and try again, if this error persists, contact support for assistance.", "payment-page" ),
  ];
}

function payment_page_register_universal_interface() {
  $file_version = payment_page_frontend_file_version();

  wp_register_style(PAYMENT_PAGE_PREFIX,plugins_url( 'interface/app/style.css', PAYMENT_PAGE_BASE_FILE_PATH ), [], $file_version );
  wp_enqueue_style(PAYMENT_PAGE_PREFIX );

  wp_register_script( PAYMENT_PAGE_PREFIX, plugins_url( 'interface/app/app.min.js', PAYMENT_PAGE_BASE_FILE_PATH ), [ 'jquery', 'wp-util', 'lodash' ], $file_version, true );

  wp_localize_script( PAYMENT_PAGE_PREFIX, 'payment_page_data', [
    'configuration' => payment_page_frontend_configuration(),
    'lang'          => payment_page_frontend_language()
  ] );

  wp_enqueue_script( PAYMENT_PAGE_PREFIX );
}

function _payment_page_refresh_rewrite_rules_and_capabilities() {
  global $wp_roles;

  $capabilities = [
    PAYMENT_PAGE_ADMIN_CAP
  ];

  foreach( $capabilities as $capability )
    $wp_roles->add_cap( 'administrator', $capability );

  try {
    flush_rewrite_rules();
  } catch ( \Exception $exception ) {

  }
}