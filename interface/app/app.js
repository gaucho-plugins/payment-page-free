// @codekit-prepend "utility/date.js"
// @codekit-prepend "utility/element.js"
// @codekit-prepend "utility/misc.js"
// @codekit-prepend "utility/string.js"

// @codekit-append "library/popup.js"

// @codekit-append "inc/api.js"
// @codekit-append "inc/hashtag.js"
// @codekit-append "inc/resource.js"
// @codekit-append "inc/template.js"

/**
 * @author Robert Rusu
 */
let PaymentPage = {

  Library : {},
  Component : {},

  _assets : {
    requested : [],
    loaded    : []
  },
  _components : {
    pending   : {},
    instanced : {}
  },
  _template_prefix : 'payment-page-',

  settings : {},
  lang : {},

  Init : function ( containerTarget ) {
    containerTarget.find( '[data-payment-page-component]' ).not( '[data-payment-page-component-loaded]' ).each( function() { PaymentPage.initComponent( jQuery(this) ); });

    containerTarget.find( '#payment-page-notification-container .notice-dismiss' ).on( "click", function() {
      PaymentPage.API.post( 'payment-page/v1/administration/dismiss-notification', false );
    });
  },

  initComponent : function( container ) {
    let component = container.attr( 'data-payment-page-component' );

    this.setLoadingContent( container, '', ( component === 'listing-search' ? 'mini' : '' ) );

    if( typeof PaymentPage.Component[ component ] !== "undefined" ) {
      PaymentPage._instanceComponent( component, container );
    } else {
      PaymentPage._loadComponent( component, container );
    }
  },

  getComponentInstance : function( component, component_index ) {
    component_index = parseInt( component_index );

    return PaymentPage._components.instanced[ component ][ component_index ];
  },

  setLoadingContent : function( container, loading_text = '', type = '' ) {
    if( container.find( ' > .payment-page-application-loader-wrapper' ).length === 1 ) {
        container.find( ' > .payment-page-application-loader-status-text' ).remove();

      if( loading_text !== '' )
        container.prepend( '<div class="payment-page-application-loader-status-text">' + loading_text + '</div>' );

      return;
    }

    container.html( payment_page_element_loader( type ) );

    if( loading_text !== '' )
      container.prepend( '<div class="payment-page-application-loader-status-text">' + loading_text + '</div>' );
  },

  setNoResultsContent : function( container ) {
    container.html( '<div data-payment-page-notification="info">' + this.lang.no_results_response + '</div>' );
  },

  setErrorContent : function( container, response ) {
    if( typeof response.error_html !== "undefined" ) {
      container.html( response.error_html );
      PaymentPage.Init( container );

      return;
    }

    let error = ( typeof response.error_notification !== "undefined" ? response.error_notification : response.error );

    container.html( '<div data-payment-page-notification="danger">' + error + '</div>' );
  },

  setCancelledContent : function( container ) {
    if( typeof window.closed !== "undefined" && window.closed ) {
      container.html( '' );
      return;
    }

    container.html( '<div data-payment-page-notification="info">' + this.lang.cancelled_request + '</div>' );
  },

  setFailedAssetFetchContent : function( container ) {
    if( typeof window.closed !== "undefined" && window.closed ) {
      container.html( '' );
      return;
    }

    container.html( '<div data-payment-page-notification="danger">' + this.lang.asset_failed_fetch + '</div>' );
  },

  setHTML : function( container, html ) {
    PaymentPage.Destroy( container );

    container.html( html );

    PaymentPage.Init( container );
  },

  _loadComponent : function( component, componentContainer = false ) {
    if( typeof this._components.pending[ component ] === "undefined" ) {
      if( componentContainer !== false )
        this._components.pending[ component ] = [ componentContainer ];

      this.Resource.loadCSS(
        this.getComponentAssetPath( component, 'style.css' ),
        {
          'payment-page-component-stylesheet' : component
        }
      )

      this.Resource.loadJS( this.getComponentAssetPath( component, 'controller.min.js' ), function() {
        PaymentPage._loadedComponent( component );
      }, function() {
        if( componentContainer !== false )
          PaymentPage.setFailedAssetFetchContent( componentContainer );
      } );

      return;
    }

    if( componentContainer === false )
      return;

    if( typeof this._components.pending[ component ] === "undefined" )
      this._components.pending[ component ] = [];

    this._components.pending[ component ][ this._components.pending[ component ].length ] = componentContainer;
  },

  _loadedComponent : function( component ) {
    if( typeof this._components.pending[ component ] === 'undefined' )
      return;

    if( this._components.pending[ component ].length !== 0 ) {
      jQuery.each( this._components.pending[ component ], function( index, componentContainer ) {
        PaymentPage._instanceComponent( component, componentContainer );
      });
    }

    delete this._components.pending[ component ];
  },

  _instanceComponent : function( component, componentContainer ) {
    if( typeof componentContainer.attr( "data-payment-page-component-loaded" ) !== "undefined" )
      return;

    componentContainer.attr( 'data-payment-page-component-loaded', 0 );

    this.setLoadingContent( componentContainer );

    if( typeof this._components.instanced[ component ] === "undefined" )
      this._components.instanced[ component ] = [];

    this._components.instanced[ component ][ this._components.instanced[ component ].length ] = payment_page_clone_object( this.Component[ component ] );

    componentContainer.attr( "data-payment-page-component-instance-index", this._components.instanced[ component ].length - 1 );

    this._components.instanced[ component ][ this._components.instanced[ component ].length - 1 ].Init( componentContainer );

    componentContainer.attr( 'data-payment-page-component-loaded', 1 );

    this.Hashtag.Init();
  },

  getComponentAssetPath : function( component, path ) {
    if( typeof this.settings.component_injection[ component ] !== "undefined" ) {
      let component_injection = this.settings.component_injection[ component ];

      if( typeof component_injection === 'object' ) {
        let objectKeys   = Object.keys( component_injection ),
          _matched_index = false;

        jQuery.each( objectKeys, function( _objectKeyIndex, _objectKey ) {
          if( path.indexOf( _objectKey ) !== 0 )
            return true;

          _matched_index = _objectKey;

          return false;
        });

        if( false !== _matched_index )
          return component_injection[ _matched_index ] + '/' + path.replace( _matched_index, '' );

        return component_injection[ '__default' ] + '/' + path;
      }

      return this.settings.component_injection[ component ] + '/' + path;
    }

    return this.settings.library_url + 'component/' + component + '/' + path;
  },

  LoadAssets : function( asset, callback = false, _attach_version = true ) {
    if( typeof asset === "string" ) {
      if( payment_page_in_array( asset, PaymentPage._assets.loaded ) ) {
        if( typeof callback === 'function' )
          callback();

        return;
      }

      if( payment_page_in_array( asset, PaymentPage._assets.requested ) ) {
        setTimeout( function() {
          PaymentPage.LoadAssets( asset, callback, _attach_version );
        }, 100 );

        return;
      }

      PaymentPage._assets.requested[ PaymentPage._assets.requested.length ] = asset;

      if( asset.endsWith( '.css' ) ) {
        PaymentPage.Resource.loadCSS( asset );

        if( typeof callback === 'function' )
          callback();

        return;
      }

      PaymentPage.Resource.loadJS( asset, callback, false, _attach_version );

      return;
    }

    let current_asset = _.head( asset );

    if( asset.length === 1 ) {
      this.LoadAssets( current_asset, callback );
      return;
    }

    let objectInstance   = this,
        remaining_assets = _.drop( asset, 1 );

    this.LoadAssets( current_asset, function() {
      objectInstance.LoadAssets( remaining_assets, callback );
    } );
  },

  isAssetLoaded : function( asset ) {
    if( typeof this._assets.loaded === "undefined" )
      return false;

    if( asset instanceof Array ) {
      let response = true;

      jQuery.each( asset, function( k, a ) {
        if( !PaymentPage._assets.loaded.includes( a ) )
          response = false;
      });

      return response;
    }

    return this._assets.loaded.includes( asset );
  },

  Destroy : function( target ) {
    if( typeof target.attr( 'data-payment-page-component' ) !== "undefined" )
      PaymentPage.__destroyComponent( target );

    if( typeof tinyMCE !== "undefined" ) {
      tinyMCE.triggerSave();

      target.find( ".wp-editor-area" ).each( function() {
        if( jQuery(this).attr( "id" ) !== "undefined" )
          tinyMCE.execCommand('mceRemoveEditor', false, jQuery(this).attr('id'));
      });
    }

    target.find( '[data-payment-page-component]' ).each( function() {
      PaymentPage.__destroyComponent( jQuery(this) );
    });

    target.find( '[data-payment-page-library]' ).each( function() {
      let library = jQuery(this).attr( 'data-payment-page-library' );

      library = payment_page_uc_first(library);

      if( typeof PaymentPage.Library[ library ] !== "undefined" ) {
        PaymentPage.Library[library].Destroy( jQuery(this) );
      }
    });

    target.find( "*" ).off();
    target.html( '' );
  },

  __destroyComponent : function( targetContainer ) {
    let component       = targetContainer.attr( "data-payment-page-component" ),
        component_index = targetContainer.attr( "data-payment-page-component-instance-index" );

    let _is_stored = ( typeof PaymentPage._components.instanced[ component ] !== 'undefined' && typeof PaymentPage._components.instanced[ component ][component_index] !== 'undefined' )

    if( _is_stored && typeof PaymentPage._components.instanced[ component ][component_index].__onDestroy === 'function' )
      PaymentPage._components.instanced[ component ][component_index].__onDestroy();

    targetContainer.removeAttr( "data-payment-page-component" );
    targetContainer.removeAttr( "data-payment-page-component-loaded" );
    targetContainer.removeAttr( "data-payment-page-component-instance-index" );

    if( _is_stored )
      PaymentPage._components.instanced[ component ].splice( component_index, 1 );
  },

  RemoveHTMLNode : function( target ) {
    this.Destroy( target );
    target.remove();
  }

};

function _payment_page_init_application( lang, configuration ) {
  PaymentPage.lang     = lang;
  PaymentPage.settings = payment_page_parse_args( configuration, PaymentPage.settings );

  PaymentPage.Init( jQuery( "body" ) );

  jQuery(window).trigger( "payment_page_ready" );
}

jQuery( document ).ready( function() {
  jQuery.extend(jQuery.expr[":"], {
    "containsCaseInsensitive": function (elem, i, match, array) {
      return (elem.textContent || elem.innerText || "").toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
    }
  });

  _payment_page_init_application( window.payment_page_data.lang, window.payment_page_data.configuration );
});
