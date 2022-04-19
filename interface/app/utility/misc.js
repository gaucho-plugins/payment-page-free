function payment_page_parse_args( args, default_args ) {
  if( typeof args === "string" )
    args = JSON.parse( args );

  return jQuery.extend( true, default_args, args );
}

function payment_page_css_variable_value( container, property ) {
  return getComputedStyle( container[ 0 ] ).getPropertyValue(property );
}

function payment_page_get_user_locale() {
  return navigator.userLanguage || (navigator.languages && navigator.languages.length && navigator.languages[0]) || navigator.language || navigator.browserLanguage || navigator.systemLanguage || 'en';
}

function payment_page_is_valid_email(email) {
  return email.match(
    /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
  );
}

/**
 * @param redirect_link
 */
function payment_page_redirect( redirect_link ) {
  window.location = redirect_link;
}

function ec_scroll_to( object ) {
  object = ( typeof object === 'string' ? jQuery( object ) : object );

  object[ 0 ].scrollIntoView( { behavior: "smooth", block: "center", inline: "nearest" } );
}

function payment_page_is_in_viewport(el) {
  if ( el instanceof jQuery)
    el = el[0];

  let rect = el.getBoundingClientRect();

  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
}


function payment_page_in_array( value, array ) {
  return jQuery.inArray( value, array  ) !== -1;
}

function payment_page_clone_object( object ) {
  return jQuery.extend( true, {}, object );
}

function payment_page_clone_array( array ) {
  return jQuery.merge( [], array );
}

function payment_page_is_mobile() {
  return /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
}

function payment_page_is_display_state_visible( target ) {
  if( typeof target.attr( 'data-payment-page-display-state' ) === "undefined" )
    return true;

  return target.attr( 'data-payment-page-display-state' ) === "visible";
}

function payment_page_set_display_state_visible( target ) {
  target.attr( 'data-payment-page-display-state', 'visible' );
}

function payment_page_set_display_state_hidden( target ) {
  target.attr( 'data-payment-page-display-state', 'hidden' );
}

function payment_page_hashtag_container_from_browser_data_object( container, default_data ) {
  let hashtag_temp = payment_page_hashtag_container_from_browser( container ),
      hashtag_data = ( typeof default_data !== "object" || default_data === null ? {} : payment_page_clone_object( default_data ) );

  hashtag_temp = hashtag_temp.split( ';' );

  jQuery.each( hashtag_temp, function( key, hashtag_token ) {
    let token_split = hashtag_token.split( ':' );

    hashtag_data[ token_split[ 0 ] ] = decodeURIComponent( token_split[ 1 ] );
  });

  return hashtag_data;
}

function payment_page_get_currency_symbol(locale, currency) {
  let response = (0).toLocaleString(
    locale,
    {
      style: 'currency',
      currency: currency.toUpperCase(),
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
      currencyDisplay: 'symbol',
    }
  ).replace(/\d/g, '').trim();

  return ( response.length > 1 ? response[ response.length - 1 ] : response );
}


function payment_page_hashtag_container_from_browser( target ) {
  if( window.location.hash.length === 0 )
    return '';

  let hash_tokens  = window.location.hash.replace( '#', '' ).split( '&' ),
      parent_count = ( typeof target.attr( 'data-payment-page-hashtag-parent-container' ) !== "undefined"
        ? jQuery( target.attr( 'data-payment-page-hashtag-parent-container' ) )
        : target
      ).parents( '[data-payment-page-hashtag-identifier]' ).length;

  if( typeof hash_tokens[ parent_count ] === "undefined" )
    return '';

  return hash_tokens[ parent_count ];
}

function payment_page_hashtag_data_to_string( data_object ) {
  let container_hashtag = '';

  jQuery.each( data_object, function( _k, _v ) {
    if( _v !== '' && _v !== null && typeof _v !== 'undefined' )
      container_hashtag += ( container_hashtag === '' ? '' : ';' ) + _k + ':' + encodeURIComponent( _v );
  });

  return container_hashtag;
}

