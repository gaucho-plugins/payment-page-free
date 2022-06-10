let PaymentPageElementor_FormFields = {

  elementorInstance : null,
  container : {},
  repeaterContainer : {},
  groupTemplateContainer : {},

  Init : function( elementorInstance ) {
    this.elementorInstance = elementorInstance;
    this.container = jQuery( elementorInstance.el );
    this.repeaterContainer = this.container.find( '.fields-repeater-container' );
    this.groupTemplateContainer = this.repeaterContainer.find( '.field-group-template' );

    this.groupTemplateContainer.hide();

    let objectInstance = this,
        control_value = elementorInstance.getControlValue(),
        ordered_fields = [];

    jQuery.each( Object.values( control_value ), function( k, v ) {
      if( typeof v.order !== 'undefined' )
        v.order = parseInt( v.order );

      ordered_fields.push( v );
    });

    console.log( control_value );

    ordered_fields = _.sortBy( ordered_fields, 'order' );

    jQuery.each( ordered_fields, function( _k, field_data ) {
      objectInstance._attachFieldRow( field_data );
    });

    objectInstance.__bindFieldOrdering();

    this.container.find( '.add-more-options' ).off( "click" ).on( "click", function( event ) {
      event.preventDefault();

      objectInstance._attachFieldRow( {
        name : "custom_field_" + Date.now(),
        type : "custom",
        size : 6,
        size_mobile : 0,
        order : objectInstance.repeaterContainer.find( '.field-group' ).length
      } );
      objectInstance.__bindFieldOrdering();
      objectInstance.syncToElementor();
    });
  },

  _attachFieldRow : function( row_data ) {
    this.groupTemplateContainer.clone().appendTo( this.repeaterContainer );

    let objectInstance = this,
        fieldRowContainer = this.repeaterContainer.find( '.field-group-template:last' );

    fieldRowContainer.removeClass( 'field-group-template' ).show();

    if( typeof row_data.name !== 'undefined' )
      fieldRowContainer.find( '[data-setting="key"]' ).val( row_data.name );

    if( typeof row_data.type !== 'undefined' ) {
      fieldRowContainer.find( '[data-setting="type"]' ).val( row_data.type );

      if( jQuery.inArray( row_data.type, [ 'payment_method_card', 'payment_method_iban'] ) === -1 ) {
        if( typeof row_data.placeholder !== 'undefined' )
          fieldRowContainer.find( '[data-setting="placeholder"]' ).val( row_data.placeholder );
      } else {
        fieldRowContainer.find( '[data-setting-container="placeholder"]' ).remove();
      }

      if( row_data.type === "custom" ) {
        if( typeof row_data.is_required !== 'undefined' && parseInt( row_data.is_required ) ) {
          fieldRowContainer.find( '[data-setting="is_required"]' ).prop("checked",1 );
        }
      } else {
        fieldRowContainer.find( '.remove-field-container' ).hide();
        fieldRowContainer.find( '[data-setting-container="is_required"]' ).remove();
      }
    }

    // Possible browser cache issue, need to prevent it.
    if( typeof row_data.is_hidden === "undefined" && ( typeof row_data.name === 'undefined' || row_data.name !== 'card_zip_code' ) ) {
      fieldRowContainer.find( '[data-setting-container="is_hidden"]' ).remove();
    } else {
      if( typeof row_data.is_hidden !== 'undefined' && parseInt( row_data.is_hidden ) ) {
        fieldRowContainer.find( '[data-setting="is_hidden"]' ).prop("checked",1 );
      }
    }

    if( typeof row_data.label !== 'undefined' )
      fieldRowContainer.find( '[data-setting="label"]' ).val( row_data.label );

    if( typeof row_data.size !== 'undefined' )
      fieldRowContainer.find( '[data-setting="size"]' ).val( row_data.size );

    if( typeof row_data.size_mobile !== 'undefined' )
      fieldRowContainer.find( '[data-setting="size_mobile"]' ).val( row_data.size_mobile );

    fieldRowContainer.find( '[data-setting]' ).on( "keyup change", function( event ) {
      objectInstance.syncToElementor();
    });

    fieldRowContainer.find( '.remove-field' ).off( "click" ).on( "click", function() {
      let _fieldRow = jQuery(this).parents( '.field-group:first' );

      _fieldRow.remove();

      objectInstance.syncToElementor();
    });
  },

  __bindFieldOrdering : function() {
    let objectInstance = this,
        sortableContainers = this.repeaterContainer.find( '.field-group' ).not( '.field-group-template' );

    if( this.repeaterContainer.data( 'sortable' ) )
      this.repeaterContainer.sortable("destroy");

    this.repeaterContainer.removeClass( "ui-sortable" );

    this.repeaterContainer.sortable( {
      handle      : "> .payment-page-draggable-container > .payment-page-draggable",
      axis        : 'y',
      opacity     : 0.7,
      tolerance   : 'pointer',
      revert      : true,
      stop: function(e,ui) {
        sortableContainers.each( function() {
          jQuery(this).find( '[data-setting="order"]' ).val( jQuery(this).index() );
        });

        objectInstance.syncToElementor();
      }
    } );
  },

  syncToElementor : function() {
    let value = {};

    this.repeaterContainer.find( '.field-group' ).not( '.field-group-template' ).each( function() {
      let key = jQuery(this).find( '[data-setting="key"]' ).val(),
          type = jQuery(this).find( '[data-setting="type"]' ).val(),
          order = jQuery(this).find( '[data-setting="order"]' ).val(),
          label = jQuery(this).find( '[data-setting="label"]' ).val(),
          size = jQuery(this).find( '[data-setting="size"]' ).val(),
          size_mobile = jQuery(this).find( '[data-setting="size_mobile"]' ).val(),
          isHiddenInput = jQuery(this).find( '[data-setting="is_hidden"]' ),
          isRequiredInput = jQuery(this).find( '[data-setting="is_required"]' ),
          placeholderInput = jQuery(this).find( '[data-setting="placeholder"]' );

      value[ key ] = {
        label       : label,
        name        : key,
        placeholder : ( placeholderInput.length > 0 ? placeholderInput.val() : '' ),
        is_required : ( isRequiredInput.length > 0 ? ( isRequiredInput.is( ':checked' ) ? 1 : 0 ) : 1 ),
        type        : type,
        size        : size,
        size_mobile : size_mobile,
        order       : order
      };

      if( isHiddenInput.length > 0 )
        value[ key ][ 'is_hidden' ] = ( isHiddenInput.is( ':checked' ) ? 1 : 0 );

    });

    this.elementorInstance.setValue( value );
  },

};

(function () {
  jQuery(window).load(function () {
    let pricingplans = window.elementor.modules.controls.BaseData.extend({

      _instance : null,

      onReady: function () {
        this._instance = jQuery.extend( true, {}, PaymentPageElementor_FormFields );

        this._instance.Init( this );
      },

      ui: function ui() {
        return {
          anySetting : 'input[data-setting]'
        };
      },

      onFieldChange : function(event) {
        this._instance.syncToElementor();
      },

      onApplyClicked : function(event) {
        event.preventDefault();

        this._instance.syncToElementor();
      }
    });

    window.elementor.addControlView("payment_page_form_fields", pricingplans);
  });
})(jQuery);