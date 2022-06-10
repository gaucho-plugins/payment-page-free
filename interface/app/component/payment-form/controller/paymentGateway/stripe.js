PaymentPage.Component[ 'payment-form' ].paymentGateway.stripe = {

  controllerInstance : null,

  _stripeSDK : null,
  _stripeElements : null,

  cardElement : null,
  expiryElement : null,
  cvcElement : null,
  ibanElement : null,

  paymentRequestObject : null,
  paymentRequestButtonObject : null,

  labelObject : {
    card   : null,
    expiry : null,
    cvc    : null,
    iban   : null,
  },

  isEmpty : {
    card   : true,
    expiry : true,
    cvc    : true,
    iban   : true,
  },

  isValid : {
    card   : false,
    expiry : false,
    cvc    : false,
    iban   : false
  },

  _init : function( controllerInstance, callback ) {
    this.controllerInstance = controllerInstance;

    let objectInstance = this;

    if( typeof window.Stripe === "undefined" ) {
      PaymentPage.LoadAssets( 'https://js.stripe.com/v3/', function() {
        objectInstance.__initStripeSDKLoaded( callback );
      }, false );

      return;
    }

    objectInstance.__initStripeSDKLoaded( callback );
  },

  __initStripeSDKLoaded : function( callback ) {
    if( this._stripeSDK === null )
      this._stripeSDK = window.Stripe( this.controllerInstance.configuration.payment_gateways.stripe.publishable_key, {
        apiVersion: "2020-08-27",
      } );

    callback();
  },

  _getCurrentPaymentMethodHandlerString : function() {
    return this._getPaymentMethodHandlerString( this.controllerInstance.currentPaymentMethod );
  },

  _getPaymentMethodHandlerString : function( payment_method ) {
    return this.controllerInstance.paymentMethodsMap[ 'stripe' ][ payment_method ].payment_method;
  },

  mountPaymentMethod : function( payment_method, _attemptAutoAdvance = false ) {
    let handler = this._getCurrentPaymentMethodHandlerString( payment_method );

    if( handler === 'ccard' ) {
      this._mountCreditCard();
    } else if( handler === 'sepa' ) {
      this._mountSEPA();
    } else if( handler === 'ach_direct_debit' ) {
      this._mountACHDirectDebit();
    } else if( handler === 'alipay' ) {
      this._mountAliPay( _attemptAutoAdvance );
    } else if( handler === 'wechat' ) {
      this._mountWeChat( _attemptAutoAdvance );
    } else if( payment_page_in_array( handler, [ 'apple_pay', 'google_pay', 'microsoft_pay' ] ) ) {
      this._mountPaymentRequest( handler, _attemptAutoAdvance );
    }
  },

  onPaymentTermsChange : function( payment_method ) {
    if( payment_page_in_array( this._getPaymentMethodHandlerString( payment_method ), [ 'apple_pay', 'google_pay', 'microsoft_pay' ] ) )
      this.__syncPaymentRequestObject();
  },

  maybeEnablePaymentTrigger : function() {
    if( this._getCurrentPaymentMethodHandlerString() === 'ccard' ) {
      if( !this.isValid.card || !this.isValid.expiry || !this.isValid.cvc ) {
        this.controllerInstance.paymentTriggerObject.attr( "disabled", "disabled" );
        return false;
      }

      if( this.controllerInstance.container.find( '[name="pp_zip"]' ).val() === '' ) {
        this.controllerInstance.paymentTriggerObject.attr( "disabled", "disabled" );
        return false;
      }
    }

    return true;
  },

  _mountCreditCard : function() {
    let objectInstance = this;

    this._stripeElements = this._stripeSDK.elements();

    this.cardElement = this._stripeElements.create( 'cardNumber', { style : this.__getStripeElementInputStyle() } );
    this.expiryElement = this._stripeElements.create( 'cardExpiry', { style : this.__getStripeElementInputStyle() } );
    this.cvcElement = this._stripeElements.create( 'cardCvc', { style : this.__getStripeElementInputStyle() } );

    this.cardElement.mount( document.querySelector( '#payment-page-card_number-' + this.controllerInstance.configuration.uniqid ) );
    this.expiryElement.mount( document.querySelector( '#payment-page-card_expiration_date-' + this.controllerInstance.configuration.uniqid ) );
    this.cvcElement.mount( document.querySelector( '#payment-page-card_cvc-' + this.controllerInstance.configuration.uniqid ) );

    this.labelObject.card = this.controllerInstance.container.find( 'label[for="payment-page-card_number-' + this.controllerInstance.configuration.uniqid + '"]');
    this.labelObject.expiry = this.controllerInstance.container.find( 'label[for="payment-page-card_expiration_date-' + this.controllerInstance.configuration.uniqid + '"]');
    this.labelObject.cvc = this.controllerInstance.container.find( 'label[for="payment-page-card_cvc-' + this.controllerInstance.configuration.uniqid + '"]');

    this.cardElement.on('focus', function(event) {
      objectInstance.labelObject.card
                                .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                .attr( "data-payment-page-interaction-state", "focus" );
    });

    this.expiryElement.on('focus', function(event) {
      objectInstance.labelObject.expiry
                                .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                .attr( "data-payment-page-interaction-state", "focus" );
    });

    this.cvcElement.on('focus', function(event) {
      objectInstance.labelObject.cvc
                                .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                .attr( "data-payment-page-interaction-state", "focus" );
    });

    this.cardElement.on('blur', function(event) {
      objectInstance.labelObject.card
                                .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                .attr( "data-payment-page-interaction-state", ( objectInstance.isEmpty.card ? "blur" : ( objectInstance.isValid.card ? "not-empty" : "error" ) ) );
    });

    this.expiryElement.on('blur', function(event) {
      objectInstance.labelObject.expiry
                                .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                .attr( "data-payment-page-interaction-state", ( objectInstance.isEmpty.expiry ? "blur" : ( objectInstance.isValid.expiry ? "not-empty" : "error" ) ) );
    });

    this.cvcElement.on('blur', function(event) {
      objectInstance.labelObject.cvc
                                .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                .attr( "data-payment-page-interaction-state", ( objectInstance.isEmpty.cvc ? "blur" : ( objectInstance.isValid.cvc ? "not-empty" : "error" ) ) );
    });

    this.cardElement.on('change', function(event) {
      objectInstance.isEmpty.card = event.empty;

      if (event.error) {
        objectInstance.labelObject.card.attr( 'data-payment-page-interaction-state', 'error' ).html( event.error.message );
        objectInstance.isValid.card = false;
      } else {
        objectInstance.labelObject.card.attr( 'data-payment-page-interaction-state', 'good' ).html(
          objectInstance.controllerInstance.configuration.field_map.card_number.label
        );
        objectInstance.isValid.card = true;
      }

      if( !event.empty ) {
        objectInstance.labelObject.card
                                  .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                  .attr( "data-payment-page-interaction-state", "not-empty" );
      }

      objectInstance.controllerInstance._maybeEnablePaymentTrigger();
    });

    this.expiryElement.on('change', function(event) {
      objectInstance.isEmpty.expiry = event.empty;

      if (event.error) {
        objectInstance.labelObject.expiry.attr( 'data-payment-page-interaction-state', 'error' ).html( event.error.message );
        objectInstance.isValid.expiry = false;
      } else {
        objectInstance.labelObject.expiry.attr( 'data-payment-page-interaction-state', 'good' ).html(
          objectInstance.controllerInstance.configuration.field_map.card_expiration_date.label
        );
        objectInstance.isValid.expiry = true;
      }

      if( !event.empty ) {
        objectInstance.labelObject.expiry
                                  .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                  .attr( "data-payment-page-interaction-state", "not-empty" );
      }

      objectInstance.controllerInstance._maybeEnablePaymentTrigger();
    });

    this.cvcElement.on('change', function(event) {
      objectInstance.isEmpty.cvc = event.empty;

      if (event.error) {
        objectInstance.labelObject.cvc.attr( 'data-payment-page-interaction-state', 'error' ).html( event.error.message );
        objectInstance.isValid.cvc = false;
      } else {
        objectInstance.labelObject.cvc.attr( 'data-payment-page-interaction-state', 'good' ).html(
          objectInstance.controllerInstance.configuration.field_map.card_cvc.label
        );
        objectInstance.isValid.cvc = true;
      }

      if( !event.empty ) {
        objectInstance.labelObject.cvc
                                  .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                  .attr( "data-payment-page-interaction-state", "not-empty" );
      }

      objectInstance.controllerInstance._maybeEnablePaymentTrigger();
    });

    this.controllerInstance.paymentTriggerObject.off( "click" ).on( "click", function() {
      if( typeof jQuery(this).attr( "disabled" ) !== 'undefined' )
        return;

      if( jQuery(this).find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      objectInstance.controllerInstance.paymentTriggerObject.parent().find( '[data-payment-page-notification]' ).remove();

      jQuery(this).attr( "disabled", "disabled" );
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.__createPaymentMethodCreditCard();
    });

    this.controllerInstance.paymentTriggerObject.show();
  },

  _mountSEPA : function() {
    let objectInstance = this;

    this._stripeElements = this._stripeSDK.elements();

    this.ibanElement = this._stripeElements.create( 'iban', {
      style              : this.__getStripeElementInputStyle(),
      supportedCountries : [ 'SEPA' ]
    } );
    this.ibanElement.mount( document.querySelector( '#payment-page-iban-' + this.controllerInstance.configuration.uniqid ) );
    this.labelObject.iban = this.controllerInstance.container.find( 'label[for="payment-page-iban-' + this.controllerInstance.configuration.uniqid + '"]');

    this.ibanElement.on('focus', function(event) {
      objectInstance.labelObject.iban
                                .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                .attr( "data-payment-page-interaction-state", "focus" );
    });

    this.ibanElement.on('blur', function(event) {
      objectInstance.labelObject.iban
                                .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                .attr( "data-payment-page-interaction-state", ( objectInstance.isEmpty.iban ? "blur" : ( objectInstance.isValid.iban ? "not-empty" : "error" ) ) );
    });

    this.ibanElement.on('change', function(event) {
      objectInstance.isEmpty.iban = event.empty;

      if (event.error) {
        objectInstance.labelObject.iban.attr( 'data-payment-page-interaction-state', 'error' ).html( event.error.message );
        objectInstance.isValid.iban = false;
      } else {
        objectInstance.labelObject.iban.attr( 'data-payment-page-interaction-state', 'good' ).html( 'IBAN' );
        objectInstance.isValid.iban = true;
      }

      if( !event.empty ) {
        objectInstance.labelObject.iban
                                  .parents( '[data-payment-page-component-payment-form-section="field"]' )
                                  .attr( "data-payment-page-interaction-state", "not-empty" );
      }

      objectInstance.controllerInstance._maybeEnablePaymentTrigger();
    });

    this.controllerInstance.paymentTriggerObject.off( "click" ).on( "click", function() {
      if( typeof jQuery(this).attr( "disabled" ) !== 'undefined' )
        return;

      if( jQuery(this).find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      objectInstance.controllerInstance.paymentTriggerObject.parent().find( '[data-payment-page-notification]' ).remove();

      jQuery(this).attr( "disabled", "disabled" );
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.__createPaymentMethodSEPADebit();
    });

    this.controllerInstance.paymentTriggerObject.show();
  },

  _mountACHDirectDebit : function() {
    let objectInstance = this;

    this.controllerInstance.paymentTriggerObject.off( "click" ).on( "click", function() {
      if( typeof jQuery(this).attr( "disabled" ) !== 'undefined' )
        return;

      if( jQuery(this).find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      objectInstance.controllerInstance.paymentTriggerObject.parent().find( '[data-payment-page-notification]' ).remove();

      jQuery(this).attr( "disabled", "disabled" );
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.__createPaymentMethodACHDirectDebit();
    });

    this.controllerInstance.paymentTriggerObject.show();
  },

  _mountAliPay : function( _attemptAutoAdvance = false ) {
    let objectInstance = this;

    this.controllerInstance.paymentTriggerObject.off( "click" ).on( "click", function() {
      if( typeof jQuery(this).attr( "disabled" ) !== 'undefined' )
        return;

      if( jQuery(this).find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      objectInstance.controllerInstance.paymentTriggerObject.parent().find( '[data-payment-page-notification]' ).remove();

      jQuery(this).attr( "disabled", "disabled" );
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.__createPaymentIntentOrSetup( '' );
    });

    this.controllerInstance.paymentTriggerObject.show();

    if( _attemptAutoAdvance && objectInstance.controllerInstance.canEnablePaymentTrigger() ) {
      this.controllerInstance.paymentTriggerObject.removeAttr( "disabled" ).trigger( "click" );
    }
  },

  _mountWeChat : function( _attemptAutoAdvance = false ) {
    let objectInstance = this;

    this.controllerInstance.paymentTriggerObject.off( "click" ).on( "click", function() {
      if( typeof jQuery(this).attr( "disabled" ) !== 'undefined' )
        return;

      if( jQuery(this).find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      objectInstance.controllerInstance.paymentTriggerObject.parent().find( '[data-payment-page-notification]' ).remove();

      jQuery(this).attr( "disabled", "disabled" );
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.__createPaymentIntentOrSetup( '' );
    });

    this.controllerInstance.paymentTriggerObject.show();

    if( _attemptAutoAdvance && objectInstance.controllerInstance.canEnablePaymentTrigger() ) {
      this.controllerInstance.paymentTriggerObject.removeAttr( "disabled" ).trigger( "click" );
    }
  },

  _mountPaymentRequest : function( handler, _attemptAutoAdvance = false ) {
    this.controllerInstance.paymentTriggerAlternativeContainerObject.show();

    PaymentPage.setLoadingContent( this.controllerInstance.paymentTriggerAlternativeContainerObject, '', 'mini' );

    if( this.paymentRequestObject === null )
      this.__syncPaymentRequestObject();

    let objectInstance = this;

    this.paymentRequestObject.canMakePayment().then(function(result) {
      if ( result
            && (
            ( handler === 'apple_pay' && result.applePay )
            || ( handler === 'google_pay' && result.googlePay )
            || ( handler === 'microsoft_pay' && !result.applePay && !result.googlePay )
          ) ) {
        objectInstance.controllerInstance.paymentTriggerAlternativeContainerObject.html(
          '<div data-payment-page-notification="info">' + objectInstance.controllerInstance.configuration.lang.payment_method_wallet_prerender + '</div>'
        );

        objectInstance._stripeElements = objectInstance._stripeSDK.elements();

        objectInstance.paymentRequestButtonObject = objectInstance._stripeElements.create('paymentRequestButton', {
          paymentRequest: objectInstance.paymentRequestObject,
        });

        objectInstance.paymentRequestButtonObject.mount( '#' + objectInstance.controllerInstance.paymentTriggerAlternativeContainerObject.attr( "id" ) );

        if( _attemptAutoAdvance && objectInstance.controllerInstance.canEnablePaymentTrigger() ) {
          objectInstance.paymentRequestButtonObject.show();
        }
      } else {
        objectInstance.controllerInstance.paymentTriggerAlternativeContainerObject.html(
          '<div data-payment-page-notification="warning">' + objectInstance.controllerInstance.configuration.lang.payment_method_wallet_incompatible + '</div>'
        );
      }
    });
  },

  __syncPaymentRequestObject : function() {
    if( this.paymentRequestObject === false )
      return;

    let checkout_information = this.controllerInstance.getCheckoutInformation(),
        objectInstance = this;

    if( this.paymentRequestObject === null ) {
      try {
        this.paymentRequestObject = this._stripeSDK.paymentRequest({
          country : this.controllerInstance.configuration.payment_gateways.stripe.country_code,
          currency: checkout_information.currency,
          total: {
            label  : checkout_information.title,
            amount : parseFloat( ( typeof checkout_information.price_setup !== "undefined" ? checkout_information.price_setup : checkout_information.price ) ) * 100,
          },
          requestPayerName: false,
          requestPayerEmail: false,
        });

        this.paymentRequestObject.on('paymentmethod', function(ev) {
          objectInstance.__createPaymentIntentOrSetup( ev.paymentMethod.id, ev );
        });
      } catch( e ) {
        this.paymentRequestObject = false;
      }
    } else {
      this.paymentRequestObject.update({
        currency: checkout_information.currency,
        total: {
          label  : checkout_information.title,
          amount : parseFloat( checkout_information.price ) * 100,
        },
      });
    }
  },

  __createPaymentMethodCreditCard : function() {
    let objectInstance = this,
        payment_method_args = {
          type : "card",
          card : this.cardElement,
          billing_details: {
            name: this.controllerInstance.container.find( '[name="first_name"]' ).val() + ' ' + this.controllerInstance.container.find( '[name="last_name"]' ).val()
          },
        };

    if( this.controllerInstance.container.find( '[name="card_zip_code"]' ).length > 0 ) {
      payment_method_args.billing_details.address = {
        postal_code : this.controllerInstance.container.find( '[name="card_zip_code"]' ).val()
      };
    }

    const stripePaymentMethod = this._stripeSDK.createPaymentMethod( payment_method_args).then( function( response ) {
      if( response.error ) {
        objectInstance.controllerInstance.afterPaymentFailed( response.error.message );

        return;
      }

      objectInstance.__createPaymentIntentOrSetup( response.paymentMethod.id );
    });
  },

  __createPaymentMethodSEPADebit : function() {
    let objectInstance = this;

    const stripePaymentMethod = this._stripeSDK.createPaymentMethod({
      type            : "sepa_debit",
      sepa_debit      : this.ibanElement,
      billing_details : {
        name  : this.controllerInstance.container.find( '[name="first_name"]' ).val() + ' ' + this.controllerInstance.container.find( '[name="last_name"]' ).val(),
        email : this.controllerInstance.container.find( '[name="email_address"]' ).val()
      },
    }).then( function( response ) {
      if( response.error ) {
        objectInstance.controllerInstance.afterPaymentFailed( response.error.message );

        return;
      }

      objectInstance.__createPaymentIntentOrSetup( response.paymentMethod.id );
    });
  },

  __createPaymentMethodACHDirectDebit : function() {
    let objectInstance     = this,
        productInformation = this.controllerInstance.getCheckoutInformation(),
          request_data     = this.controllerInstance.attachRestRequestCustomerDetails( {
            post_id         : this.controllerInstance.configuration.post_id,
            price_frequency : productInformation.frequency,
            price_currency  : productInformation.currency,
          } );

    PaymentPage.API.post('payment-page/v1/stripe/plaid-link-token', request_data, function( response ) {
      if( response.message ) {
        objectInstance.controllerInstance.afterPaymentFailed( response.message );
        return;
      }

      objectInstance.__createPaymentMethodACHDirectDebitPlaidLoaded( response );
    } );
  },

  __createPaymentMethodACHDirectDebitPlaidLoaded : function( link_token_data ) {
    let objectInstance = this;

    if( typeof window.Plaid === "undefined" ) {
      PaymentPage.LoadAssets( 'https://cdn.plaid.com/link/v2/stable/link-initialize.js', function() {
        objectInstance.__createPaymentMethodACHDirectDebitPlaidLoaded( link_token_data );
      }, false );

      return;
    }

    const configs = {
      // Pass the link_token generated in step 2.
      token: link_token_data.link_token,
      onSuccess: function(public_token, metadata) {
        if( metadata.accounts.length >= 2 ) {
          objectInstance.controllerInstance.afterPaymentFailed(
            'Set <a href="https://dashboard.plaid.com/link/account-select" target="_blank">Select Account ></a> to Enabled for one account in the Plaid developer dashboard.'
          );
          return;
        }

        if( metadata.accounts.length !== 1 ) {
          objectInstance.controllerInstance.afterPaymentFailed( '' );
          return;
        }

        let productInformation = objectInstance.controllerInstance.getCheckoutInformation();

        PaymentPage.API.post('payment-page/v1/stripe/plaid-link-confirm', objectInstance.controllerInstance.attachRestRequestCustomerDetails( {
          post_id               : objectInstance.controllerInstance.configuration.post_id,
          product_title         : productInformation.title,
          price_frequency       : productInformation.frequency,
          price_currency        : productInformation.currency,
          price_amount          : productInformation.price,
          setup_price_amount    : ( typeof productInformation.price_setup !== "undefined" ? productInformation.price_setup : null ),
          custom_fields         : objectInstance.controllerInstance.getCustomFieldsData(),
          plaid_public_token    : public_token,
          plaid_bank_account_id : metadata.accounts[0].id
        } ), function( response ) {
          if( response.message ) {
            objectInstance.controllerInstance.afterPaymentFailed( response.message );
            return;
          }

          objectInstance.controllerInstance.afterPaymentSuccess();
        } );
      },
      onExit: async function(err, metadata) {
        if (err != null) {
          objectInstance.controllerInstance.afterPaymentFailed( '' );
          return;
        }

        objectInstance.controllerInstance.afterPaymentFailed( false );
      },
    };

    let linkHandler = window.Plaid.create(configs);

    linkHandler.open();
  },

  __createPaymentIntentOrSetup : function( payment_method_id, ev ) {
    let objectInstance = this,
        productInformation = this.controllerInstance.getCheckoutInformation(),
        handler = this._getCurrentPaymentMethodHandlerString(),
        request_data = this.controllerInstance.attachRestRequestCustomerDetails({
          post_id            : this.controllerInstance.configuration.post_id,
          product_title      : productInformation.title,
          price_frequency    : productInformation.frequency,
          price_currency     : productInformation.currency,
          price_amount       : productInformation.price,
          setup_price_amount : ( typeof productInformation.price_setup !== "undefined" ? productInformation.price_setup : null ),
          stripe_payment_method_id : payment_method_id,
          payment_method    : handler,
          custom_fields     : this.controllerInstance.getCustomFieldsData()
        } );

    PaymentPage.API.post('payment-page/v1/stripe/payment-intent-or-setup', request_data, function( response ) {
      if( response.message ) {
        if( typeof ev !== 'undefined' && typeof ev.complete === 'function' )
          ev.complete('fail');

        objectInstance.controllerInstance.afterPaymentFailed( response.message );
        return;
      }

      if( handler === 'ccard' ) {
        objectInstance.__afterPaymentIntentOrSetupCreatedCCard( response, payment_method_id );
      } else if( handler === 'sepa' ) {
        objectInstance.__afterPaymentIntentOrSetupCreatedSEPA( response, payment_method_id );
      } else if( handler === 'alipay' ) {
        objectInstance.__afterPaymentIntentOrSetupCreatedAliPay( response );
      } else if( handler === 'wechat' ) {
        objectInstance.__afterPaymentIntentOrSetupCreatedWeChatPay( response );
      } else if( handler === 'apple_pay' || handler === 'google_pay' || handler === 'microsoft_pay' ) {
        objectInstance.__afterPaymentIntentOrSetupCreatedCCard( response, payment_method_id, ev );
      }
    } );
  },

  __afterPaymentIntentOrSetupCreatedCCard : function( response, payment_method_id, ev ) {
    let objectInstance = this;

    if( typeof response.payment_intent_id !== "undefined" ) {
      if( !response.payment_intent_requires_action ) {
        objectInstance.__checkout( payment_method_id, response.payment_intent_id, ev );
      } else {
        objectInstance._stripeSDK.confirmCardPayment(
          response.payment_intent_secret,
          {
            payment_method : payment_method_id
          }
        ).then( function( result ) {
          if (result.error) {
            if( typeof ev !== 'undefined' && typeof ev.complete === 'function' )
              ev.complete('fail');

            objectInstance.controllerInstance.afterPaymentFailed( result.error.message );
            return;
          }

          objectInstance.__checkout( payment_method_id, result.paymentIntent.id, ev );
        });
      }
    } else {
      objectInstance._stripeSDK.confirmCardSetup( response.setup_intent_secret, {
        payment_method: payment_method_id,
      }).then(function(result) {
        if (result.error) {
          if( typeof ev !== 'undefined' && typeof ev.complete === 'function' )
            ev.complete('fail');

          objectInstance.controllerInstance.afterPaymentFailed( result.error.message );
          return;
        }

        objectInstance.__checkout( payment_method_id, result.setupIntent.id, ev );
      });
    }
  },

  __afterPaymentIntentOrSetupCreatedSEPA : function( response, payment_method_id ) {
    let objectInstance = this;

    if( typeof response.payment_intent_id !== "undefined" ) {
      if( !response.payment_intent_requires_action ) {
        objectInstance.__checkout( payment_method_id, response.payment_intent_id );
      } else {
        objectInstance._stripeSDK.confirmSepaDebitPayment(
          response.payment_intent_secret,
          {
            payment_method : payment_method_id
          }
        ).then( function( result ) {
          if (result.error) {
            objectInstance.controllerInstance.afterPaymentFailed( result.error.message );
            return;
          }

          objectInstance.__checkout( payment_method_id, result.paymentIntent.id );
        });
      }
    } else {
      objectInstance._stripeSDK.confirmSepaDebitSetup( response.setup_intent_secret, {
        payment_method: payment_method_id,
      }).then(function(result) {
        if (result.error) {
          objectInstance.controllerInstance.afterPaymentFailed( result.error.message );
          return;
        }

        objectInstance.__checkout( payment_method_id, result.setupIntent.id );
      });
    }
  },

  __afterPaymentIntentOrSetupCreatedAliPay : function( response ) {
    if( typeof response.payment_intent_id === "undefined" )
      return;

    this._stripeSDK.confirmAlipayPayment(
      response.payment_intent_secret,
      {
        return_url: this.controllerInstance.attachPaymentInformationParams( window.location.href, false )
      }
    ).then(function(result) {
      if (result.error) {
        // Inform the customer that there was an error.
      }
    });
  },

  __afterPaymentIntentOrSetupCreatedWeChatPay : function( response ) {
    let objectInstance = this;

    if( typeof response.payment_intent_id === "undefined" )
      return;

    this._stripeSDK.confirmWechatPayPayment(
      response.payment_intent_secret,
      {
        payment_method_options: {
          wechat_pay: {
            client: 'web',
          },
        }
      },
    ).then(function( response ) {
      if( typeof response.paymentIntent === "undefined" || response.paymentIntent.status !== "succeeded" ) {
        objectInstance.controllerInstance.afterPaymentFailed( ( typeof response.error !== 'undefined' ? response.error.message : '' ) );
        return;
      }

      objectInstance.controllerInstance.afterPaymentSuccess();
    });
  },

  __checkout : function( payment_method_id, stripe_payment_intent_or_setup_id, ev ) {
    let objectInstance = this;

    let productInformation = this.controllerInstance.getCheckoutInformation(),
        request_data = this.controllerInstance.attachRestRequestCustomerDetails( {
          post_id                  : objectInstance.controllerInstance.configuration.post_id,
          product_title            : productInformation.title,
          price_frequency          : productInformation.frequency,
          price_currency           : productInformation.currency,
          price_amount             : productInformation.price,
          setup_price_amount       : ( typeof productInformation.price_setup !== "undefined" ? productInformation.price_setup : null ),
          stripe_payment_method_id : payment_method_id,
          stripe_payment_intent_or_setup_id : stripe_payment_intent_or_setup_id,
          custom_fields            : this.controllerInstance.getCustomFieldsData()
        } );

    this.controllerInstance.container.find( ':input' ).attr( "disabled", "disabled" );

    PaymentPage.API.post('payment-page/v1/stripe/checkout', request_data, function( response ) {
      if( typeof response.message !== 'undefined' ) {
        if( typeof ev !== 'undefined' && typeof ev.complete === 'function' )
          ev.complete('fail');
        
        objectInstance.controllerInstance.afterPaymentFailed( '' );

        return;
      }

      if( typeof ev !== 'undefined' && typeof ev.complete === 'function' )
        ev.complete('success');

      objectInstance.controllerInstance.afterPaymentSuccess();
    } );
  },

  __getStripeElementInputStyle : function() {
    return {
      base: {
        color   : payment_page_css_variable_value( this.controllerInstance.container, '--payment-page-element-form-input-color' ),
        fontSize: payment_page_css_variable_value( this.controllerInstance.container, '--payment-page-element-form-input-font-size' ),
        '::placeholder': {
          color   : payment_page_css_variable_value( this.controllerInstance.container, '--payment-page-element-form-input-placeholder-color' ),
          fontSize: payment_page_css_variable_value( this.controllerInstance.container, '--payment-page-element-form-input-placeholder-font-size' ),
        },
      },
    };
  },

};