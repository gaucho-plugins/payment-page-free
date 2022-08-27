<?php

namespace PaymentPage;

use PaymentPage\Migration\Admin as Payment_Page_Migration_Admin;

class Migration {

  /**
   * @var null|Migration;
   */
  protected static $_instance = null;

  /**
   * @return Migration
   */
  public static function instance(): Migration {
    if( self::$_instance === null )
      self::$_instance = new self();

    return self::$_instance;
  }

  public $current_version = '0.0.0';
  public $current_version_available = '1.2.0';
  public $version_file_folder = PAYMENT_PAGE_BASE_PATH . '/lib/migrations/';
  public $version_map = [
    '1.2.0'  => 'version-1.2.0.php'
  ];

  public $option_alias_version  = 'payment_page_migration_version';
  public $option_alias_progress = 'payment_page_migration_progress';

  public $administration = null;

  public function setup() {
    $this->current_version = get_option( $this->option_alias_version, $this->current_version );

    register_activation_hook( PAYMENT_PAGE_BASE_FILE_PATH, [ $this, '_plugin_activation_hook' ] );

    if( is_admin() )
      $this->administration = new Payment_Page_Migration_Admin();
  }

  public function get_table_structure() :array {
    $charset_collate = payment_page_wpdb()->get_charset_collate();

    $log              = payment_page_wpdb()->prefix . PAYMENT_PAGE_TABLE_LOG;
    $payments         = payment_page_wpdb()->prefix . PAYMENT_PAGE_TABLE_PAYMENTS;
    $stripe_customers = payment_page_wpdb()->prefix . PAYMENT_PAGE_TABLE_STRIPE_CUSTOMERS;
    $stripe_products  = payment_page_wpdb()->prefix . PAYMENT_PAGE_TABLE_STRIPE_PRODUCTS;
    $stripe_prices    = payment_page_wpdb()->prefix . PAYMENT_PAGE_TABLE_STRIPE_PRICES;

    $response = [];

    $response[ $log ] = 'CREATE TABLE `' . $log . '` (
                                `id`             bigint(20) NOT NULL AUTO_INCREMENT,
                                `post_id`        bigint(20) NOT NULL DEFAULT 0,
                                `namespace`      VARCHAR(500) NOT NULL DEFAULT "",
                                `action`         VARCHAR(500) NOT NULL DEFAULT "",
                                `content`        LONGTEXT NOT NULL,
                                `created_at`     int(11) NOT NULL,
                                PRIMARY KEY (id),
                                KEY ' . $log . '_post_id (post_id)
                              ) ' . $charset_collate;

