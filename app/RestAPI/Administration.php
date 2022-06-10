<?php

namespace PaymentPage\RestAPI;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use PaymentPage\API\Notification as API_Notification;
use PaymentPage\API\PaymentPage as API_PaymentPage;
use PaymentPage\PaymentGateway as PaymentGateway;
use PaymentPage\Settings as Settings;

class Administration {

  public static function register_routes() {
    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/administration/dashboard',
      [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => '\PaymentPage\RestAPI\Administration::dashboard',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        },
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/administration/dismiss-notification',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\Administration::dismiss_notification',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        },
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/administration/template-list',
      [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => '\PaymentPage\RestAPI\Administration::template_list',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        },
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/administration/set-quick-setup-skip',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\Administration::set_quick_setup_skip',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        },
      ]
    );

    register_rest_route(
      PAYMENT_PAGE_REST_API_PREFIX . '/v1',
      '/administration/import-template',
      [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => '\PaymentPage\RestAPI\Administration::import_template',
        'permission_callback' => function() {
          return current_user_can( PAYMENT_PAGE_ADMIN_CAP );
        },
      ]
    );
  }

  public static function dashboard( WP_REST_Request $request ) {
    return rest_ensure_response( [
      'invalid_url_characters'  => [ '~' ],
      'lang'                    => [
        'upgrade'                      => __( "Upgrade >", "payment-page" ),
        'upgrade_payment_gateway'      => __( "To accept %s, please %s", "payment-page" ),
        'upgrade_payment_gateway_recurring' => __( "To accept recurring payments with %s, please %s", "payment-page" ),

        'menu_name_payment_gateways'   => __( "Payment Gateways", "payment-page" ),
        'menu_name_templates'          => __( "Templates", "payment-page" ),

        'payment_gateway_settings_set'    => __( "Configure", "payment-page" ),
        'payment_gateway_settings_edit'   => __( "Configured", "payment-page" ),
        'payment_gateway_settings_title'  => __( "%s Connection Settings", "payment-page" ),

        'payment_gateway_connect'      => __( "Connect with %s", "payment-page" ),
        'payment_gateway_disconnect'   => __( "Disconnect" ),
        'payment_gateway_mode_test'    => __( "Test", "payment-page" ),
        'payment_gateway_mode_live'    => __( "Live", "payment-page" ),

        'payment_methods_title'         => __( "Payment Methods", "payment-page" ),
        'payment_methods_status'        => __( "Status", "payment-page" ),
        'payment_method_requires_https' => __( "This payment method requires a HTTPS connection for both live & testing", "payment-page" ),
        'payment_method_settings_save'  => __( "Save Settings", "payment-page" ),

        'payment_gateway_methods_expand'         => __( "Expand", "payment-page" ),
        'payment_gateway_methods_hide'           => __( "Hide", "payment-page" ),
        'payment_gateway_webhook_settings_save'  => __( "Save Settings", "payment-page" ),

        'template_notification_soon'   => __( "More Coming Soon!", "payment-page" ),
        'template_requires_elementor'  => __( "Requires Elementor", "payment-page" ),
        'template_install_elementor'   => __( "Install Elementor Now", "payment-page" ),
        'template_activate_elementor'  => __( "Active Elementor Now", "payment-page" ),
        'template_select'              => __( "Import >", "payment-page" ),
        'template_preview'             => __( "Preview >", 'payment-page' ),

        'quick_setup_return'   => __( "< %s", 'payment-page' ),
        'quick_setup_next'     => __( "Next, %s >", 'payment-page' ),
        'quick_setup_skip_to'  => __( "Skip to %s >", 'payment-page' ),
        'quick_setup_exit'     => __( "Exit Quick Setup >", "payment-page" ),
        'quick_setup_resume'   => __( "Start Quick Setup >", "payment-page" ),

        'notification_url_invalid_characters' => __( "Your website URL contains invalid character(s) : %s which will cause problems with the %s connection.", "payment-page" ),
        'notification_url_mismatch_ssl'       => __( "Your SSL security certificate is not properly configured on your site. Please configure SSL in order to connect %s. Your hosting provider can help with this.", 'payment-page' )
      ],
      'upgrade_link'    => admin_url( 'admin.php?page=payment-page-pricing' ),
      'template_list'   => self::_get_template_list(),
      'payment_gateway' => PaymentGateway::get_administration_dashboard(),
      'quick_setup_skipped'         => intval( Settings::instance()->get( 'skipped_quick_setup' ) ),
      'quick_setup_steps'           => [
        [
          'alias'         => 'connect_payment_gateway',
          'title'         => __( 'Connect your Payment Gateway', "payment-page" ),
          'sub_title'     => __( "Welcome to Payment Page", "payment-page" ),
          'nav_title'     => __( "Manage Gateways", "payment-page" ),
          'is_completed'  => ( PaymentGateway::get_integration_from_settings( 'stripe' )->get_public_key() !== '' ),
          'template'      => 'payment-gateways'
        ],
        [
          'alias'          => 'connect_stripe_test_gateway',
          'title'          => __( 'Next, Connect Stripe in Test Mode', "payment-page" ),
          'nav_title'      => __( "Manage Gateways", "payment-page" ),
          'is_completed'   => ( Settings::instance()->get( 'stripe_test_public_key' ) !== '' ),
          'requires_steps' => [ 0 ],
          'template'       => 'payment-gateways'
        ],
        [
          'alias'          => 'connect_stripe_live_gateway',
          'title'          => __( 'Next, Connect Stripe in Live Mode', "payment-page" ),
          'nav_title'      => __( "Manage Gateways", "payment-page" ),
          'is_completed'   => ( Settings::instance()->get( 'stripe_live_public_key' ) !== '' ),
          'requires_steps' => [ 0, 1 ],
          'template'       => 'payment-gateways'
        ],
        [
          'alias'          => 'select_template',
          'title'          => __( 'Next, Select a Template', "payment-page" ),
          'nav_title'      => __( "Select a Template", "payment-page" ),
          'is_completed'   => 0, //( intval( Settings::instance()->get( 'primary_template_page_id' ) ) != 0 ),
          'template'       => 'templates'
        ],
      ]
    ] );
  }

  public static function dismiss_notification( WP_REST_Request $request ) {
    $latest_notification = API_Notification::instance()->get_latest_notification();

    if( isset( $latest_notification[ 'id' ] ) )
      update_user_meta(
        get_current_user_id(),
        PAYMENT_PAGE_ALIAS . '_last_notification_id',
        $latest_notification[ 'id' ]
      );

    return rest_ensure_response( [
      'status' => 'ok'
    ] );
  }

  public static function template_list( WP_REST_Request $request ) {
    return rest_ensure_response( [
      'data' => self::_get_template_list()
    ] );
  }

  public static function set_quick_setup_skip( WP_REST_Request $request ) {
    if( !$request->has_param( 'status' ) )
      return new WP_Error(
        'rest_error',
        esc_html( sprintf( __( "Missing request param %s", "payment-page" ), 'status' ) ),
        [
          'status' => 400
        ]
      );

    Settings::instance()->update( [
      'skipped_quick_setup' => ( intval( $request->get_param( 'status' ) ) ? 1 : 0 )
    ] );

    return rest_ensure_response([
      'quick_setup_skipped' => intval( Settings::instance()->get( 'skipped_quick_setup' ) ),
    ] );
  }

  private static function _get_template_list() {
    $template_list = API_PaymentPage::instance()->get_import_template_list();

    foreach( $template_list as $template_key => $template ) {
      $template_list[ $template_key ][ 'user_can_install' ] = intval( $template[ 'user_can_install' ] );

      if( !isset( $template[ 'dependencies' ] ) )
        continue;

      foreach( $template[ 'dependencies' ] as $dependency_key => $dependency ) {
        if( !is_array( $dependency ) || !isset( $dependency[ 'name' ] ) || $dependency[ 'name' ] === 'gutenberg' ) {
          unset( $template[ 'dependencies' ][ $dependency_key ] );
          continue;
        }

        $plugin_slug = ( $dependency[ 'name' ] === 'elementor' ? 'elementor/elementor.php' : false );

        if( $plugin_slug === false ) {
          unset( $template[ 'dependencies' ][ $dependency_key ] );
          continue;
        }

        if( !file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {
          $template[ 'dependencies' ][ $dependency_key ][ 'installed' ] = 0;
          $template[ 'dependencies' ][ $dependency_key ][ 'activated' ] = 0;
          continue;
        }

        $template[ 'dependencies' ][ $dependency_key ][ 'installed' ] = 1;
        $template[ 'dependencies' ][ $dependency_key ][ 'activated' ] = ( is_plugin_active( $plugin_slug ) ? 1 : 0 );
      }

      if( empty( $template[ 'dependencies' ] ) ) {
        unset( $template_list[ $template_key ] );
        continue;
      }

      $template_list[ $template_key ] = $template;
    }

    return $template_list;
  }

  public static function import_template( WP_REST_Request $request ) {
    if( !$request->has_param( 'id' ) )
      return new WP_Error(
        'rest_error',
        esc_html( sprintf( __( "Missing request param %s", "payment-page" ), 'id' ) ),
        [
          'status' => 400
        ]
      );

    $template_information = API_PaymentPage::instance()->get_import_template_content( $request->get_param( 'id' ) );

    if( empty( $template_information ) )
      return new WP_Error(
        'rest_error',
        __( "Could not retrieve template, please try again.", 'payment-page' ),
        [
          'status' => 400
        ]
      );

    unset( $template_information[ 'metadata' ][ '_edit_last' ] );
    unset( $template_information[ 'metadata' ][ '_edit_lock' ] );
    unset( $template_information[ 'metadata' ][ '_wp_old_slug' ] );
    unset( $template_information[ 'metadata' ][ 'is_template' ] );
    unset( $template_information[ 'metadata' ][ '_is_template' ] );

    if( intval( Settings::instance()->get( 'primary_template_page_id' ) ) != 0 ) {
      wp_update_post( [
        'ID' => Settings::instance()->get( 'primary_template_page_id' ),
        'post_content' => $template_information[ 'content'][ 'rendered' ],
      ] );

      $page_id = Settings::instance()->get( 'primary_template_page_id' );

      Settings::instance()->update( [
        'primary_template_import_id' => intval( $request->get_param( 'id' ) )
      ] );
    } else {
      $page_id = wp_insert_post([
        'post_title' => PAYMENT_PAGE_NAME,
        'post_content' => $template_information[ 'content'][ 'rendered' ],
        'post_status' => 'draft',
        'post_author' => 1,
        'post_type' => 'page'
      ] );

      Settings::instance()->update( [
        'primary_template_page_id'  => $page_id,
        'primary_template_import_id' => intval( $request->get_param( 'id' ) )
      ] );
    }

    foreach( $template_information[ 'metadata' ] as $meta_key => $meta_values ) {
      delete_post_meta( $page_id, $meta_key );

      foreach( $meta_values as $meta_value ) {
        $meta_value = maybe_unserialize( $meta_value );

        if( is_string( $meta_value ) )
          $meta_value = addslashes( $meta_value );

        add_post_meta( $page_id, $meta_key, $meta_value );
      }
    }

    return rest_ensure_response( [
      'message' => sprintf( __( "Import Success, %s", 'payment-page' ), '<a href="' . admin_url( 'post.php?post=' . $page_id . '&action=elementor' ) . '" target="_blank">' . __( "Edit Page", "payment-page" ) . '</a>' )
    ] );
  }

}