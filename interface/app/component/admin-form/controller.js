PaymentPage.Component[ 'admin-form' ] = {

  container     : {},
  configuration : {
    title       : '',
    description : '',
    fields      : {},
    operations  : {},
    rest_data   : {},
    rest_path   : '',
  },

  Init : function( container ) {
    this.container = container;

    let objectInstance = this;

    payment_page_component_configuration_parse( this, function() {
      objectInstance.loadTemplate();
    } );
  },

  loadTemplate : function() {
    let objectInstance = this;

    PaymentPage.Template.load( this.container, 'admin-form', 'template/default.html', {
      title       : this.configuration.title,
      description : this.configuration.description,
      fields      :  _.sortBy( Object.values( this.configuration.fields ), 'order' ),
      operations  :  _.sortBy( Object.values( this.configuration.operations ), 'order' )
    }, function() {
      objectInstance.container.find( " > form" ).on( "submit", function( event ) {
        event.preventDefault();
        event.stopImmediatePropagation();

        jQuery(this).find( 'input[type="submit"]' ).parent().html( payment_page_element_loader( 'mini' ) );

        let request_data = payment_page_clone_object( objectInstance.configuration.rest_data );

        jQuery(this).find( ':input[name]' ).not( '[type="submit"]' ).each( function() {
          request_data[ jQuery(this).attr( "name" ) ] = jQuery(this).val();
        });

        PaymentPage.API.post( objectInstance.configuration.rest_path, request_data, function() {
          objectInstance.container.trigger( "payment_page_settings_saved" );
        });
      });
    });
  }

};