    $response[ $payments ] = 'CREATE TABLE `' . $payments . '` (
                            `id`             bigint(20) NOT NULL AUTO_INCREMENT,
                            `post_id`        bigint(20) NOT NULL DEFAULT 0,
                            `user_id`        bigint(20) NOT NULL DEFAULT 0,
                            `email_address`   VARCHAR(500) NOT NULL DEFAULT "",
                            `first_name`      VARCHAR(500) NOT NULL DEFAULT "",
                            `last_name`       VARCHAR(500) NOT NULL DEFAULT "",
                            `payment_gateway` VARCHAR(200) NOT NULL DEFAULT "",
                            `payment_method`  VARCHAR(200) NOT NULL DEFAULT "",
                            `metadata_json`   LONGTEXT NOT NULL,
                            `amount`          int(11) NOT NULL DEFAULT 0,
                            `amount_received` int(11) NOT NULL DEFAULT 0,
                            `currency`        VARCHAR(100) NOT NULL DEFAULT "",
                            `is_paid`         int(1) NOT NULL DEFAULT 0,
                            `is_live`         int(1) NOT NULL DEFAULT 0,
                            `created_at`      int(11) NOT NULL,
                            `updated_at`      int(11) NOT NULL,
                            PRIMARY KEY (id),
                            KEY ' . $payments . '_post_id (post_id),
                            KEY ' . $payments . '_user_id (user_id)
                          ) ' . $charset_collate;

    $response[ $stripe_customers ] = 'CREATE TABLE `' . $stripe_customers . '` (
                                `id`                    bigint(20) NOT NULL AUTO_INCREMENT,
                                `email_address`         VARCHAR(500) NOT NULL DEFAULT "",
                                `stripe_id`             VARCHAR(500) NOT NULL DEFAULT "",
                                `stripe_account_id`     VARCHAR(500) NOT NULL DEFAULT "",
                                `subscription_currency` VARCHAR(10) NOT NULL DEFAULT "none",
                                `is_live`               int(1) NOT NULL DEFAULT 0,
                                `created_at`            int(11) NOT NULL,
                                `updated_at`            int(11) NOT NULL,
                                PRIMARY KEY (id),
                                KEY ' . $stripe_customers . '_stripe_id (stripe_id),
                                KEY ' . $stripe_customers . '_is_live (is_live),
                                KEY ' . $stripe_customers . '_stripe_account_id (stripe_account_id)
                              ) ' . $charset_collate;

    $response[ $stripe_products ] = 'CREATE TABLE `' . $stripe_products . '` (
                                `id`                bigint(20) NOT NULL AUTO_INCREMENT,
                                `title`             VARCHAR(1000) NOT NULL DEFAULT "",
                                `stripe_id`         VARCHAR(500) NOT NULL DEFAULT "",
                                `stripe_account_id` VARCHAR(500) NOT NULL DEFAULT "",
                                `is_live`           int(1) NOT NULL DEFAULT 0,
                                `created_at`        int(11) NOT NULL,
                                `updated_at`        int(11) NOT NULL,
                                PRIMARY KEY (id),
                                KEY ' . $stripe_products . '_stripe_id (stripe_id),
                                KEY ' . $stripe_products . '_is_live (is_live),
                                KEY ' . $stripe_products . '_stripe_account_id (stripe_account_id)
                              ) ' . $charset_collate;

    $response[ $stripe_prices ] = 'CREATE TABLE `' . $stripe_prices . '` (
                                `id`                bigint(20) NOT NULL AUTO_INCREMENT,
                                `product_id`        bigint(20) NOT NULL,
                                `stripe_id`         VARCHAR(500) NOT NULL DEFAULT "",
                                `stripe_product_id` VARCHAR(500) NOT NULL DEFAULT "",
                                `stripe_account_id` VARCHAR(500) NOT NULL DEFAULT "",
                                `price`             int(11) NOT NULL DEFAULT 0,
                                `currency`          VARCHAR(5) NOT NULL DEFAULT "",
                                `frequency`         VARCHAR(100) NOT NULL DEFAULT "",
                                `is_live`           int(1) NOT NULL DEFAULT 0,
                                `created_at`        int(11) NOT NULL,
                                `updated_at`        int(11) NOT NULL,
                                PRIMARY KEY (id),
                                KEY ' . $stripe_prices . '_product_id (product_id),
                                KEY ' . $stripe_prices . '_stripe_id (stripe_id),
                                KEY ' . $stripe_prices . '_is_live (is_live),
                                KEY ' . $stripe_prices . '_stripe_product_id (stripe_product_id),
                                KEY ' . $stripe_prices . '_stripe_account_id (stripe_account_id)
                              ) ' . $charset_collate;

    return $response;
  }

  public function get_table_list() :array {
    return array_keys( $this->get_table_structure() );
  }

  public function _plugin_activation_hook() {
    if( !$this->handle_migration_in_background() )
      return;

    $table_structure = $this->get_table_structure();

    if( !empty( $table_structure ) ) {
      foreach( $table_structure as $table_name => $table_query )
        payment_page_dbDelta( $table_query );

      $this->current_version = $this->current_version_available;

      update_option( $this->option_alias_version, $this->current_version_available );
    }

    if( method_exists( $this,'_after_background_migration' ) )
      $this->_after_background_migration();

    if( method_exists( $this,'_after_activation' ) )
      $this->_after_activation();

    Settings::instance()->update( [
      'configuration-setup-rules-flushed' => 0
    ] );
  }


  public function get_current_migration_version() {
    foreach( $this->version_map as $migration_version => $migration_file )
      if( version_compare( $migration_version, $this->current_version, ">")  )
        return $migration_version;

    return false;
  }

  public function is_migration_required() {
    return version_compare( $this->current_version_available, $this->current_version, ">");
  }

  public function can_run_in_background() :bool {
    if( $this->current_version != '0.0.0' )
      return false;

    return true;
  }

  public function migrate_to_version( string $version_number ): string {
    @set_time_limit(0);
    @ini_set('memory_limit','512M');

    $last_migration = get_option( $this->option_alias_progress, 0 );

    if( time() - $last_migration < 30 )
      return "<p>" . sprintf( __( "Migration currently marked in progress, it started %s seconds ago.", "payment-page" ), ( $last_migration == 0 ? 'too many' : time() - $last_migration ) ). "</p>" .
             '<p><a class="button button-primary payment-page-migration-continue">' . __( "Retry", "payment-page" ) . '</a></p>';

    if( !version_compare( $version_number, $this->current_version, ">" ) && $this->current_version != 0 )
      return "<p>" . __( "This version has already been installed, skipping", "payment-page" ) . "</p>";

    update_option( $this->option_alias_progress, time() );

    $response = $this->_migrate_to_version( $version_number );

    delete_option( $this->option_alias_progress );

    return $response;
  }

  private function _migrate_to_version( $migrate_to_version ): string {
    $migrate_to_version_response = '';

    $migration_okay = true;

    foreach( $this->version_map as $migration_version => $migration_file ) {
      if ( version_compare( $this->current_version, $migration_version ) != -1 )
        continue;

      if( !file_exists( $this->version_file_folder . $migration_file ) ){
        $migrate_to_version_response .= '<p data-payment-page-notification="danger">' . sprintf( __( "File not found %s.", "payment-page" ), $this->version_file_folder . $migration_file ). '</p>';
        $migrate_to_version_response .= '<p><a class="button button-primary payment-page-migration-continue">' . __( "Retry", "payment-page" ) . '</a></p>';
        $migration_okay = false;
        break;
      }

      if ( version_compare( $migrate_to_version, $migration_version, "<") )
        break;

      ob_start();

      require_once( $this->version_file_folder . $migration_file );

      $migrate_to_version_response = ob_get_contents() . $migrate_to_version_response;

      ob_end_clean();

      if( strpos($migrate_to_version_response, 'payment-page-migration-failed-message' ) !== false
          || strpos($migrate_to_version_response, 'payment-page-migration-repeat-file-message' ) !== false ) {
        $migration_okay = false;

        break;
      }
    }

    if( $migration_okay ) {
      $this->current_version = $migrate_to_version;
      update_option( $this->option_alias_version, $migrate_to_version );
    }

    return $migrate_to_version_response;
  }

  public function is_valid_table_structure(): bool {
    $table_structure = $this->get_table_structure();

    if( empty( $table_structure ) )
      return true;

    foreach( $table_structure as $table_name => $table_query ) {
      $sql = payment_page_wpdb()->prepare( "SHOW TABLES LIKE %s", payment_page_wpdb()->esc_like( $table_name ) );

      if( payment_page_wpdb()->get_var( $sql ) === null )
        return false;
    }

    return true;
  }

  /**
   * @param bool $force_dbDelta
   * @return array|bool - True(bool) if everything is good
   */
  public function fix_table_structure( bool $force_dbDelta ) {
    $table_structure = $this->get_table_structure();

    if( empty( $table_structure ) )
      return true;

    foreach( $table_structure as $table_name => $table_query ) {
      if( !$force_dbDelta ) {
        $sql = payment_page_wpdb()->prepare( "SHOW TABLES LIKE %s", payment_page_wpdb()->esc_like( $table_name ) );

        if( payment_page_wpdb()->get_var( $sql ) != null )
          continue;
      }

      payment_page_dbDelta( $table_query );

      $sql = payment_page_wpdb()->prepare( "SHOW TABLES LIKE %s", payment_page_wpdb()->esc_like( $table_name ) );

      if( payment_page_wpdb()->get_var( $sql ) === null )
        return [
          'status'          => 'error',
          'table'           => $table_name,
          'table_query_b64' => base64_encode( $table_query )
        ];
    }

    return true;
  }

  public function handle_migration_in_background() :bool {
    if( $this->current_version == '0.0.0' )
      return get_option( $this->option_alias_version, false ) === false;

    return false;
  }

}