<?php

namespace PaymentPage\RestAPI;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use PaymentPage\API\PaymentPage as API_PaymentPage;
use PaymentPage\PaymentGateway as PaymentGateway;
use PaymentPage\Settings as Settings;

class Plugin {

  public static function register_routes() {
    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/plugin/install',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\Plugin::install',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        },
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/plugin/activate',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\Plugin::activate',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        },
      ]
    );
  }

  public static function install( WP_REST_Request $request ) {
    if( !$request->has_param( 'identifier' ) )
      return new WP_Error(
        'rest_error',
        esc_html( sprintf( __( "Missing request param %s", "payment-page" ), 'identifier' ) ),
        [
          'status' => 400
        ]
      );

    if( $request->get_param( 'identifier' ) !== 'elementor' )
      return new WP_Error(
        'rest_error',
        __( "Plugin install not allowed.", "payment-page" ),
        [
          'status' => 400
        ]
      );

    require_once (ABSPATH . 'wp-admin/includes/plugin-install.php');
    require_once (ABSPATH . 'wp-admin/includes/plugin.php');
    require_once (ABSPATH . 'wp-admin/includes/file.php');

    if( !class_exists( '\Plugin_Upgrader' ) )
      require_once (ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

    if( !class_exists( '\WP_Ajax_Upgrader_Skin' ) )
      require_once (ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php');

    $identifier = $request->get_param( 'identifier' );

    if( is_plugin_active( $identifier . '/' . $identifier . '.php' ) )
      return rest_ensure_response( [
        'is_installed' => 1
      ] );

    try {
      $api = plugins_api('plugin_information', array(
        'slug' => $identifier
      ));
    } catch (\Throwable $th) {
      return new WP_Error( 'rest_error', $th->getMessage() );
    }

    $skin = new \WP_Ajax_Upgrader_Skin();
    $upgrader = new \Plugin_Upgrader($skin);
    $result = $upgrader->install($api->download_link);

    if ( is_wp_error($result ) )
      return $result;

    return rest_ensure_response( [
      'is_installed' => 1
    ] );
  }

  public static function activate( WP_REST_Request $request ) {
    if( !$request->has_param( 'identifier' ) )
      return new WP_Error(
        'rest_error',
        esc_html( sprintf( __( "Missing request param %s", "payment-page" ), 'identifier' ) ),
        [
          'status' => 400
        ]
      );

    if( $request->get_param( 'identifier' ) !== 'elementor' )
      return new WP_Error(
        'rest_error',
        __( "Plugin install not allowed.", "payment-page" ),
        [
          'status' => 400
        ]
      );

    require_once (ABSPATH . 'wp-admin/includes/plugin-install.php');
    require_once (ABSPATH . 'wp-admin/includes/plugin.php');

    $pluginDir = WP_PLUGIN_DIR . '/' . $request->get_param( 'identifier' );
    $pluginPath = $pluginDir . '/' . $request->get_param( 'identifier' ) . '.php';

    if ( !file_exists($pluginPath ) )
      return new WP_Error(
        'rest_error',
        __( "Plugin Activation not allowed.", "payment-page" ),
        [
          'status' => 400
        ]
      );

    activate_plugin($pluginPath);

    return rest_ensure_response( [
      'is_active' => 1
    ] );
  }

}