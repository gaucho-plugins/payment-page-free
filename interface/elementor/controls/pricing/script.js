let PaymentPageElementor_Pricing = {

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
        control_value = elementorInstance.getControlValue();

    if( typeof control_value === "object" )
      control_value = Object.values( control_value );

    if( !( control_value instanceof Array ) )
      control_value = [ {
        currency        : 'usd',
        price           : 99,
        has_setup_price : 0,
        setup_price     : '',
        frequency : {
          value: "one-time",
          label: "One-time"
        }
      } ];

    jQuery.each( control_value, function( _k, pricing_data ) {
      objectInstance._attachFieldRow( pricing_data );
    });

    this.container.find( '.add-more-options' ).off( "click" ).on( "click", function( event ) {
      event.preventDefault();

      objectInstance._attachFieldRow( {} );
    });
  },

  _attachFieldRow : function( pricing_data ) {
    this.groupTemplateContainer.clone().appendTo( this.repeaterContainer );

    let objectInstance = this,
        fieldRowContainer = this.repeaterContainer.find( '.field-group-template:last' );

    fieldRowContainer.removeClass( 'field-group-template' ).show();

    if( typeof pricing_data.price !== 'undefined' )
      fieldRowContainer.find( '[data-setting="price"]' ).val( pricing_data.price );

    if( typeof pricing_data.setup_price !== 'undefined' && fieldRowContainer.find( '[data-setting="setup_price"]' ).length > 0 )
      fieldRowContainer.find( '[data-setting="setup_price"]' ).val( pricing_data.setup_price );

    if( typeof pricing_data.has_setup_price !== 'undefined'
        && fieldRowContainer.find( '[data-setting="has_setup_price"]' ).length > 0
        && parseInt( pricing_data.has_setup_price ) )
      fieldRowContainer.find( '[data-setting="has_setup_price"]' ).prop('checked', true);

    if( typeof pricing_data.currency !== 'undefined' )
      fieldRowContainer.find( '[data-setting="currency"]' ).val( pricing_data.currency );

    if( typeof pricing_data.frequency !== 'undefined' && typeof pricing_data.frequency.value !== 'undefined' )
      fieldRowContainer.find( '[data-setting="frequency"]' ).val( pricing_data.frequency.value );

    fieldRowContainer.find( '[data-setting="frequency"]' ).on( "change", function() {
      if( fieldRowContainer.find( '[data-setting="has_setup_price"]' ).length === 0 )
        return;

      let target = fieldRowContainer.find( '[data-setting="has_setup_price"]' ),
          targetContainer = target.parents( "div:first" );

      if( jQuery(this).val() === 'one-time' ) {
        if( fieldRowContainer.find( '[data-setting="has_setup_price"]' ).is( ":checked" ) )
          fieldRowContainer.find( '[data-setting="has_setup_price"]' ).prop('checked', false ).trigger("change");

        targetContainer.hide();
      } else {
        targetContainer.show();
      }
    }).trigger( "change" );

    if( this.repeaterContainer.find( '.field-group' ).length >= 3 )
      fieldRowContainer.find( '.remove-price-row' ).parent().css( 'display', 'flex' );

    fieldRowContainer.find( '[data-setting="price"], [data-setting="currency"], [data-setting="frequency"], [data-setting="has_setup_price"], [data-setting="setup_price"]' )
                     .on( "keyup change", function( event ) {
      objectInstance._updateFieldRowIdentifierHash( jQuery(this).parents( '.field-group:first' ) );
      objectInstance.syncToElementor();
    });

    fieldRowContainer.find( '[data-setting="has_setup_price"]' ).on( "change", function() {
      let container = fieldRowContainer.find( '[data-setting="setup_price"]' ).parents( "div:first" );

      if( jQuery(this).is( ":checked" ) )
        container.show();
      else
        container.hide();
    }).trigger( "change" );

    fieldRowContainer.find( '.remove-price-row' ).off( "click" ).on( "click", function() {
      let _fieldRow = jQuery(this).parents( '.field-group:first' );

      _fieldRow.remove();
      objectInstance._checkAndNotifyFieldRowHashDuplicates( _fieldRow.attr( "data-field-value-hash" ) );

      objectInstance.syncToElementor();
    });

    this._updateFieldRowIdentifierHash( fieldRowContainer );
  },

  _updateFieldRowIdentifierHash : function( fieldRowContainer ) {
    let previous_hash = ( typeof fieldRowContainer.attr( "data-field-value-hash" ) !== 'undefined' ? fieldRowContainer.attr( "data-field-value-hash" ) : null ),
        hash = '';

    hash += fieldRowContainer.find( '[data-setting="price"]' ).val();
    hash += '_' + fieldRowContainer.find( '[data-setting="currency"]' ).val();

    if( fieldRowContainer.find( '[data-setting="frequency"]' ).length > 0 )
      hash += '_' + fieldRowContainer.find( '[data-setting="frequency"]' ).val();
    else
      hash += 'one-time' + fieldRowContainer.find( '[data-setting="frequency"]' ).val();

    fieldRowContainer.attr( "data-field-value-hash", hash );

    this._checkAndNotifyFieldRowHashDuplicates( previous_hash );
    this._checkAndNotifyFieldRowHashDuplicates( hash );
  },

  _checkAndNotifyFieldRowHashDuplicates : function( hash ) {
    let detected_fields = this.repeaterContainer.find( '[data-field-value-hash="' + hash + '"]' );

    if( detected_fields.length >= 2 ) {
      detected_fields.each( function() {
        if( jQuery(this).find( '.payment-page-field-hash-error' ).length === 1 )
          return true;

        jQuery(this).append(
          '<div class="payment-page-field-hash-error" style="color: #e74c3c;margin: 10px 0;font-size: 18px;text-align:center;">Duplicate Pricing Configuration.</div>'
        );
      });
    } else {
      detected_fields.find( '.payment-page-field-hash-error' ).remove();
    }
  },

  syncToElementor : function() {
    let value = [];

    this.repeaterContainer.find( '[data-field-value-hash]' ).each( function() {
      let frequencyObject = jQuery(this).find( '[data-setting="frequency"]' ),
          hasSetupPriceObject = jQuery(this).find( '[data-setting="has_setup_price"]' ),
          setupPriceObject = jQuery(this).find( '[data-setting="setup_price"]' );

      let data = {
        currency  : jQuery(this).find( '[data-setting="currency"]' ).val(),
        price        : jQuery(this).find( '[data-setting="price"]' ).val(),
        frequency : (
          frequencyObject.length >= 1
            ? {
              value: frequencyObject.val(),
              label: ( frequencyObject.find( 'option[value="' + frequencyObject.val() + '"]' ).text() )
            }
            : {
              value: "one-time",
              label: "One-time"
            }
        )
      };

      if( hasSetupPriceObject.length > 0 )
        data.has_setup_price = hasSetupPriceObject.is( ":checked" ) ? 1 : 0;

      if( setupPriceObject.length > 0 )
        data.setup_price = setupPriceObject.val();

      value.push( data );
    });

    this.elementorInstance.setValue( value );
  },

};

(function () {
  jQuery(window).load(function () {
    let pricingplans = window.elementor.modules.controls.BaseData.extend({

      _instance : null,

      onReady: function () {
        this._instance = jQuery.extend( true, {}, PaymentPageElementor_Pricing );

        this._instance.Init( this );
      },

      ui: function ui() {
        return {
          inputPrice: 'input[data-setting="price"]',
          setupPrice: 'input[data-setting="setup_price"]',
          selectCurrency: 'select[data-setting="currency"]',
          selectFrequency: 'select[data-setting="frequency"]',
          btnRemoveRow: ".remove-price-row",
          btnAddRow: ".add-more-options",
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

    window.elementor.addControlView("pricingplans", pricingplans);
  });
})(jQuery);