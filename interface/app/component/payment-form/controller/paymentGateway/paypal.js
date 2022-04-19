PaymentPage.Component[ 'payment-form' ].paymentGateway.paypal = {

  controllerInstance : null,
  buttonsInstance : null,

  _paypalSDKSettings : {
    currency        : '',
    is_subscription : '',

    _rendering_timestamp : 0,
  },

  _init : function( controllerInstance, callback ) {
    this.controllerInstance = controllerInstance;

    if( typeof window.paypal !== "undefined" )
      this.unMountPaymentMethod();

    callback();
  },

  _getCurrentPaymentMethodHandlerString : function() {
    return this._getPaymentMethodHandlerString( this.controllerInstance.currentPaymentMethod );
  },

  _getPaymentMethodHandlerString : function( payment_method ) {
    return this.controllerInstance.paymentMethodsMap[ 'paypal' ][ payment_method ].payment_method;
  },

  mountPaymentMethod : function( payment_method, _attemptAutoAdvance = false ) {
    let handler = this._getCurrentPaymentMethodHandlerString( payment_method );

    if( handler === 'standard_checkout' ) {
      this._renderStandardCheckoutButtons();
    }
  },

  unMountPaymentMethod : function() {
    if( this.buttonsInstance !== null && typeof this.buttonsInstance.close === 'function' )
      this.buttonsInstance.close();

    this.buttonsInstance = null;

    if( typeof window.paypal !== "undefined" ) {
      if( typeof window.paypal.__internal_destroy__ === 'function' )
        window.paypal.__internal_destroy__();
    }

    jQuery( '#payment-page-paypal-sdk' ).remove();

    window.paypal = undefined;
  },

  onPaymentTermsChange : function( payment_method ) {
    if( this._getPaymentMethodHandlerString( payment_method ) === 'standard_checkout' )
      this._renderStandardCheckoutButtons();
  },

  maybeEnablePaymentTrigger : function() {
    return true;
  },

  _renderStandardCheckoutButtons : function() {
    let objectInstance = this;

    let productInformation = objectInstance.controllerInstance.getCheckoutInformation();

    if( productInformation.frequency !== 'one-time' ) {
      this.unMountPaymentMethod();

      objectInstance.controllerInstance.paymentTriggerAlternativeContainerObject.html(
        '<div data-payment-page-notification="danger">Not available</div>'
      ).show();
      return;
    }

    if( Math.ceil( Date.now() / 1000 ) - this._paypalSDKSettings._rendering_timestamp < 1 )
      return;

    if( typeof window.paypal === "undefined"
        || this._paypalSDKSettings.currency !== productInformation.currency
        || this._paypalSDKSettings.is_subscription !== ( productInformation.frequency !== 'one-time' ) ) {

      this._paypalSDKSettings._rendering_timestamp = Math.ceil( Date.now() / 1000 );
      this._paypalSDKSettings.currency = productInformation.currency;
      this._paypalSDKSettings.is_subscription = ( productInformation.frequency !== 'one-time' );

      if( this.buttonsInstance !== null )
        this.unMountPaymentMethod();

      let url = 'https://www.paypal.com/sdk/js?client-id=' + this.controllerInstance.configuration.payment_gateways.paypal.client_id;

      url += '&components=buttons&currency=' + productInformation.currency.toUpperCase() + '&vault=true';
      // &enable-funding=venmo

      if( this._paypalSDKSettings.is_subscription )
        url += "&intent=subscription";

      const script = document.createElement('script');

      script.id = "payment-page-paypal-sdk"
      script.src = url;

      jQuery("head").append( script );

      if( typeof window.paypal !== 'undefined' ) {
        objectInstance.__renderStandardCheckoutButtonsSDKReady();
      } else {
        objectInstance.controllerInstance.paymentTriggerAlternativeContainerObject.html( payment_page_element_loader( 'mini' ) ).show();

        // Yay, we need to do shit, because PayPal is... magical.
        setTimeout( function() {
          if( typeof window.paypal !== 'undefined' ) {
            objectInstance.__renderStandardCheckoutButtonsSDKReady();
          } else {
            setTimeout( function() {
              objectInstance.__renderStandardCheckoutButtonsSDKReady();
            }, 5000 );
          }
        }, 2000 );
      }

      return;
    }

    this.__renderStandardCheckoutButtonsSDKReady();
  },

  __renderStandardCheckoutButtonsSDKReady : function() {
    let objectInstance = this;

    this.controllerInstance.paymentTriggerAlternativeContainerObject.attr( 'data-payment-page-size', 'wide' ).show();

    if( this.buttonsInstance === null
        && typeof window.paypal !== 'undefined'
        && typeof window.paypal.FUNDING !== 'undefined' ) {
      let productInformation = objectInstance.controllerInstance.getCheckoutInformation();
      let button_args = {
        fundingSource : window.paypal.FUNDING.PAYPAL,
        onApprove: function(data, actions) {
          return actions.order.capture().then(function(details) {
            objectInstance.controllerInstance.afterPaymentSuccess();
          });
        },
        onCancel : function() {
          objectInstance.__displayFormFields();
        },
        onError : function() {
          objectInstance.__displayFormFields();
        },
        style: {
          layout: 'vertical',
          label:  'paypal'
        }
      };

      if( productInformation.frequency === 'one-time' ) {
        button_args.createOrder = function( data, actions ) {
          objectInstance.__hideFormFields();

          let productInformation = objectInstance.controllerInstance.getCheckoutInformation();

          return actions.order.create( {
            payer       : {
              name  : {
                given_name : objectInstance.controllerInstance.container.find( '[name="first_name"]' ).val(),
                surname    : objectInstance.controllerInstance.container.find( '[name="last_name"]' ).val(),
              },
              email_address : objectInstance.controllerInstance.container.find( '[name="email_address"]' ).val()
            },
            purchase_units : [ {
              description : productInformation.title,
              amount      : {
                currency_code : productInformation.currency.toUpperCase(),
                value : productInformation.price
              }
            } ]
          } );
        };
      } else {
        objectInstance.controllerInstance.paymentTriggerAlternativeContainerObject.html( payment_page_element_loader( 'mini' ) ).show();

        objectInstance.___renderStandardCheckoutButtonsSDKReadySubscription( button_args );

        return;
      }

      this.buttonsInstance = window.paypal.Buttons( button_args );
    }

    if( this.controllerInstance.paymentTriggerAlternativeContainerObject.find( '.paypal-buttons' ).length === 0 ) {
      this.controllerInstance.paymentTriggerAlternativeContainerObject.html( "" );
      this.buttonsInstance.render( '#' + this.controllerInstance.paymentTriggerAlternativeContainerObject.attr( "id" ) );
    }
  },

  __hideFormFields : function() {
    this.controllerInstance.container.find( '[data-payment-page-component-payment-form-section="field_wrapper"]' ).slideUp( "slow" );
  },

  __displayFormFields : function() {
    this.controllerInstance.container.find( '[data-payment-page-component-payment-form-section="field_wrapper"]' ).slideDown( "slow" );
  },

  ___renderStandardCheckoutButtonsSDKReadySubscription : function( button_args ) {
    let objectInstance = this;

    // After subscription id ready.

    this.buttonsInstance = window.paypal.Buttons( button_args );

    this.controllerInstance.paymentTriggerAlternativeContainerObject.html( "" );
    this.buttonsInstance.render( '#' + this.controllerInstance.paymentTriggerAlternativeContainerObject.attr( "id" ) );
  }

};