<?php

namespace PaymentPage;

class AdminController {

  /**
   * @var null|AdminController;
   */
  protected static $_instance = null;

  /**
   * @return AdminController
   */
  public static function instance(): AdminController {
    if( self::$_instance === null )
      self::$_instance = new self();

    return self::$_instance;
  }

  private $_action_map = [
    'force-db-table-integrity' => '_action_force_db_table_integrity'
  ];

  public function setup() {
    $latest_notification = API\Notification::instance()->get_latest_notification();

    if( $latest_notification !== null && isset( $latest_notification[ 'id' ] ) )
      add_action( 'admin_notices', [ $this, '_admin_notice' ] );

    add_action( 'admin_menu', [ $this, '_register_menu' ] );
    add_filter( 'display_post_states', [ $this, '_post_states' ], 1000, 2 );

    if( Request::instance()->is_request_type( 'admin' ) ) {
      add_action( 'admin_enqueue_scripts', 'payment_page_register_universal_interface', 5 );
    }

    if( Settings::instance()->get_flag( 'configuration-setup-rules-flushed' ) === false )
      add_action( 'init', [ $this, '_setup_rules_flush_action_init' ], 7 );

    if( isset( $_GET[ PAYMENT_PAGE_PREFIX . '-action' ] ) && isset( $this->_action_map[ $_GET[ PAYMENT_PAGE_PREFIX . '-action' ] ] ) ) {
      add_action( 'init', [ $this, $this->_action_map[ $_GET[ PAYMENT_PAGE_PREFIX . '-action' ] ] ] );
    }
  }

  public function _admin_notice() {
    if( !current_user_can( PAYMENT_PAGE_ADMIN_CAP ) )
      return;

    $latest_notification = API\Notification::instance()->get_latest_notification();

    if( intval( get_user_meta( get_current_user_id(), PAYMENT_PAGE_ALIAS . '_last_notification_id', true ) ) === intval( $latest_notification[ 'id' ] ) )
      return;

    $protocols = wp_allowed_protocols();

    if( !in_array( 'data', $protocols ) )
      $protocols[] = 'data';

    $content = wp_kses( $latest_notification[ 'content' ], payment_page_content_allowed_html_tags(), $protocols );

    echo '<div id="payment-page-notification-container" class="notice notice-info is-dismissible">
            <h2>' . $latest_notification[ 'title' ]. '</h2>
            ' . $content . '
          </div>';
  }

  public function _register_menu() {
    add_menu_page(
      PAYMENT_PAGE_NAME,
      PAYMENT_PAGE_NAME,
      PAYMENT_PAGE_ADMIN_CAP,
      PAYMENT_PAGE_MENU_SLUG,
      [ $this, '_display_dashboard' ],
      'data:image/svg+xml;base64,' . base64_encode( file_get_contents( PAYMENT_PAGE_BASE_PATH . '/interface/img/icon.svg' ) ),
      100
    );
  }

  public function _post_states( $states, $post ) {
    if( !class_exists( '\Elementor\Plugin' ) )
      return $states;

    $elementor_document = \Elementor\Plugin::instance()->documents->get( $post->ID );

    if( !$elementor_document->is_built_with_elementor() )
      return $states;

    $test = json_encode( $elementor_document->get_elements_data() );

    if( strpos( $test, '"widgetType":"payment-form"' ) !== false )
      $states[] = PAYMENT_PAGE_NAME;

    return $states;
  }

  public function _display_dashboard() {
    Template::load_template( 'component-dashboard.php' );
  }

  public function _setup_rules_flush_action_init() {
    if( Request::instance()->is_request_type( 'ajax' ) )
      return;

    _payment_page_refresh_rewrite_rules_and_capabilities();

    Settings::instance()->update( [
      'configuration-setup-rules-flushed' => 1
    ] );

    add_action( "init", function() {
      payment_page_redirect( Request::instance()->get_current_url() );
      exit;
    }, 500 );
  }

  public function _action_force_db_table_integrity() {
    if( !current_user_can( PAYMENT_PAGE_ADMIN_CAP ) )
      exit( __( 'Invalid Request', 'payment_page' ) );

    $response = Migration::instance()->fix_table_structure( true );

    if( is_array( $response ) ) {
      payment_page_debug_dump( base64_decode( $response[ 'table_query_b64' ] ) );

      exit( __( 'Did not manage to fix Table Structure', 'payment_page' ) );
    }

    payment_page_redirect( get_admin_url( null, 'site-health.php'), 302 );
    exit;
  }

}