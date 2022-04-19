PaymentPage.Component[ 'admin-marketing-notifications' ] = {

  container     : {},
  configuration : {
    area_slug : ""
  },
  data          : {
    name        : '',
    description : '',
    features    : [],
    submit_text : '',

    first_name    : '',
    last_name     : '',
    email_address : '',
    current_user  : {}
  },

  Init : function( container ) {
    this.container = container;

    let objectInstance = this;

    payment_page_component_configuration_parse( this, function() {
      objectInstance._loadData();
    } );
  },

  _loadData : function() {
    let objectInstance = this;

    PaymentPage.setLoadingContent( this.container );

    PaymentPage.API.fetch('payment-page/v1/tagging/area/' + this.configuration.area_slug, false, function( response ) {
      if( typeof response !== 'object' ) {
        PaymentPage.setFailedAssetFetchContent( objectInstance.container );
        return;
      }

      objectInstance.data = response.data;

      objectInstance._loadTemplate();
    } );
  },

  _loadTemplate : function() {
    let objectInstance = this;

    PaymentPage.Template.load( this.container, 'admin-marketing-notifications', 'template/default.html', this.data, function() {
      objectInstance._bindEvents();
    });
  },

  _bindEvents : function() {
    let objectInstance = this;

    this.container.find( '[data-payment-page-component-admin-marketing-notifications-trigger^="pgs_notification_"]' ).on( "click", function() {
      let interaction_state = ( typeof jQuery(this).attr( "data-payment-page-interaction-state" ) === 'undefined' ? '' : jQuery(this).attr( "data-payment-page-interaction-state" ) );

      if( interaction_state === 'selected' )
        jQuery(this).removeAttr( "data-payment-page-interaction-state" );
      else
        jQuery(this).attr( 'data-payment-page-interaction-state', 'selected' );

      if( objectInstance.container.find( '[data-payment-page-component-admin-marketing-notifications-trigger^="pgs_notification_"][data-payment-page-interaction-state="selected"]' ).length === 0 )
        objectInstance.container.find( ' > form' ).hide( "slow" );
      else
        objectInstance.container.find( ' > form' ).show( "slow" );

      objectInstance.container.find( '[data-payment-page-notification]' ).remove();
    });

    this.container.find( '[data-payment-page-component-admin-marketing-notifications-trigger="pgs_notification"]' ).on( "click", function() {
      if( jQuery(this).find( '.payment-page-application-loader-wrapper').length > 0 )
        return;

      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      let trigger = jQuery(this),
          tags = [];

      objectInstance.container.find( '[data-payment-page-notification]' ).remove();

      objectInstance.container.find( '[data-payment-page-component-admin-marketing-notifications-trigger^="pgs_notification_"][data-payment-page-interaction-state="selected"]' ).each( function() {
        tags.push( jQuery(this).attr( 'data-payment-page-tag' ) );
      });

      PaymentPage.API.post( 'payment-page/v1/tagging/apply', {
        first_name    : objectInstance.container.find( '[name="first_name"]' ).val(),
        last_name     : objectInstance.container.find( '[name="last_name"]' ).val(),
        email_address : objectInstance.container.find( '[name="email_address"]' ).val(),
        tags          : tags,
        area_slug     : objectInstance.configuration.area_slug
      }, function( response ) {
        if( typeof response.message !== 'undefined' )
          objectInstance.container.find( ' > form' ).append( '<div data-payment-page-notification="' + ( typeof response.code !== 'undefined' ? 'danger' : 'success' ) + '">' + response.message + '</div>' );

        trigger.html( objectInstance.data.submit_text );
      });
    });
  }

};