<?php

namespace PaymentPage\Migration;

use PaymentPage\Migration;
use PaymentPage\Settings;

class Admin {

  public function __construct() {
    add_action( 'wp_ajax_payment_page_migration_handler', [ $this, 'migrate_single' ] );

    if( Migration::instance()->is_migration_required() ) {
      add_action( 'admin_init', [ $this, '_init' ] );
      add_action( 'admin_menu', [ $this, '_register_menu' ], 50 );
    } else if( isset( $_GET['page'] ) && ( str_starts_with( $_GET['page'], 'payment-page-migration' ) ) ) {
      add_action( "init", function() {
        payment_page_redirect( admin_url( PAYMENT_PAGE_DEFAULT_URL_PATH ) );
        exit;
      });
    }
  }

  public function _init() {
    if( isset( $_GET['page'] ) && ( str_starts_with($_GET['page'], 'payment-page-migration' ) ) )
      return;

    if( isset( $_GET[ 'page' ] ) && str_starts_with( $_GET['page'], 'payment-page-' ) ) {
      payment_page_redirect( admin_url( 'admin.php?page=payment-page-migration' ) );
      exit;
    }

    add_action('admin_notices', [ $this, 'admin_notices' ] );
  }

  public function _register_menu() {
    payment_page_admin_register_menu(
      sprintf( __(" %s Migration", "payment-page" ), PAYMENT_PAGE_NAME ),
      __( "Migration", "payment-page"),
      PAYMENT_PAGE_ADMIN_CAP,
      "payment-page-migration",
      [ $this, "display" ]
    );
  }

  public function display() {
    echo '<div class="wrap">';
    echo '<h2>' . sprintf( __( "%s Migration", "payment-page"), PAYMENT_PAGE_NAME ) . '</h2>';
    echo '<hr/>';

    echo '<p>' . sprintf( __( "You're about to upgrade to %s version %s this version brings in database upgrades.", "payment-page" ), PAYMENT_PAGE_NAME, PAYMENT_PAGE_VERSION ) . '</p>';
    echo '<p>' . __( "Please do not close this screen after pressing continue, until the update is completed.", "payment-page" ) . '</p>';
    echo '<a class="button button-primary payment-page-migration-continue">' . __( "Continue", "payment-page" ) . '</a>';

    echo '<div id="migration-payment-page-spinner-loader-container" style="display:none;"></div>';

    echo '<div id="payment-page-migration-helper-log"></div>';
    echo '</div>';

    echo '
      <style>
      #payment-page-migration-helper-log {
         display:none;
         background: var( --payment-page-layout-secondary-background-color );
         padding: var( --payment-page-spacing-type-secondary );
         border-radius: 5px;
      }
      
