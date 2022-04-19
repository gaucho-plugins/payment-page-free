PaymentPage.Resource = {

  loadCSS : function( href, _args ) {
    let args = ( typeof _args === "object" ? _args : {} );

    args.rel  = 'stylesheet';
    args.type = 'text/css';
    args.href = href + '?version=' + PaymentPage.settings.file_version;

    jQuery( '<link/>', args ).appendTo('head');

    PaymentPage._assets.loaded[ PaymentPage._assets.loaded.length ] = href;
  },

  loadJS : function( path, callback, callback_error, _attach_version = true ) {
    let getScript = jQuery.ajax({
      type    : "GET",
      url     : path + ( _attach_version ? '?version=' + PaymentPage.settings.file_version : '' ),
      success : function() {
        PaymentPage._assets.loaded[ PaymentPage._assets.loaded.length ] = path;

        if( typeof callback === 'function' )
          callback();
      },
      dataType : "script",
      cache    : true
    });

    if( typeof callback_error === 'function' )
      getScript.fail( callback_error );
  },

  getTemplateFile : function( path, callback ) {
    jQuery.ajax({
      type     : "GET",
      url      : path + '?version=' + PaymentPage.settings.file_version,
      success  : callback,
      dataType : "html",
      cache    : true
    });
  }

};