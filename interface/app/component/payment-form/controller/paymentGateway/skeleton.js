PaymentPage.Component[ 'payment-form' ].paymentGateway.skeleton = {

  controllerInstance : null,

  _init : function( controllerInstance, callback ) {
    this.controllerInstance = controllerInstance;

    callback();
  },

  _getCurrentPaymentMethodHandlerString : function() {
    return 'skeleton';
  },

  _getPaymentMethodHandlerString : function( payment_method ) {
    return 'Demo Preview';
  },

  mountPaymentMethod : function( payment_method, _attemptAutoAdvance = false ) {

  },

  unMountPaymentMethod : function() {

  },

  onPaymentTermsChange : function( payment_method ) {

  },

  maybeEnablePaymentTrigger : function() {
    return true;
  },

  __hideFormFields : function() {
    this.controllerInstance.container.find( '[data-payment-page-component-payment-form-section="field_wrapper"]' ).slideUp( "slow" );
  },

  __displayFormFields : function() {
    this.controllerInstance.container.find( '[data-payment-page-component-payment-form-section="field_wrapper"]' ).slideDown( "slow" );
  },

};