      #payment-page-migration-helper-log > p {
        margin : 0 0 var( --payment-page-spacing-type-block-element ) 0;
        font-size : var( --payment-page-text-default-font-size );
      }
      
      </style>
      <script type="text/javascript">
        var PAYMENT_PAGE_Migration = {
        
          processing : false,
        
          init : function() {
            var objectInstance = this;
            
            jQuery("#wpbody-content").on( "click", ".payment-page-migration-continue", function() {
              jQuery(this).fadeOut("slow");
              
              objectInstance.migrateToNext();
            });
            
            PaymentPage.setLoadingContent( jQuery("#migration-payment-page-spinner-loader-container") );
          },
          
          migrateToNext : function( request_data, last_response ) {
            if( this.processing === true )
              return;
            
            last_response = ( typeof last_response === "undefined" ? "" : last_response );
            
            jQuery("#migration-payment-page-spinner-loader-container").slideDown("slow");
              
            var objectInstance = this;
            
            request_data = ( request_data !== undefined && request_data !== null && request_data.constructor === Object ? request_data : {} );
            request_data.action  = "payment_page_migration_handler";
            request_data.attempt = ( typeof request_data.attempt !== "undefined" ? request_data.attempt : 0 );
            
            this.processing = true;
            
            jQuery.post(ajaxurl, request_data )
                  .done( function (response) {
                    objectInstance.processing = false;

                    if( last_response !== "" ) {
                      if( jQuery( "#payment-page-migration-attempt-helper" ).length === 0 )
                        jQuery( "body" ).append( \'<div id="payment-page-migration-attempt-helper" style="display: none !important;"></div>\' );
                      
                      var helperObject = jQuery( "#payment-page-migration-attempt-helper" );
                      
                      helperObject.html( response );
                      helperObject.find( "[data-payment-page-migration-attempt]" ).remove();
                      
                      var current_response_clean = helperObject.html();
                      
                      helperObject.html( last_response );
                      helperObject.find( "[data-payment-page-migration-attempt]" ).remove();
                      
                      if( helperObject.html() === current_response_clean )
                        request_data.attempt++;
                      else 
                        request_data.attempt = 0;
                    }
                                                            
                    jQuery("#payment-page-migration-helper-log").show().prepend( response );
                    
                    if( jQuery( response ).find(".payment-page-migration-continue , .payment-page-migration-complete").length === 0 && response.indexOf("payment-page-migration-complete") === -1 )
                      objectInstance.migrateToNext( { 
                        attempt        : request_data.attempt
                      }, response );
                    else 
                      jQuery("#migration-payment-page-spinner-loader-container").slideUp("slow");
                  } ).fail( function() {
                    objectInstance.processing = false;
                    
                    objectInstance.migrateToNext();
                  });
          }
        
        };
        
        jQuery( window ).on( "payment_page_ready", function() {
          PAYMENT_PAGE_Migration.init();
        });
      </script>
    ';
  }

  public function admin_notices($hook) {

    $url = admin_url( 'admin.php?page=payment-page-migration' );

    echo '<div class="notice notice-success is-dismissible">';
    echo    '<h2>' . sprintf(__( '%s Database Upgrade', "payment-page" ), PAYMENT_PAGE_NAME ) . '</h2>';
    echo    '<p>' . sprintf( 'Before you continue to use %s you need to migrate to the newest version.', PAYMENT_PAGE_NAME ) . '</p>';
    echo    '<p><a class="button button-primary" href="' . $url .'">' . __( "Click here to continue", "payment-page" ) . '</a></p>';
    echo '</div>';

  }

  public function migrate_single() {
    if( !current_user_can( PAYMENT_PAGE_ADMIN_CAP ) )
      exit;

    if( Migration::instance()->current_version >= Migration::instance()->current_version_available ) {
      echo '<p class="payment-page-migration-complete">' . __( "Database successfully updated. Continue to : ", "payment-page" ) . '</p>';
      echo '<p>';
      echo    '<a href="' . admin_url( PAYMENT_PAGE_DEFAULT_URL_PATH ) . '" class="button button-primary">' . __( "Manage Settings", "payment-page" ) . '</a> ';
      echo '</p>';
      exit;
    }

    $version = Migration::instance()->get_current_migration_version();

    if( $version === false ) {
      echo '<p data-payment-page-notification="danger">' . __( "Could not determine the migration version.", "payment-page" ). '</p>';
      echo '<p><a class="button button-primary payment-page-migration-continue">' . __( "Retry", "payment-page" ) . '</a></p>';
      exit;
    }

    $attempt = ( isset( $_POST[ 'attempt'] ) ? intval( $_POST[ 'attempt'] ) : 0 );

    echo '<p data-payment-page-migration-version="' . $version . '">';
    echo    sprintf( __( "Processing File : %s", "payment-page" ), $version );

    if( $attempt !== 0 ) {
      echo '<span data-payment-page-migration-attempt="' . $attempt . '">';
      echo   '( ' . sprintf( __( "Retry Attempt : %s; Current Version : %s" , "payment-page"), $attempt, Migration::instance()->current_version ) . ' )';
      echo '</span>';
    }
    echo '</p>';

    if( $attempt >= 5 ) {
      Migration::instance()->current_version = $version;
      update_option( Migration::instance()->option_alias_version, $version );

      echo '<p data-payment-page-notification="danger">';
      echo    sprintf( __( "Skipped Migration file for version : %s, check your Platform Health Report for possible issues.", "payment-page" ), $version );
      echo '</p>';
      exit;
    }

    echo Migration::instance()->migrate_to_version( $version );

    Settings::instance()->update( [
      'configuration-setup-rules-flushed' => 1
    ] );

    exit;
  }

}