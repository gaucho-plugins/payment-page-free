PaymentPage.Hashtag = {

  _registeredEvent : false,
  inTriggerLoop    : false,

  Init : function() {
    if( this._registeredEvent )
      return;

    jQuery(window).on('hashchange', function() {
      PaymentPage.Hashtag._eventTriggered();
    });

    this._registeredEvent = true;
  },

  _eventTriggered : function() {
    let _loopResponse  = true;

    this.inTriggerLoop = true;

    jQuery.each( PaymentPage._components.instanced, function( component_name, components ) {
      jQuery.each( components, function( component_index, component ) {
        if( typeof component.__onWindowHashChangeGlobal === 'function' ) {
          component.__onWindowHashChangeGlobal();
          return _loopResponse;
        }

        if( typeof component.__onWindowHashChange !== 'function'
            || typeof component.container.attr( 'data-payment-page-hashtag-identifier' ) === 'undefined'
            || typeof component.container === 'undefined' )
          return _loopResponse;

        let hash = payment_page_hashtag_container_from_browser( component.container );

        if( hash === component.container.attr( 'data-payment-page-hashtag-identifier' ) )
          return _loopResponse;

        component.__onWindowHashChange( hash );

        _loopResponse = false;

        return _loopResponse;
      });

      return _loopResponse;
    });

    this.inTriggerLoop = false;
  },

};