function payment_page_hashtag_container_sync_to_browser( container, data_object ) {
  container.attr( 'data-payment-page-hashtag-identifier', payment_page_hashtag_data_to_string( data_object ) );

  payment_page_hashtag_container_to_browser( container );
}

function payment_page_hashtag_container_to_browser( target ) {
  // Hard reject non-components.
  if( typeof target.attr( 'data-payment-page-component' ) === 'undefined' )
    return;

  let hash = ( typeof target.attr( 'data-payment-page-hashtag-identifier' ) !== "undefined" ? target.attr( 'data-payment-page-hashtag-identifier' ) : '' );

  ( typeof target.attr( 'data-payment-page-hashtag-parent-container' ) !== "undefined"
      ? jQuery( target.attr( 'data-payment-page-hashtag-parent-container' ) )
      : target
  ).parents( '[data-payment-page-hashtag-identifier]' ).each( function() {
    hash = jQuery(this).attr( 'data-payment-page-hashtag-identifier' ) + ( hash === '' ? '' : '&' ) + hash;
  });

  if( window.location.hash === '#' + hash || ( window.location.hash === '' && hash === '' ) )
    return;

  if( PaymentPage.Hashtag.inTriggerLoop )
    return;
  
  window.location.hash = hash;
}

function payment_page_get_cookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
}

function payment_page_set_cookie(name,value,minutes) {
  let expires = "";

  if (minutes) {
    let date = new Date();
    date.setTime(date.getTime()+(minutes * 60 * 1000));

    expires = "; expires="+date.toGMTString();
  }

  document.cookie = name+"="+value+expires+"; path=/";
}

function payment_page_country_code_to_flag( country_code ) {
  return country_code.toUpperCase().replace(/./g, char => String.fromCodePoint(char.charCodeAt(0)+127397) );
}

function payment_page_browser_lang() {
  if ( typeof navigator.languages !== undefined )
    return navigator.languages[0];
  else
    return navigator.language;
}

function payment_page_format_currency( number, currency ) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: currency }).format( number );
}

function payment_page_format_percent( number, fraction_digits, max = null ) {
  number = parseFloat( number );

  let options = {
    style: 'percent',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  };

  if( max !== null && number >= max ) {
    number = max;

    options.minimumFractionDigits = 0;
    options.maximumFractionDigits = 0;
  }

  let formatter = new Intl.NumberFormat( payment_page_browser_lang(), options );

  return formatter.format(number / 100 );
}

function payment_page_component_configuration_parse( componentInstance, callback, is_custom_parse = false ) {
  if( typeof componentInstance.container.attr( 'data-payment-page-component-args' ) !== "undefined" ) {
    let configuration = JSON.parse( componentInstance.container.attr( 'data-payment-page-component-args' ) );

    componentInstance.container.removeAttr( 'data-payment-page-component-args' );

    __payment_page_component_configuration_parse_set( componentInstance, configuration, callback, is_custom_parse );
  } else if( typeof componentInstance.container.attr( 'data-payment-page-component-action' ) !== "undefined" ) {
    PaymentPage.Request.post( componentInstance.container.attr( 'data-payment-page-component-action' ), {}, function(response) {
      if( response.status !== 'ok' ) {
        PaymentPage.setErrorContent( componentInstance.container, response );

        return;
      }

      delete response.status;

      componentInstance.container.removeAttr( 'data-payment-page-component-action' );

      __payment_page_component_configuration_parse_set( componentInstance, response, callback, is_custom_parse );
    });
  } else {
    if( typeof callback === 'function')
      callback();
    else if( typeof callback === 'string' )
      componentInstance[ callback ]();
  }
}

function __payment_page_component_configuration_parse_set( componentInstance, configuration, callback, is_custom_parse ) {
  if( is_custom_parse ) {
    if( typeof callback === 'function')
      callback( configuration );
    else if( typeof callback === 'string' )
      componentInstance[ callback ]( configuration );

    return;
  }

  componentInstance.configuration = payment_page_parse_args( configuration, componentInstance.configuration );

  if( typeof callback === 'function')
    callback();
  else if( typeof callback === 'string' )
    componentInstance[ callback ]();
}