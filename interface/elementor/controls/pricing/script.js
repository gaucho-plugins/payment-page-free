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
        currency  : 'usd',
        price     : 99,
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

    if( typeof pricing_data.currency !== 'undefined' )
      fieldRowContainer.find( '[data-setting="currency"]' ).val( pricing_data.currency );

    if( typeof pricing_data.frequency !== 'undefined' && typeof pricing_data.frequency.value !== 'undefined' )
      fieldRowContainer.find( '[data-setting="frequency"]' ).val( pricing_data.frequency.value );

    if( this.repeaterContainer.find( '.field-group' ).length >= 3 )
      fieldRowContainer.find( '.remove-price-row' ).parent().css( 'display', 'flex' );

    fieldRowContainer.find( '[data-setting="price"], [data-setting="currency"], [data-setting="frequency"]' ).on( "keyup change", function( event ) {
      objectInstance._updateFieldRowIdentifierHash( jQuery(this).parents( '.field-group:first' ) );
      objectInstance.syncToElementor();
    });

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
      let frequencyObject = jQuery(this).find( '[data-setting="frequency"]' );

      value.push( {
        currency  : jQuery(this).find( '[data-setting="currency"]' ).val(),
        price     : jQuery(this).find( '[data-setting="price"]' ).val(),
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
      } );
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