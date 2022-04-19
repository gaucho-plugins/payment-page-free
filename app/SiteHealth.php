<?php

namespace PaymentPage;

class SiteHealth {

  /**
   * @var null|SiteHealth;
   */
  protected static $_instance = null;

  /**
   * @return SiteHealth
   */
  public static function instance(): SiteHealth {
    if( self::$_instance === null )
      self::$_instance = new self();

    return self::$_instance;
  }

  public function tests( $tests ) {
    $tests[ 'direct' ][ PAYMENT_PAGE_ALIAS . '_test_database_integrity' ] = [
      'label' => __( '%s - Database Integrity', "payment-page" ),
      'test'  => [ $this, 'database_integrity' ],
    ];

    return $tests;
  }

  public function database_integrity(): array {
    $default = [
      'description' => '<p>' . sprintf( __( "%s uses custom tables to store data efficiently.", "payment-page" ), PAYMENT_PAGE_NAME ) . '</p>',
      'test'        => PAYMENT_PAGE_ALIAS . '_test_cron_requirement',
    ];

    if( Migration::instance()->is_valid_table_structure() )
      return [
          'label'   => sprintf( __( "%s - Valid DB Table Structure.", "payment-page" ), PAYMENT_PAGE_NAME ),
          'status'  => 'good',
          'badge'       => [
            'label' => __( 'Critical', "payment-page" ),
            'color' => 'green',
          ],
        ] + $default;

    return [
        'label'       => sprintf( __( "%s - Invalid DB Table Structure.", "payment-page" ), PAYMENT_PAGE_NAME ),
        'status'      => 'critical',
        'badge'       => [
          'label' => __( 'Critical', "payment-page" ),
          'color' => 'red',
        ],
        'actions'     => sprintf(
          '<p><a href="%s">%s</a></p>',
          esc_url( admin_url( '?' . PAYMENT_PAGE_PREFIX . '-action=force-db-table-integrity' ) ),
          __( "Fix Table Integrity", "payment-page" )
        )
      ] + $default;
  }

}