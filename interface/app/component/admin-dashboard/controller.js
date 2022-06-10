PaymentPage.Component[ 'admin-dashboard' ] = {

  container     : {},
  configuration : {},
  data : {},

  _currentlyDisplayed : '',
  _resumeQuickSetup : false,
  _skipQuickSetup : false,

  _xhrSetPaymentMethodStatusMAP : {},

  Init : function( container ) {
    this.container = container;

    let objectInstance = this;

    payment_page_component_configuration_parse( this, function() {
      PaymentPage.Template.preload( 'admin-dashboard', [
        'template/_navigation.html',
        'template/_payment-gateways.html',
        'template/_templates.html'
      ], function() {
        objectInstance._loadData();
      } )
    } );
  },

  _loadData : function() {
    let objectInstance = this;

    PaymentPage.setLoadingContent( this.container );

    PaymentPage.API.fetch('payment-page/v1/administration/dashboard', false, function( response ) {
      if( typeof response !== 'object' ) {
        PaymentPage.setFailedAssetFetchContent( objectInstance.container );
        return;
      }

      objectInstance.data = response;

      objectInstance._loadTemplate();
    } );
  },

  _getTemplateArgs : function() {
    let objectInstance = this,
        response = payment_page_parse_args( this.data, this.configuration );

    response.current_page = payment_page_hashtag_container_from_browser( this.container );

    if( response.current_page !== 'payment-gateways'
        && response.current_page !== 'templates' )
      response.current_page = 'templates';

    response.quick_setup_index = false;
    response.quick_setup_return_index = false;
    response.quick_setup_skip_index = false;

    jQuery.each( response.quick_setup_steps, function( step_key, step ) {
      if( step.is_completed && !objectInstance._resumeQuickSetup )
        return true;

      if( response.quick_setup_index !== false ) {
        if( typeof step.requires_steps === 'undefined' ) {
          response.quick_setup_skip_index = step_key;

          return false;
        }

        if( objectInstance._resumeQuickSetup )
          return true;

        let good = true;

        jQuery.each( step.requires_steps, function( req_step_key, req_step ) {
          if( response.quick_setup_steps[ req_step ].is_completed )
            return true;

          good = false;
          return false;
        });

        if( good ) {
          response.quick_setup_skip_index = step_key;

          return false;
        }

        return true;
      }

      response.quick_setup_index = step_key;
    });

    if( this._skipQuickSetup !== false ) {
      response.quick_setup_return_index = response.quick_setup_index;
      response.quick_setup_index = this._skipQuickSetup;

      response.quick_setup_skip_index = false;
    }

    // Allow back in the process from Templates step ( non true setup process, but desired )
    if( response.current_page === 'templates' && response.quick_setup_return_index === false && response.quick_setup_skip_index === false )
      response.quick_setup_return_index = 0;

    if( response.quick_setup_index !== false && !this.data.quick_setup_skipped ) {
      response.current_page = response.quick_setup_steps[ response.quick_setup_index ].template;

      if( response.current_page !== payment_page_hashtag_container_from_browser( this.container ) ) {
        this.container.attr( 'data-payment-page-hashtag-identifier', response.current_page );
        payment_page_hashtag_container_to_browser(this.container);
      }
    }

    return response;
  },

  _loadTemplate : function() {
    let objectInstance = this,
        template_args = this._getTemplateArgs();

    this._currentlyDisplayed = template_args.current_page;

    this.container.attr( "data-payment-page-hashtag-identifier", this._currentlyDisplayed );

    PaymentPage.Template.load( this.container, 'admin-dashboard', 'template/default.html', template_args, function() {
      objectInstance._bindQuickSetupEvents();
      objectInstance._bindTemplateEvents();
      objectInstance._bindPaymentEvents();

      let _cookie = payment_page_get_cookie( 'payment_page_dashboard_open_gateway' );

      if( objectInstance.configuration.payment_gateway.stripe.mode_live_configured
          || objectInstance.configuration.payment_gateway.stripe.mode_test_configured ) {
        if( _cookie === null || typeof _cookie === 'undefined' )
          _cookie = 'stripe';
      }

      if( _cookie !== 'none' )
        objectInstance.container
                      .find( '[data-payment-page-gateway-alias="' + _cookie + '"] > [data-payment-page-component-admin-dashboard-trigger="payment_gateway_expand"]' )
                      .trigger( "click" );
    });
  },

  _bindQuickSetupEvents : function() {
    let objectInstance = this;

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger="quick-setup"]' ).on( "click", function() {
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.data.quick_setup_skipped = 0;
      objectInstance._resumeQuickSetup = true;
      objectInstance._loadTemplate();
    } );

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger="quick-setup-exit"]' ).on( "click", function() {
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.data.quick_setup_skipped = 1;

      PaymentPage.API.post('payment-page/v1/administration/set-quick-setup-skip', { 'status' : 1 }, function( response ) {
        objectInstance.data = response;

        objectInstance._loadTemplate();
      } );
    } );

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger^="quick_setup_skip_"]' ).on( "click", function() {
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance._skipQuickSetup = parseInt( jQuery(this).attr( 'data-payment-page-component-admin-dashboard-trigger' ).replace( "quick_setup_skip_", "" ) );
      objectInstance._loadTemplate();
    });

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger="quick_setup_return"]' ).on( "click", function() {
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance._skipQuickSetup = false;
      objectInstance._loadTemplate();
    });
  },

  _bindTemplateEvents : function() {
    let objectInstance = this;

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger="install_plugin_elementor"]' ).on( "click", function() {
      if( typeof jQuery(this).attr( "disabled" ) !== 'undefined' )
        return;

      if( jQuery(this).find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.container.find( '[data-payment-page-component-admin-dashboard-trigger="install_plugin_elementor"]' ).not( jQuery(this) ).attr( "disabled", "disabled" );

      let parentContainer = jQuery(this).parents( '[data-payment-page-component-admin-dashboard-section="template_information"]:first' );

      parentContainer.find( '[data-payment-page-notification]' ).remove();

      PaymentPage.API.post('payment-page/v1/plugin/install', { 'identifier' : 'elementor' }, function( response ) {
        if( typeof response.message !== 'undefined' ) {
          parentContainer.append( '<div data-payment-page-notification="danger">' + response.message + '</div>' );

          return;
        }

        PaymentPage.API.post('payment-page/v1/plugin/activate', { 'identifier' : 'elementor' }, function( response ) {
          objectInstance._loadData();
        } );
      } );
    } );

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger="activate_plugin_elementor"]' ).on( "click", function() {
      if( typeof jQuery(this).attr( "disabled" ) !== 'undefined' )
        return;

      if( jQuery(this).find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      let parentContainer = jQuery(this).parents( '[data-payment-page-component-admin-dashboard-section="template_information"]:first' );

      parentContainer.find( '[data-payment-page-notification]' ).remove();

      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.container.find( '[data-payment-page-component-admin-dashboard-trigger="activate_plugin_elementor"]' ).not( jQuery(this) ).attr( "disabled", "disabled" );

      PaymentPage.API.post('payment-page/v1/plugin/activate', { 'identifier' : 'elementor' }, function( response ) {
        if( typeof response.message !== 'undefined' ) {
          parentContainer.append( '<div data-payment-page-notification="danger">' + response.message + '</div>' );

          return;
        }

        objectInstance._loadData();
      } );
    } );

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger^="select_template_"]' ).on( "click", function() {
      if( typeof jQuery(this).attr( "disabled" ) !== 'undefined' )
        return;

      if( jQuery(this).find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      let parentContainer = jQuery(this).parents( '[data-payment-page-component-admin-dashboard-section="template_information"]:first' );

      parentContainer.find( '[data-payment-page-notification]' ).remove();

      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      objectInstance.container.find( '[data-payment-page-component-admin-dashboard-trigger^="select_template_"]' ).not( jQuery(this) ).attr( "disabled", "disabled" );

      PaymentPage.API.post('payment-page/v1/administration/import-template', {
        'id' : parseInt( jQuery(this).attr( 'data-payment-page-component-admin-dashboard-trigger' ).replace( "select_template_", "" ) )
      }, function( response ) {
        if( typeof response.message !== 'undefined' )
          parentContainer.append( '<div data-payment-page-notification="' + ( typeof response.code !== 'undefined' ? 'danger' : 'success' ) + '">' + response.message + '</div>' );

        objectInstance.container.find( '[data-payment-page-component-admin-dashboard-trigger^="select_template_"]' ).removeAttr( "disabled" ).html( objectInstance.data.lang.template_select );
      } );
    } );
  },

  _bindPaymentEvents : function() {
    let objectInstance = this;

    this.container.find(
      '[data-payment-page-gateway-alias] > [data-payment-page-component-admin-dashboard-trigger="payment_gateway_hide"],' +
      '[data-payment-page-gateway-alias] > [data-payment-page-component-admin-dashboard-trigger="payment_gateway_expand"]'
    ).on( "click", function() {
      let payment_gateway_container = jQuery(this).parents( '[data-payment-page-gateway-alias]' ),
          payment_method_container = payment_gateway_container.find( '[data-payment-page-component-admin-dashboard-section="payment_methods_container"]' );

      if( parseInt( payment_gateway_container.attr( 'data-payment-page-has-payment-methods-visible' ) ) ) {
        payment_gateway_container.attr( 'data-payment-page-has-payment-methods-visible', 0 );
        payment_method_container.slideUp( "slow" );
        payment_page_set_cookie( 'payment_page_dashboard_open_gateway', 'none', 60 * 24 * 30 );
      } else {
        payment_gateway_container.attr( 'data-payment-page-has-payment-methods-visible', 1 );
        payment_method_container.slideDown( "slow" );
        payment_page_set_cookie( 'payment_page_dashboard_open_gateway', payment_gateway_container.attr( "data-payment-page-gateway-alias" ), 60 * 24 * 30 );
      }

    });

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger="gateway_mode_checkbox"]' ).on( "change", function() {
      let mode = jQuery(this).is( ":checked" ) ? 'test' : 'live';

      jQuery(this).parents( '[data-payment-page-gateway-mode]' ).attr( 'data-payment-page-gateway-mode', mode );

      objectInstance.data.payment_gateway.stripe.mode = mode;

      PaymentPage.API.post('payment-page/v1/payment-gateway/set-mode', {
        'payment_gateway' : jQuery(this).parents( '[data-payment-page-gateway-alias]:first' ).attr( "data-payment-page-gateway-alias" ),
        'is_live' : ( jQuery(this).is( ":checked" ) ? 0 : 1 )
      }, function( response ) {

      } );
    });

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger^="payment_method_"]' ).on( "change", function() {
      let gateway_container = jQuery(this).parents( '[data-payment-page-gateway-alias]:first' ),
          gateway = gateway_container.attr( "data-payment-page-gateway-alias" ),
          payment_method = jQuery(this).attr( 'data-payment-page-component-admin-dashboard-trigger' ).replace( "payment_method_", "" ),
          table_td_container = jQuery(this).parents( 'td:first' );

      if( jQuery(this).is( ":checked" ) ) {
        objectInstance.data.payment_gateway[ gateway ].payment_methods_enabled.push( payment_method );
      } else {
        objectInstance.data.payment_gateway[ gateway ].payment_methods_enabled = objectInstance.data.payment_gateway.stripe.payment_methods_enabled.filter(function(value, index, arr){
          return value !== payment_method;
        });
      }

      table_td_container.find( ' > label' ).hide();
      table_td_container.append( payment_page_element_loader( 'mini' ) );

      if( typeof objectInstance._xhrSetPaymentMethodStatusMAP[ gateway ] !== 'undefined'
          && objectInstance._xhrSetPaymentMethodStatusMAP[ gateway ] !== false )
        objectInstance._xhrSetPaymentMethodStatusMAP[ gateway ].abort();

      objectInstance._xhrSetPaymentMethodStatusMAP[ gateway ] = PaymentPage.API.post('payment-page/v1/payment-gateway/set-payment-methods', {
        'payment_gateway' : gateway,
        'payment_methods' : objectInstance.data.payment_gateway[ gateway ].payment_methods_enabled,
      }, function( response ) {
        if( typeof response !== 'object' || response.status !== 'ok' )
          return;

        if( typeof response.refresh !== 'undefined' && parseInt( response.refresh ) )
          objectInstance._loadData();

        objectInstance._xhrSetPaymentMethodStatusMAP[ gateway ] = false;

        objectInstance.container.find( '[data-payment-page-component-admin-dashboard-trigger^="payment_method_"]' ).each( function() {
          let current_table_container = jQuery(this).parents( 'td:first' );

          if( current_table_container.find( ' > label' ).is( ":hidden" ) ) {
            current_table_container.find( '.payment-page-application-loader-wrapper' ).remove();
            current_table_container.find( ' > label' ).show();
          }
        });
      } );
    });

    this.container.find(
      '[data-payment-page-component-admin-dashboard-trigger^="gateway_connect_"],' +
      '[data-payment-page-component-admin-dashboard-trigger^="gateway_settings_"]'
    ).on( "click", function() {
      if( jQuery(this).find( '.payment-page-application-loader-wrapper' ).length > 0 )
        return;

      if( parseInt( PaymentPage.settings.is_https ) ) {
        if( window.location.href.indexOf( PaymentPage.settings.site_url ) === -1 ) {
          PaymentPage.Library.Popup.display(
            '<div data-payment-page-notification="danger">' +
                      objectInstance.data.lang.notification_url_mismatch_ssl.replace( "%s", jQuery(this).parents( '[data-payment-page-gateway-alias]:first' ).find( '> [data-payment-page-component-admin-dashboard-section="header"]:first > h2' ).text() ) +
                    '</div>'
          );
          return;
        }
      }

      let invalid_url_characters = [];

      jQuery.each( objectInstance.data.invalid_url_characters, function( _key, invalid_character ) {
        if( PaymentPage.settings.site_url.indexOf( invalid_character ) !== -1 )
          invalid_url_characters.push( invalid_character );
      } );

      if( invalid_url_characters.length > 0 ) {
        PaymentPage.Library.Popup.display(
          '<div data-payment-page-notification="danger">' +
                    objectInstance.data.lang.notification_url_invalid_characters
                        .replace( "%s", '<strong>' + invalid_url_characters.join( " " ) + '</strong>' )
                        .replace( "%s", jQuery(this).parents( '[data-payment-page-gateway-alias]:first' ).find( '> [data-payment-page-component-admin-dashboard-section="header"]:first > h2' ).text() ) +
                  '</div>'
        );
        return;
      }

      let triggerObject = jQuery(this),
          paymentGatewayContainer = jQuery(this).parents( '[data-payment-page-gateway-alias]:first' ),
          is_live = (
        jQuery(this).attr( "data-payment-page-component-admin-dashboard-trigger" )
                    .replace( 'gateway_connect_', '' )
                    .replace( 'gateway_settings_', '' ) === 'live'
          ? 1
          : 0
      ),
          payment_gateway = paymentGatewayContainer.attr( "data-payment-page-gateway-alias" ),
          _button_inner_html = jQuery(this).html();

      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      PaymentPage.API.fetch('payment-page/v1/payment-gateway/connect', {
        payment_gateway : payment_gateway,
        is_live         : is_live
      }, function( response ) {
        if( response.type === 'redirect' ) {
          window.location = response.url;
          return;
        }

        if( response.type === "settings" ) {
          let title = paymentGatewayContainer.find( ' > [data-payment-page-component-admin-dashboard-section="header"] > h2 ' ).text() + ' ' +
                      '<span data-payment-page-mode="' + ( is_live ? 'live' : 'test' ) + '">' +
                        ( is_live ? objectInstance.data.lang.payment_gateway_mode_live : objectInstance.data.lang.payment_gateway_mode_test  ) +
                      '</span>';

          title = objectInstance.data.lang.payment_gateway_settings_title.replace( "%s", title );

          triggerObject.html( _button_inner_html );

          PaymentPage.Library.Popup.display(
            '<div data-payment-page-component="admin-form" ' +
                         'data-payment-page-component-args="' + _.escape( JSON.stringify( {
                                                                                            title       : title,
                                                                                            description : response.description,
                                                                                            fields      : response.fields,
                                                                                            operations  : response.operations,
                                                                                            rest_data   : {
                                                                                              payment_gateway : payment_gateway,
                                                                                              is_live         : is_live
                                                                                            },
                                                                                            rest_path   : 'payment-page/v1/payment-gateway/save-settings',
                                                                                          } ) ) + '"></div>',
            {
              trigger_app_init : true
            }
          );

          PaymentPage.Library.Popup.getContainerObject()
                                   .find( '[data-payment-page-component="admin-form"]' )
                                   .on( 'payment_page_settings_saved', function() {
                                     PaymentPage.Library.Popup.close();
                                     objectInstance._loadData();
                                   });
        }
      } );
    });

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger^="gateway_disconnect_"]' ).on( "click", function() {
      PaymentPage.setLoadingContent( jQuery(this), '', 'mini' );

      PaymentPage.API.post('payment-page/v1/payment-gateway/disconnect', {
        'payment_gateway' : jQuery(this).parents( '[data-payment-page-gateway-alias]:first' ).attr( "data-payment-page-gateway-alias" ),
        'is_live' : ( jQuery(this).attr( "data-payment-page-component-admin-dashboard-trigger" ).replace( 'gateway_disconnect_', '' ) === 'live' ? 1 : 0 )
      }, function( response ) {
        objectInstance._loadData();
      } );
    });

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger^="payment_method-settings_"]' ).on( "click", function() {
      let triggerObject = jQuery(this),
          gateway_container = jQuery(this).parents( '[data-payment-page-gateway-alias]:first' ),
          payment_gateway = gateway_container.attr( "data-payment-page-gateway-alias" ),
          args  = JSON.parse( triggerObject.attr( "data-payment-page-component-admin-dashboard-args" ) ),
          _temp = triggerObject.attr( "data-payment-page-component-admin-dashboard-trigger" ).replace( 'payment_method-settings_', '' ),
          is_live = ( _temp.substr( 0, 4 ) === 'live' ? 1 : 0 ),
          payment_method = _temp.replace( ( is_live ? "live_" : "test_" ), "" );

      PaymentPage.Library.Popup.display(
        '<div data-payment-page-component="admin-form" ' +
                     'data-payment-page-component-args="' + _.escape( JSON.stringify( {
                        title       : args.title,
                        description : args.description,
                        fields      : args.fields,
                        operations  : {
                          save : {
                            label : objectInstance.data.lang.payment_method_settings_save,
                            type  : 'save',
                            order : 1,
                          }
                        },
                        rest_data   : {
                          payment_gateway : payment_gateway,
                          payment_method  : payment_method,
                          is_live         : is_live
                        },
                        rest_path   : 'payment-page/v1/payment-gateway/save-payment-method-settings',
                      } ) ) + '"></div>',
        {
          trigger_app_init : true
        }
      );

      PaymentPage.Library.Popup.getContainerObject()
                               .find( '[data-payment-page-component="admin-form"]' )
                               .on( 'payment_page_settings_saved', function() {
                                   PaymentPage.Library.Popup.close();
                                   objectInstance._loadData();
                               });
    });

    this.container.find( '[data-payment-page-component-admin-dashboard-trigger^="payment_gateway_webhook_settings_"]' ).on( "click", function() {
      let triggerObject = jQuery(this),
        gateway_container = jQuery(this).parents( '[data-payment-page-gateway-alias]:first' ),
        payment_gateway = gateway_container.attr( "data-payment-page-gateway-alias" ),
        args  = JSON.parse( triggerObject.attr( "data-payment-page-component-admin-dashboard-args" ) ),
        _temp = triggerObject.attr( "data-payment-page-component-admin-dashboard-trigger" ).replace( 'payment_gateway_webhook_settings_', '' ),
        is_live = ( _temp.substr( 0, 4 ) === 'live' ? 1 : 0 );

      PaymentPage.Library.Popup.display(
        '<div data-payment-page-component="admin-form" ' +
                     'data-payment-page-component-args="' + _.escape( JSON.stringify( {
                        title       : args.title,
                        description : args.description,
                        fields      : args.fields,
                        operations  : {
                          save : {
                            label : objectInstance.data.lang.payment_gateway_webhook_settings_save,
                            type  : 'save',
                            order : 1,
                          }
                        },
                        rest_data   : {
                          payment_gateway : payment_gateway,
                          is_live         : is_live
                        },
                        rest_path   : 'payment-page/v1/payment-gateway/save-webhook-settings',
                      } ) ) + '"></div>',
        {
          trigger_app_init : true
        }
      );

      PaymentPage.Library.Popup.getContainerObject()
        .find( '[data-payment-page-component="admin-form"]' )
        .on( 'payment_page_settings_saved', function() {
          PaymentPage.Library.Popup.close();
          objectInstance._loadData();
        });
    });
  },

  __onWindowHashChange : function( hash ) {
    if( parseInt( this.data.quick_setup_skipped ) === 0 )
      PaymentPage.API.post('payment-page/v1/administration/set-quick-setup-skip', { 'status' : 1 }, function( response ) {} );

    if( hash === this._currentlyDisplayed )
      return;

    this._loadTemplate();
  }

};