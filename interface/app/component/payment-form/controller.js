// @codekit-append "controller/paymentGateway/paypal.js"
// @codekit-append "controller/paymentGateway/skeleton.js"
// @codekit-append "controller/paymentGateway/stripe.js"

PaymentPage.Component[ 'payment-form' ] = {

  container     : {},
  configuration : {
    uniqid                : '',
    is_free_version       : 0,
    currency_symbol       : 0,
    subscription_selector : 0,
    currency_selector     : 0,
    post_id               : '',
    domain_name           : '',
    pricing_plans         : [],
    field_map             : {},
    submit_actions        : [],
    submit_trigger_label  : {
      token_order : [],
      token_map   : {
        customText1 : '',
        customText2 : '',
        customText3 : '',
      }
    },
    pricing_plan_option_label : {
      token_order : [],
      token_map   : {
        select_field_custom_text    : '',
        select_field_separator_text : ''
      }
    },
    lang            : {
      invalid_email_address  : '',
      pricing_plan_title     : '',
      payment_information    : '',
      payment_method         : '',
      pricing_custom_amount  : '',
      confirmation_page_title          : '',
      confirmation_page_message        : '',
      confirmation_page_item           : '',
      confirmation_page_customer_name  : '',
      confirmation_page_email          : '',
      confirmation_page_payment_date   : '',
      confirmation_page_payment_amount : '',
      notification_payment_failed      : '',
      payment_method_wallet_prerender       : '',
      payment_method_wallet_incompatible    : '',
      payment_method_one_time_only_tooltip  : '',
    },
    payment_gateways : {
      stripe : {
        publishable_key : '',
        payment_methods : []
      },
      paypal : {
        client_id       : '',
        payment_methods : []
      }
    },
    success_redirect_location : '',
    success_has_payment_details : 1,
    current_payment_gateway  : '',
    current_payment_method   : '',
    ignore_url_params        : 0,
    has_query_string_support : 0,
  },

  paymentGateway : {},

  _isCustomPricing : false,

  paymentTriggerObject : null,
  paymentTriggerAlternativeContainerObject : null,
  pricingOptions       : [],
  currencyOptionsAssoc   : {},
  frequencyOptionsAssoc  : {},
  currencyFrequencyOptions : {},
  fieldList              : [],

  currentPaymentGateway : '',
  currentPaymentMethod  : '',
  paymentMethodsMap     : {

  },

  _syncCurrentPaymentID : false,
  _syncCurrentPaymentSecret : false,
  _syncCurrentPaymentJSON : {},
  _syncCurrentPaymentXHR : false,
  _syncCurrentPaymentCallback : false,

  _hiddenFields : {},

  Init : function( container ) {
    this.container = container;

    let objectInstance = this;

    this.configuration.payment_gateways = {};

    payment_page_component_configuration_parse( this, function() {
      if( objectInstance.configuration.ignore_url_params === 0 ) {
        const urlParams = new URLSearchParams( window.location.search );

        if( urlParams.get( 'redirect_status' ) === 'succeeded' ) {
          objectInstance.afterPaymentSuccessURLCallback( urlParams );
          return;
        }
      }

      if (jQuery.fn.inputmask) {
        objectInstance._inputMaskReady();
      } else {
        PaymentPage.LoadAssets( PaymentPage.settings.libraries.inputmask, function() {
          objectInstance._inputMaskReady();
        });
      }
    } );
  },

  _inputMaskReady : function() {
    let objectInstance = this;

    jQuery.each( this.configuration.payment_gateways, function( _k, payment_gateway ) {
      objectInstance.paymentMethodsMap[ _k ] = {};

      jQuery.each( payment_gateway.payment_methods, function( _k_pm, payment_method ) {
        objectInstance.paymentMethodsMap[ _k ][ payment_method.id ] = payment_method;
      });
    });

    if( this.configuration.current_payment_gateway !== '' && this.configuration.current_payment_method !== '' ) {
      this.currentPaymentGateway = this.configuration.current_payment_gateway;
      this.currentPaymentMethod = this.configuration.current_payment_method;
    } else {
      this.currentPaymentGateway = Object.keys( this.configuration.payment_gateways )[ 0 ];

      this.currentPaymentMethod = this.configuration.payment_gateways[ this.currentPaymentGateway ].payment_methods[0].id;
    }

    PaymentPage.Template.preload( 'payment-form', [
      'template/payment-information.html'
    ], function() {
      objectInstance.__initSDKRecursive();
    } );
  },

  __initSDKRecursive : function() {
    let objectInstance = this;

    if( objectInstance.paymentGateway.stripe.controllerInstance === null
        && typeof this.configuration.payment_gateways.stripe !== 'undefined'
        && this.configuration.payment_gateways.stripe.publishable_key !== '' ) {
      objectInstance.paymentGateway.stripe._init( objectInstance, function() {
        objectInstance.__initSDKRecursive();
      } );

      return;
    }

    if( objectInstance.paymentGateway.paypal.controllerInstance === null
        && typeof this.configuration.payment_gateways.paypal !== 'undefined'
        && this.configuration.payment_gateways.paypal.client_id !== '' ) {
      objectInstance.paymentGateway.paypal._init( objectInstance, function() {
        objectInstance.__initSDKRecursive();
      } );

      return;
    }

    this._init();
  },

  _init : function() {
    let objectInstance = this,
        _attemptAutoAdvance = false;

    this.pricingOptions = [];
    this.currencyOptionsAssoc = {};
    this.frequencyOptionsAssoc = {};
    this.currencyFrequencyOptions = {};

    this.fieldList = [];

    jQuery.each( Object.values( this.configuration.field_map ), function( k, v ) {
      if( typeof v.order !== 'undefined' )
        v.order = parseInt( v.order );

      objectInstance.fieldList.push( v );
    });

    this.fieldList = _.sortBy( this.fieldList, 'order' );

    jQuery.each( this.configuration.pricing_plans, function( _k, pricing_plan ) {
      jQuery.each( pricing_plan.prices, function( _kp, price ) {
        let label = '';

        jQuery.each( objectInstance.configuration.pricing_plan_option_label.token_order, function( _ppol_k, label_token ) {
          label += ( label === '' ? '' : ' ' );

          if( label_token === "select_field_plan_name" ) {
            label += pricing_plan.plan_title;
          } else if( label_token === "select_field_plan_price" ) {
            label += price.price;
          } else if( label_token === "select_field_frequency" ) {
            label += price.frequency.label;
          } else if( label_token === "select_field_plan_price_currency" ) {
            if( price.price === '' )
              label += '';
            else
              label += ( objectInstance.configuration.currency_symbol
                          ? payment_page_get_currency_symbol( payment_page_get_user_locale(), price.currency )
                          : price.currency.toUpperCase()
            );
          } else if( label_token === "select_field_setup_price" ) {
            if( typeof price.setup_price !== "undefined"
                && typeof price.has_setup_price !== "undefined"
                && parseInt( price.has_setup_price ) )
              label += price.setup_price;
            else
              label += '';
          } else if( typeof objectInstance.configuration.pricing_plan_option_label.token_map[ label_token ] !== 'undefined' ) {
            label += objectInstance.configuration.pricing_plan_option_label.token_map[ label_token ];
          }
        });

        if( price.price === '' ) {
          if( typeof objectInstance.configuration.pricing_plan_option_label.token_map.select_field_separator_text !== 'undefined'
              && objectInstance.configuration.pricing_plan_option_label.token_map.select_field_separator_text.length === 1 )
            label = jQuery.trim( payment_page_trim( label, objectInstance.configuration.pricing_plan_option_label.token_map.select_field_separator_text ) );

          if( typeof objectInstance.configuration.pricing_plan_option_label.token_map.select_field_custom_text !== 'undefined'
              && objectInstance.configuration.pricing_plan_option_label.token_map.select_field_custom_text.length === 1 )
            label = jQuery.trim( payment_page_trim( label, objectInstance.configuration.pricing_plan_option_label.token_map.select_field_custom_text ) );

          if( typeof objectInstance.configuration.pricing_plan_option_label.token_map.select_field_separator_text !== 'undefined'
            && objectInstance.configuration.pricing_plan_option_label.token_map.select_field_separator_text.length === 1 )
            label = jQuery.trim( payment_page_trim( label, objectInstance.configuration.pricing_plan_option_label.token_map.select_field_separator_text ) );
        }

        if( typeof objectInstance.currencyFrequencyOptions[ price.currency ] === 'undefined' )
          objectInstance.currencyFrequencyOptions[ price.currency ] = [];

        if( !payment_page_in_array( price.frequency.value, objectInstance.currencyFrequencyOptions[ price.currency ] ) )
          objectInstance.currencyFrequencyOptions[ price.currency ].push( price.frequency.value );

        if( typeof objectInstance.frequencyOptionsAssoc[ price.frequency.value ] === 'undefined' )
          objectInstance.frequencyOptionsAssoc[ price.frequency.value ] = price.frequency.label;

        if( typeof objectInstance.currencyOptionsAssoc[ price.currency ] === 'undefined' )
          objectInstance.currencyOptionsAssoc[ price.currency ] = ( objectInstance.configuration.currency_symbol
              ? payment_page_get_currency_symbol( payment_page_get_user_locale(), price.currency )
              : price.currency.toUpperCase()
          );

        let current_option = {
          'label'     : label,
          'title'     : pricing_plan.plan_title,
          'currency'  : price.currency,
          'frequency' : price.frequency.value,
          'price'     : price.price
        };

        if( typeof price.setup_price !== "undefined"
            && typeof price.has_setup_price !== "undefined"
            && parseInt( price.has_setup_price ) )
          current_option.setup_price = price.setup_price;

        objectInstance.pricingOptions.push( current_option );
      });
    });

    let replace_args = {};

    if( this.configuration.has_query_string_support ) {
      let _usedParams = [];
      const urlParams = new URLSearchParams( window.location.search );

      jQuery.each( this.fieldList, function( _field_key, field ) {
        let param = urlParams.get( field.name );

        if( param === null ) {
          if( typeof field.label !== "undefined" ) {
            let alias = field.label.toLowerCase().replaceAll( " ", "_" );

            param = urlParams.get( alias );

            if( param !== null ) {
              _usedParams.push( alias );
            }
          }
        } else {
          _usedParams.push( field.name );
        }

        if( param !== null ) {
          if( typeof replace_args[ 'field_value_assoc' ] === "undefined" )
            replace_args[ 'field_value_assoc' ] = {};

          replace_args[ 'field_value_assoc' ][ field.name ] = param;
        }
      });

      if( urlParams.get( 'currency' ) !== null ) {
        replace_args[ 'selected_currency' ] = urlParams.get( "currency" );

        _usedParams.push( 'currency' );
      }

      if( urlParams.get( 'frequency' ) !== null ) {
        replace_args[ 'selected_frequency' ] = urlParams.get( "frequency" );

        _usedParams.push( 'frequency' );
      }

      if( urlParams.get( 'amount' ) !== null ) {
        replace_args[ 'custom_amount_value' ] = parseFloat( urlParams.get( "amount" ) );

        _usedParams.push( 'amount' );
      }

      if( urlParams.get( 'gateway' ) !== null && urlParams.get( 'method' ) !== null ) {
        objectInstance.currentPaymentGateway = urlParams.get( 'gateway' );
        objectInstance.currentPaymentMethod = urlParams.get( 'method' );

        _usedParams.push( 'gateway' );
        _usedParams.push( 'method' );
      }

      if( _usedParams.length >= 1 ) {
        let search = location.search.substring(1),
            all_params = JSON.parse('{"' + decodeURI(search).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}')

        jQuery.each( all_params, function( k, v ) {
          if( !payment_page_in_array( k, _usedParams ) )
            objectInstance._hiddenFields[ k ] = v;
        });

        if( _usedParams.length >= 3 )
          _attemptAutoAdvance = true;
      }
    }

    this._loadTemplate( replace_args, _attemptAutoAdvance );
  },

  _loadTemplate : function( replace_args, _attemptAutoAdvance = false ) {
    let objectInstance = this,
        template_args = payment_page_clone_object( this.configuration );

    template_args.fields_list = this.fieldList;
    template_args.payment_gateway = this.currentPaymentGateway;
    template_args.payment_method  = this.currentPaymentMethod;
    template_args.payment_method_handler = this.paymentMethodsMap[ this.currentPaymentGateway ][ this.currentPaymentMethod ].payment_method;

    template_args.selected_currency = Object.keys( this.currencyOptionsAssoc )[ 0 ];
    template_args.selected_frequency = ( this.configuration.subscription_selector ? Object.keys( this.frequencyOptionsAssoc )[ 0 ] : null );

    template_args.currency_options_assoc = this.currencyOptionsAssoc;
    template_args.frequency_options_assoc = this.frequencyOptionsAssoc;

    template_args.pricing_options_html = this.__pricingOptionsToHTML(
      ( this.configuration.currency_selector
        ? (
          typeof replace_args.selected_currency !== "undefined"
            ? replace_args.selected_currency
            : template_args.selected_currency
          )
        : null
      ),
      (
        typeof replace_args.selected_frequency !== "undefined"
          ? replace_args.selected_frequency
          : template_args.selected_frequency
      ),
      ( typeof replace_args.custom_amount_value !== "undefined"
          ? replace_args.custom_amount_value
          : null
      )
    );
    template_args.payment_methods_map = this.paymentMethodsMap;
    template_args.payment_methods_counts = 0;
    template_args.field_value_assoc = [];

    if( typeof replace_args === "object" && Object.keys( replace_args ).length > 0 )
      template_args = payment_page_parse_args( replace_args, template_args );

    jQuery.each( this.paymentMethodsMap, function( _k, _payment_methods ) {
      template_args.payment_methods_counts += Object.keys( _payment_methods ).length;
    });

    template_args.style = {
      pricing_arrow_color : getComputedStyle( this.container[0] ).getPropertyValue('--payment-page-element-form-select-arrow-color' ),
      filter_arrow_color : getComputedStyle( this.container[0] ).getPropertyValue('--payment-page-element-pricing-filter-select-arrow-color' )
    };

    template_args.payment_gateway_warning = this.getCurrentPaymentGatewayWarning();
    template_args.payment_method_disclaimer = this.getCurrentPaymentMethodDisclaimer();

    PaymentPage.Template.load( this.container, 'payment-form', 'template/default.html', template_args, function() {
      objectInstance.container.find( '[name="pp_custom_amount"]' ).inputmask( "decimal", {
        digits : 2,
        digitsOptional : false,
        groupSeparator : ','
      } );

      objectInstance.paymentTriggerObject = objectInstance.container.find( '[data-payment-page-component-payment-form-trigger="submit"]' );
      objectInstance.paymentTriggerAlternativeContainerObject = objectInstance.container.find( '[data-payment-page-component-payment-form-container="alternative_submit"]' );

      objectInstance.__refreshPaymentTriggerText();
      objectInstance.__refreshFilteringRestrictions();
      objectInstance._bindFiltering();
      objectInstance._bindFormFields();
      objectInstance._bindPaymentMethods();
      objectInstance.__refreshPaymentMethodRestrictions();
      objectInstance._maybeEnablePaymentTrigger();

      objectInstance.paymentGateway[ objectInstance.currentPaymentGateway ].mountPaymentMethod(
        objectInstance.currentPaymentMethod,
        _attemptAutoAdvance
      );
    });
  },

  __pricingOptionsToHTML : function( currency, frequency, amount = null ) {
    let response = '',
        _selected = ( frequency + "__" + currency + '__' + ( amount === null ? 0 : amount ) );

    jQuery.each( this.pricingOptions, function( _k, pricing_option ) {
      if( currency !== null && pricing_option.currency !== currency )
        return true;

      if( frequency !== null && pricing_option.frequency !== frequency )
        return true;

      let value = _.escape(
        pricing_option.frequency + "__" + pricing_option.currency + "__" + pricing_option.price +
        ( typeof pricing_option.setup_price !== 'undefined' ? '__' + pricing_option.setup_price : '' )
      );

      response += '<option data-payment-page-pricing-frequency="' + _.escape( pricing_option.frequency ) + '"' +
                         ' data-payment-page-pricing-currency="' + _.escape( pricing_option.currency ) + '"' +
                         ' data-payment-page-pricing-price="' + _.escape( pricing_option.price ) + '"';

      if( typeof pricing_option.setup_price !== 'undefined' )
        response += ' data-payment-page-pricing-setup-price="' + _.escape( pricing_option.setup_price ) + '"';

      response +=        ' data-payment-page-pricing-title="' + _.escape( pricing_option.title ) + '"' +
                         ' value="' + value + '"' +
                         ( _selected === value ? ' selected="selected"' : '' ) +
                         '>' + _.escape( pricing_option.label ) + '</option>'
    });

    return response;
  },

  getCheckoutInformation : function() {
    let optionObject = this.container.find( '[name="pp_pricing_options"] option[value="' + this.container.find( '[name="pp_pricing_options"]' ).val() + '"]' ),
        response = {
          title     : optionObject.attr( "data-payment-page-pricing-title" ),
          frequency : optionObject.attr( "data-payment-page-pricing-frequency" ),
          frequency_formatted : (
            typeof this.frequencyOptionsAssoc[ optionObject.attr( "data-payment-page-pricing-frequency" ) ] !== 'undefined'
              ? this.frequencyOptionsAssoc[ optionObject.attr( "data-payment-page-pricing-frequency" ) ]
              : ''
          ),
          currency  : optionObject.attr( "data-payment-page-pricing-currency" ),
          price     : ( this._isCustomPricing ? this.container.find( '[name="pp_custom_amount"]' ).inputmask( 'unmaskedvalue' ) : optionObject.attr( "data-payment-page-pricing-price" ) ),
          price_formatted : ( this._isCustomPricing ? this.container.find( '[name="pp_custom_amount"]' ).val() : optionObject.attr( "data-payment-page-pricing-price" ) )
        };

    if( typeof optionObject.attr( "data-payment-page-pricing-setup-price" ) !== 'undefined' )
      response.price_setup = optionObject.attr( "data-payment-page-pricing-setup-price" );

    return response;
  },

  __getFilterSelectedCurrency : function() {
    if( !this.configuration.currency_selector )
      return null;

    let container = this.container.find( '[data-payment-page-component-payment-form-section="pricing-filter-currency"]' );

    if( container.is( "select" ) )
      return container.val();

    return container.find( '[data-payment-page-component-payment-form-trigger^="filter_currency_"][data-payment-page-interaction-state="active"]' )
                    .attr( "data-payment-page-component-payment-form-trigger" )
                    .replace( "filter_currency_", "" );
  },

  __getFilterSelectedFrequency : function() {
    if( !this.configuration.subscription_selector )
      return null;

    let container = this.container.find( '[data-payment-page-component-payment-form-section="pricing-filter-frequency"]' );

    if( container.is( "select" ) )
      return container.val();

    return container.find( '[data-payment-page-component-payment-form-trigger^="filter_frequency_"][data-payment-page-interaction-state="active"]' )
                    .attr( "data-payment-page-component-payment-form-trigger" )
                    .replace( "filter_frequency_", "" );
  },

  __refreshPricingOptions : function() {
    let currency  = this.__getFilterSelectedCurrency(),
        frequency = this.__getFilterSelectedFrequency(),
        pricing_options_html = this.__pricingOptionsToHTML( currency, frequency );

    if( pricing_options_html === '' ) {
      let frequencyContainer = this.container.find( '[data-payment-page-component-payment-form-section="pricing-filter-frequency"]' );

      if( frequencyContainer.is( "select" ) ) {
        let _v = this.currencyFrequencyOptions[ currency ][ 0 ],
            _s = frequencyContainer.find( ' > option[value="' + _v + '"]' );

        frequencyContainer.find( ' > option' ).not( _s ).attr( 'data-payment-page-interaction-state', 'inactive' );

        _s.removeAttr( "disabled" ).attr( 'data-payment-page-interaction-state', 'active' );
        frequencyContainer.val( _v );
        frequencyContainer.trigger( "change" );
      } else {
        frequencyContainer.find( '[data-payment-page-component-payment-form-trigger="filter_frequency_' + ( this.currencyFrequencyOptions[ currency ][ 0 ] ) + '"]' )
                          .attr( 'data-payment-page-interaction-state', 'inactive' )
                          .trigger( "click" );
      }

      return;
    }

    this.container.find( '[name="pp_pricing_options"]' ).html( pricing_options_html ).trigger( "change" );

    this.__refreshFilteringRestrictions();
    this.syncPaymentAPI();
  },

  __refreshPaymentTriggerText : function() {
    let objectInstance = this,
        pricing_information = this.getCheckoutInformation(),
        label = '';

    jQuery.each( this.configuration.submit_trigger_label.token_order, function( _stl_k, label_token ) {
      label += ( label === '' ? '' : ' ' );

      if( label_token === "totalPrice" ) {
        label += pricing_information.price_formatted;
      } else if( label_token === "frequency" ) {
        label += pricing_information.frequency_formatted;
      } else if( label_token === "currency" ) {
        label += ( objectInstance.configuration.currency_symbol
            ? payment_page_get_currency_symbol( payment_page_get_user_locale(), pricing_information.currency )
            : pricing_information.currency.toUpperCase()
        );
      } else if( typeof objectInstance.configuration.submit_trigger_label.token_map[ label_token ] !== 'undefined' ) {
        label += objectInstance.configuration.submit_trigger_label.token_map[ label_token ];
      }
    });

    this.paymentTriggerObject.html( label );
  },

  _onPaymentTermsChange : function() {
    this.__refreshPaymentTriggerText();
    this.syncPaymentAPI();

    if( typeof this.paymentGateway[ this.currentPaymentGateway ].onPaymentTermsChange === 'function' )
      this.paymentGateway[ this.currentPaymentGateway ].onPaymentTermsChange( this.currentPaymentMethod );
  },

  _bindFiltering : function() {
    let objectInstance = this;

    this.container.find(
      '[data-payment-page-component-payment-form-section="pricing-filter-frequency"],' +
      '[data-payment-page-component-payment-form-section="pricing-filter-currency"]'
    ).on( "change", function() {
      let _s = jQuery(this).find( ' > option[value="' + jQuery(this).val() + '"]' );

      objectInstance.container.find( '> option' ).not( _s ).attr( 'data-payment-page-interaction-state', 'inactive' );
      _s.attr( 'data-payment-page-interaction-state', 'active' );

      objectInstance.__refreshPricingOptions();
    });

    this.container.find( '[data-payment-page-component-payment-form-trigger^="filter_frequency_"]' ).on( "click", function() {
      if( jQuery(this).attr( 'data-payment-page-interaction-state' ) !== 'inactive' )
        return;

      objectInstance.container.find( '[data-payment-page-component-payment-form-trigger^="filter_frequency_"]' ).not( jQuery(this) ).attr( 'data-payment-page-interaction-state', 'inactive' );
      jQuery(this).attr( 'data-payment-page-interaction-state', 'active' );

      objectInstance.__refreshPricingOptions();
    });

    this.container.find( '[data-payment-page-component-payment-form-trigger^="filter_currency_"]' ).on( "click", function() {
      if( jQuery(this).attr( 'data-payment-page-interaction-state' ) !== 'inactive' )
        return;

      objectInstance.container.find( '[data-payment-page-component-payment-form-trigger^="filter_currency_"]' ).not( jQuery(this) ).attr( 'data-payment-page-interaction-state', 'inactive' );
      jQuery(this).attr( 'data-payment-page-interaction-state', 'active' );

      objectInstance.__refreshPricingOptions();
    });
  },

  __refreshFilteringRestrictions : function() {
    if( !this.configuration.currency_selector || !this.configuration.subscription_selector )
      return;

    let objectInstance = this,
        pricingInformation = this.getCheckoutInformation(),
        frequencyContainer = this.container.find( '[data-payment-page-component-payment-form-section="pricing-filter-frequency"]' );

    frequencyContainer.find( '[data-payment-page-interaction-state]' ).not( '[data-payment-page-interaction-state="active"]' ).each( function() {
      let frequency = (
        typeof jQuery(this).attr( "data-payment-page-component-payment-form-trigger" ) !== 'undefined'
          ? jQuery(this).attr( "data-payment-page-component-payment-form-trigger" ).replace( "filter_frequency_", "" )
          : jQuery(this).attr( "value" )
      );

      if( payment_page_in_array( frequency, objectInstance.currencyFrequencyOptions[ pricingInformation.currency] ) ) {
        jQuery(this).attr( 'data-payment-page-interaction-state', 'inactive' )
                    .removeAttr( "disabled" );
      } else {
        jQuery(this).attr( 'data-payment-page-interaction-state', 'disabled' )
                    .attr( "disabled", "disabled" );
      }
    });
  },

  _bindFormFields : function() {
    let objectInstance = this;

    this.container.find( '[name="pp_pricing_options"]' ).off( "change.payment_page" ).on( "change.payment_page", function() {
      let custom_pricing_container = objectInstance.container.find( '[data-payment-page-component-payment-form-section="payment-custom-amount"]' );

      if( jQuery(this).find( ' > option[value="' + jQuery(this).val() + '"]').attr( 'data-payment-page-pricing-price' ) === '' ) {
        objectInstance._isCustomPricing = true;

        custom_pricing_container.find( '[data-payment-page-component-payment-form-section="field_container"] > span' ).html(
          payment_page_get_currency_symbol( payment_page_get_user_locale(), jQuery(this).find( ' > option[value="' + jQuery(this).val() + '"]').attr( 'data-payment-page-pricing-currency' ) )
        );

        payment_page_set_display_state_visible( custom_pricing_container );
      } else {
        objectInstance._isCustomPricing = false;

        payment_page_set_display_state_hidden( custom_pricing_container );
      }

      objectInstance._onPaymentTermsChange();
      objectInstance.__refreshPaymentMethodRestrictions();
    });

    this.container.find( '[name="pp_custom_amount"]' ).off( "keyup.payment_page change.payment_page" ).on( "keyup.payment_page change.payment_page", function() {
      objectInstance._onPaymentTermsChange();
    });

    if( this.container.find( '[name="pp_pricing_options"] > option:first' ).attr( 'data-payment-page-pricing-price' ) === '' )
      this.container.find( '[name="pp_pricing_options"]' ).trigger( "change" );

    this.container.find( 'input[name]' ).off( "focus.payment_page" ).on( "focus.payment_page", function() {
      jQuery(this).parents( '[data-payment-page-component-payment-form-section="field"]' ).attr( "data-payment-page-interaction-state", "focus" );
    });

    this.container.find( 'input[name]' ).off( "blur.payment_page" ).on( "blur.payment_page", function() {
      jQuery(this).parents( '[data-payment-page-component-payment-form-section="field"]' ).attr( "data-payment-page-interaction-state", ( jQuery(this).val() === '' ? "blur" : "not-empty" ) );
    });

    this.container.find( '[name="first_name"], [name="last_name"], [name="card_zip_code"], [name][required]' ).not( '[name="email_address"]' )
                  .off( "keyup.payment_page change.payment_page" )
                  .on( "keyup.payment_page change.payment_page", function() {
      objectInstance._maybeEnablePaymentTrigger();
    });

    this.container.find( '[name="email_address"]' ).off( "keyup.payment_page change.payment_page" ).on( "keyup.payment_page change.payment_page", function( event ) {
      let labelObject = objectInstance.container.find('[for="' + jQuery(this).attr("id") + '"]');

      if( jQuery(this).val() === '' ) {
        labelObject.attr( 'data-payment-page-interaction-state', 'pending' ).html( objectInstance.configuration.field_map.email_address.label );
      } else if( !payment_page_is_valid_email( jQuery(this).val() ) ) {
        labelObject.attr( 'data-payment-page-interaction-state', 'error' ).html( objectInstance.configuration.lang.invalid_email_address );
      } else {
        labelObject.attr( 'data-payment-page-interaction-state', 'good' ).html( objectInstance.configuration.field_map.email_address.label );
      }

      objectInstance._maybeEnablePaymentTrigger();
      
      if( event.type === 'change' )
        objectInstance.syncPaymentAPI();
    });
  },

  _bindPaymentMethods : function() {
    let objectInstance = this;

    this.container.find( '[data-payment-page-component-payment-form-trigger^="switch_payment_method_"]' ).off( "click" ).on( "click", function( event ) {
      event.preventDefault();

      if( objectInstance.paymentTriggerObject.find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      if( jQuery(this).attr( "data-payment-page-interaction-state" ) !== 'inactive' )
        return;

      objectInstance.__switchPaymentMethod(
        jQuery(this).attr( 'data-payment-page-payment-gateway' ),
        jQuery(this).attr( 'data-payment-page-component-payment-form-trigger' ).replace( 'switch_payment_method_', '' )
      );
    });
  },

  __refreshPaymentMethodRestrictions : function() {
    let objectInstance = this,
        pricingInformation = this.getCheckoutInformation(),
        switchTriggers = this.container.find( '[data-payment-page-component-payment-form-trigger^="switch_payment_method_"]' );

    if( pricingInformation.frequency === 'one-time' ) {
      switchTriggers.filter( '[data-payment-page-interaction-state="disabled"]' )
                    .attr( "data-payment-page-interaction-state", 'inactive' )
                    .removeAttr( 'data-payment-page-hint' )
                    .removeAttr( 'data-payment-page-hint-location' )
                    .removeAttr( 'aria-label' );
    } else {
      if( parseInt( switchTriggers.filter( '[data-payment-page-interaction-state="active"]' ).attr( "data-payment-page-has-recurring-support" ) ) !== 1 ) {
        let override = switchTriggers.filter( '[data-payment-page-has-recurring-support="1"]:first' );

        if( override.length === 0 ) {
          objectInstance.paymentTriggerObject.hide();
          objectInstance.paymentTriggerAlternativeContainerObject.html(
            '<div data-payment-page-notification="warning">No payment method activated for recurring pricing plans.</div>'
          ).show();

          return;
        }

        this.__paymentMethodRestrictionsTriggersLock( switchTriggers.filter( '[data-payment-page-has-recurring-support="0"]' ) );

        objectInstance.__switchPaymentMethod(
          override.attr( 'data-payment-page-payment-gateway' ),
          override.attr( 'data-payment-page-component-payment-form-trigger' ).replace( 'switch_payment_method_', '' )
        );
      } else {
        this.__paymentMethodRestrictionsTriggersLock( switchTriggers.filter( '[data-payment-page-has-recurring-support="0"]' ) );
      }
    }
  },

  __paymentMethodRestrictionsTriggersLock : function( targetTriggers ) {
    let objectInstance = this,
        per_row = payment_page_css_variable_value( this.container, '--payment-page-element-form-payment-method-per-row' );

    targetTriggers.attr( "data-payment-page-interaction-state", 'disabled' ).each( function() {
      let location = 'top',
          _temp = parseInt( jQuery(this).index() ) % per_row;

      if( _temp === 0 )
        location = 'right';
      else if( _temp === per_row - 1 )
        location = "left";

      jQuery(this).attr( 'data-payment-page-hint', 'info' )
                  .attr( 'data-payment-page-hint-location', location )
                  .attr( 'aria-label', objectInstance.configuration.lang.payment_method_one_time_only_tooltip );
    });
  },

  __switchPaymentMethod : function( payment_gateway, payment_method ) {
    if( payment_gateway === this.currentPaymentGateway
        && payment_method === this.currentPaymentMethod )
      return;

    if( typeof this.paymentGateway[ this.currentPaymentGateway ].unMountPaymentMethod === 'function' )
      this.paymentGateway[ this.currentPaymentGateway ].unMountPaymentMethod( this.currentPaymentMethod );

    this.currentPaymentGateway = payment_gateway;
    this.currentPaymentMethod = payment_method;

    let objectInstance = this,
        paymentInformationContainer = this.container.find( '[data-payment-page-component-payment-form-section="payment-information"]' );

    this.container.find( '> form > [data-payment-page-notification], > [data-payment-page-notification]' ).remove();

    this.container
        .find( '[data-payment-page-component-payment-form-trigger^="switch_payment_method_"][data-payment-page-interaction-state="active"]' )
        .attr( "data-payment-page-interaction-state", "inactive" );

    this.container
        .find( '[data-payment-page-component-payment-form-trigger="switch_payment_method_' + this.currentPaymentMethod + '"]' )
        .attr( "data-payment-page-interaction-state", "active" );

    this.paymentTriggerAlternativeContainerObject.removeAttr( 'data-payment-page-size' ).hide();
    this.paymentTriggerObject.hide();

    let template_args = payment_page_clone_object( this.configuration );

    template_args.payment_gateway = this.currentPaymentGateway;
    template_args.payment_gateway_warning = this.getCurrentPaymentGatewayWarning();
    template_args.payment_method  = this.currentPaymentMethod;
    template_args.payment_method_handler = this.paymentMethodsMap[ this.currentPaymentGateway ][ this.currentPaymentMethod ].payment_method;
    template_args.payment_method_disclaimer = this.getCurrentPaymentMethodDisclaimer();
    template_args.fields_list = this.fieldList;
    template_args.field_value_assoc = [];

    paymentInformationContainer.find( 'input[name]' ).each( function() {
      template_args.field_value_assoc[ jQuery(this).attr("name" ) ] = jQuery(this).val();
    });

    PaymentPage.Template.load(
      paymentInformationContainer,
      'payment-form',
      'template/payment-information.html',
      template_args,
      function() {
        objectInstance.paymentGateway[ objectInstance.currentPaymentGateway ].mountPaymentMethod( objectInstance.currentPaymentMethod );

        objectInstance._bindFormFields();
        objectInstance._maybeEnablePaymentTrigger();
      });
  },

  afterPaymentFailed : function( message = '' ) {
    this.paymentTriggerObject.removeAttr( "disabled" );
    this.paymentTriggerAlternativeContainerObject.removeAttr( "disabled" );

    this.container.find( ':input' ).removeAttr( "disabled" );
    this.__refreshPaymentTriggerText();

    if( message === false )
      return;

    if( typeof message === 'string' && message.length > 1 )
      this.paymentTriggerObject.after(
        '<div data-payment-page-notification="danger">' + message + '</div>'
      );
    else if( this.configuration.lang.notification_payment_failed !== '' )
      this.paymentTriggerObject.after(
        '<div data-payment-page-notification="danger">' + this.configuration.lang.notification_payment_failed + '</div>'
      );
  },

  afterPaymentSuccessURLCallback : function( urlParams ) {
    if( payment_page_in_array( "redirect_to", this.configuration.submit_actions )
      && this.configuration.success_redirect_location !== '' ) {
      let url = this.configuration.success_redirect_location;

      url += ( url.indexOf( "?" ) === -1 ? '?' : '&' ) + 'title=' + encodeURIComponent( this.configuration.lang.confirmation_page_title );
      url += '&message=' + encodeURIComponent( this.configuration.lang.confirmation_page_message );
      url += '&payment_date=' + encodeURIComponent( payment_page_format_timestamp_for_current_user( Math.floor(Date.now() / 1000) ) );
      url += '&item=' + urlParams.get( 'item' );
      url += '&currency=' + urlParams.get( 'currency' );
      url += '&payment_amount=' + urlParams.get( 'payment_amount' );
      url += '&customer_name=' + urlParams.get( 'customer_name' );
      url += '&email_address=' + urlParams.get( 'email_address' );

      window.location = url;
      return;
    }

    if( payment_page_in_array( "dynamic_message", this.configuration.submit_actions ) ) {
      this.__paymentSuccessMessageDynamic(
        decodeURIComponent( urlParams.get( 'item' ) ),
        decodeURIComponent( urlParams.get( 'payment_amount' ) ),
        decodeURIComponent( urlParams.get( 'currency' ) ),
        decodeURIComponent( urlParams.get( 'customer_name' ) ),
        decodeURIComponent( urlParams.get( 'email_address' ) ),
        decodeURIComponent( urlParams.get( 'payment_gateway' ) ),
        decodeURIComponent( urlParams.get( 'payment_method_handler' ) )
      );
      return;
    }

    urlParams.delete( 'item' );
    urlParams.delete( 'payment_amount' );
    urlParams.delete( 'currency' );
    urlParams.delete( 'customer_name' );
    urlParams.delete( 'email_address' );
    urlParams.delete( 'payment_method_handler' );
    urlParams.delete( 'redirect_status' );
    urlParams.delete( 'payment_intent' );
    urlParams.delete( 'payment_intent_client_secret' );

    let url_params = urlParams.toString();

    window.location.href = '?refresh=1' + ( url_params === '' ? '' : '&' + url_params );
  },

  afterPaymentSuccess : function() {
    if( payment_page_in_array( "redirect_to", this.configuration.submit_actions )
        && this.configuration.success_redirect_location !== '' ) {
      window.location = this.attachPaymentInformationParams( this.configuration.success_redirect_location, true );
      return;
    }

    let productInformation = this.getCheckoutInformation();

    if( payment_page_in_array( "dynamic_message", this.configuration.submit_actions ) ) {
      this.__paymentSuccessMessageDynamic(
        productInformation.title,
        ( typeof productInformation.price_setup !== 'undefined' ? productInformation.price_setup : productInformation.price ),
        productInformation.currency,
        this.container.find( '[name="first_name"]' ).val() + ' ' + this.container.find( '[name="last_name"]' ).val(),
        this.container.find( '[name="email_address"]' ).val(),
        this.currentPaymentGateway,
        this.paymentGateway[ this.currentPaymentGateway ]._getCurrentPaymentMethodHandlerString()
      );
      return;
    }

    window.location.reload();
  },

  __paymentSuccessMessageDynamic : function( title, price, currency, customer_name, email_address, payment_gateway, payment_method_handler ) {
    let objectInstance = this,
        template_args = {
      lang           : this.configuration.lang,
      title          : this.configuration.lang.confirmation_page_title,
      message        : this.configuration.lang.confirmation_page_message,
      item           : title,
      payment_date   : payment_page_format_timestamp_for_current_user( Math.floor(Date.now() / 1000) ),
      currency       : currency,
      payment_amount : price,
      customer_name  : customer_name,
      email_address  : email_address,
      has_payment_details : this.configuration.success_has_payment_details,
      payment_method_handler : payment_method_handler
    };

    PaymentPage.Template.load( this.container, 'payment-form', 'template/success.html', template_args, function() {
      objectInstance.container[ 0 ].scrollIntoView( { behavior: "smooth", block: "center", inline: "nearest" } );
    });
  },

  canEnablePaymentTrigger : function() {
    let is_allowed = this.paymentGateway[ this.currentPaymentGateway ].maybeEnablePaymentTrigger();

    if( is_allowed ) {
      if( this.container.find( '[name="first_name"]' ).val() === ''
        || this.container.find( '[name="last_name"]' ).val() === ''
        || this.container.find( '[name="email_address"]' ).val() === ''
        || !payment_page_is_valid_email( this.container.find( '[name="email_address"]' ).val() ) ) {
        is_allowed = false;
      } else {
        this.container.find( 'input[name][required]' ).each( function() {
          if( jQuery(this).val() === '' )
            is_allowed = false;
        });
      }
    }

    return is_allowed;
  },

  _maybeEnablePaymentTrigger : function() {
    if( !this.canEnablePaymentTrigger() ) {
      this.paymentTriggerObject.attr( "disabled", "disabled" );
      this.paymentTriggerAlternativeContainerObject.attr( "disabled", "disabled" );
    } else {
      this.paymentTriggerObject.removeAttr( "disabled" );
      this.paymentTriggerAlternativeContainerObject.removeAttr( "disabled" );
    }
  },

  attachPaymentInformationParams : function( url, all_params ) {
    let productInformation = this.getCheckoutInformation();

    if( all_params ) {
      url += ( url.indexOf( "?" ) === -1 ? '?' : '&' ) + 'title=' + encodeURIComponent( this.configuration.lang.confirmation_page_title );
      url += '&message=' + encodeURIComponent( this.configuration.lang.confirmation_page_message );
      url += '&payment_date=' + encodeURIComponent( payment_page_format_timestamp_for_current_user( Math.floor(Date.now() / 1000) ) );
    }

    url += ( url.indexOf( "?" ) === -1 ? '?' : '&' ) + 'item=' + encodeURIComponent( productInformation.title );
    url += '&currency=' + encodeURIComponent( productInformation.currency );
    url += '&payment_amount=' + encodeURIComponent( productInformation.price );
    url += '&customer_name=' + encodeURIComponent( this.container.find( '[name="first_name"]' ).val() + ' ' + this.container.find( '[name="last_name"]' ).val() );
    url += '&email_address=' + encodeURIComponent( this.container.find( '[name="email_address"]' ).val() );
    url += '&payment_gateway=' + encodeURIComponent( this.currentPaymentGateway );
    url += '&payment_method_handler=' + encodeURIComponent( this.paymentGateway[ this.currentPaymentGateway ]._getCurrentPaymentMethodHandlerString() );

    return url;
  },

  getCurrentPaymentMethodDisclaimer : function() {
    return ( typeof this.paymentMethodsMap[ this.currentPaymentGateway ][ this.currentPaymentMethod ].disclaimer !== 'undefined' ? this.paymentMethodsMap[ this.currentPaymentGateway ][ this.currentPaymentMethod ].disclaimer : '' );
  },

  getCurrentPaymentGatewayWarning : function() {
    return (
      typeof this.configuration.payment_gateways[ this.currentPaymentGateway ].warning !== 'undefined'
        ? this.configuration.payment_gateways[ this.currentPaymentGateway ].warning
        : ''
    );
  },

  getCustomFieldsData : function() {
    let response = {};

    this.container.find( 'input[name^="custom_"]' ).each( function() {
      response[ jQuery(this).parent().find( '> label' ).text() ] = jQuery(this).val();
    });

    if( typeof this._hiddenFields === "object" && Object.keys( this._hiddenFields ).length > 0 ) {
      jQuery.each( this._hiddenFields, function( _key, _value ) {
        response[ _key ] = _value;
      });
    }

    return response;
  },

  attachRestRequestCustomerDetails : function( data ) {
    data.first_name = this.container.find( '[name="first_name"]' ).val();
    data.last_name = this.container.find( '[name="last_name"]' ).val();
    data.email_address = this.container.find( '[name="email_address"]' ).val();

    return data;
  },

  syncPaymentAPI : function( callback ) {
    let syncJSON = payment_page_parse_args( {
      post_id         : this.configuration.post_id,
      payment_gateway : this.currentPaymentGateway,
      payment_method  : this.currentPaymentMethod,
      custom_fields   : this.getCustomFieldsData()
    }, this.getCheckoutInformation() );

    delete syncJSON.frequency_formatted;
    delete syncJSON.price_formatted;
    delete syncJSON.title;

    syncJSON = this.attachRestRequestCustomerDetails( syncJSON );

    this._syncCurrentPaymentCallback = callback;

    if( JSON.stringify( syncJSON ) === JSON.stringify( this._syncCurrentPaymentJSON ) )
      return;

    if( this._syncCurrentPaymentXHR !== false ) {
      if( this._syncCurrentPaymentID !== false ) {
        this._syncCurrentPaymentXHR.abort();
      } else {
        // Chance it might not sync latest information, but, with the current flow, it's low, requests should take > 10 seconds for it to be a problem, even then, might not be.
        return;
      }
    }

    this._syncCurrentPaymentJSON = syncJSON;
    this._syncCurrentPaymentXHR = callback;

    if( this._syncCurrentPaymentID !== false )
      syncJSON._current_id = this._syncCurrentPaymentID;

    if( this._syncCurrentPaymentSecret !== false )
      syncJSON._current_secret = this._syncCurrentPaymentSecret;

    let objectInstance = this;

    this._syncCurrentPaymentXHR = PaymentPage.API.post('payment-page/v1/payment/sync-details', syncJSON, function( response ) {
      objectInstance._syncCurrentPaymentID = response.id;
      objectInstance._syncCurrentPaymentSecret = response.secret;

      if( typeof objectInstance._syncCurrentPaymentCallback === 'function' ) {
        objectInstance._syncCurrentPaymentCallback();
        objectInstance._syncCurrentPaymentCallback = false;
      }

      objectInstance._syncCurrentPaymentXHR = false;
    } );
  }

};