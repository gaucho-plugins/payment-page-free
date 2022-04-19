PaymentPage.Component[ 'popup-payment-form' ] = {

  container     : {},
  configuration : {

  },

  Init : function( container ) {
    this.container = container;

    let objectInstance = this;

    payment_page_component_configuration_parse( this, function() {
      const urlParams = new URLSearchParams( window.location.search );

      if( urlParams.get( 'redirect_status' ) === 'succeeded' ) {
        objectInstance.DisplayPopup( '', '', 0 );
      }

      PaymentPage.Template.load( objectInstance.container, 'popup-payment-form', 'template/default.html', {
        payment_method_gateways : objectInstance.configuration.payment_gateways
      },function() {
        objectInstance.container.find( '[data-payment-page-component-popup-payment-form-trigger^="start_payment_method_"]' ).off( "click" ).on( "click", function() {
          objectInstance.DisplayPopup(
            jQuery(this).attr( 'data-payment-page-payment-gateway' ),
            jQuery(this).attr( 'data-payment-page-component-popup-payment-form-trigger' ).replace( 'start_payment_method_', '' ),
            1
          );
        });
      } );
    } );
  },

  DisplayPopup : function( payment_gateway, payment_method, ignore_url_params ) {
    let args = payment_page_clone_object( this.configuration );

    args.current_payment_gateway = payment_gateway;
    args.current_payment_method  = payment_method;
    args.ignore_url_params = ignore_url_params;

    if( payment_gateway !== '' && payment_method !== '' ) {
      jQuery.each( args.payment_gateways, function( _payment_gateway_key, payment_gateway_details ) {
        if( _payment_gateway_key !== payment_gateway ) {
          delete args.payment_gateways[ _payment_gateway_key ];
          return true;
        }

        jQuery.each( payment_gateway_details.payment_methods, function( _payment_method_key, payment_method_details ) {
          if( payment_method_details.id !== payment_method ) {
            delete args.payment_gateways[ _payment_gateway_key ].payment_methods[ _payment_method_key ];
            return true;
          }
        });

        args.payment_gateways[ _payment_gateway_key ].payment_methods = Object.values( args.payment_gateways[ _payment_gateway_key ].payment_methods );
      });
    }

    PaymentPage.Library.Popup.display( '<div id="payment-form-' + args.uniqid + '" data-payment-page-component="payment-form" data-payment-page-component-args="' + _.escape( JSON.stringify( args ) )+ '"></div>', {
      trigger_app_init    : true
    } );
  }

};