

(function($) {

  $(window).load( function(){

    var pricing_selector_data = window.elementor.modules.controls.BaseData.extend({
      // Set presaved values
      onReady: function(){
        var data = this.getControlValue();
        this.initFieldsData(data)
      },

      ui: function ui() {
        return {
          fieldsContainer: '#plan-fields-sorted-container',
          hiddenInput: 'input[data-setting="select_field_sorted_fields"]',
          checkboxSwitcher : 'input[class="display_field"]',
          customTextInput : 'input[data-setting="custom_text_tag_input"]',
          separatorTextInput : 'input[data-setting="select_field_separator_text"]',
          priceCheckbox : 'input[data-setting="display_price"]',
          currencyCheckbox : 'input[data-setting="display_currency"]',
          frequencyCheckbox : 'input[data-setting="display_frequency"]',
          setupPriceCheckbox : 'input[data-setting="display_setup_price"]',
        };
      },
      initFieldsData: function initFieldsData(data){
        let customText = data.customText;
        let separatorText = data.separatorText;
        let sortedFields = data.sorted;
        var self = this;

        // First load input data
        jQuery(this.ui.customTextInput).val(customText)
        jQuery(this.ui.separatorTextInput).val(separatorText)


        // Initialize with empty sorted container
        jQuery(this.ui.fieldsContainer).empty();

        sortedFields.split(',').forEach( function(fieldId){
          var element = {id:'', label:''};
          switch(fieldId){
            case 'select_field_plan_name':
              element = {id: fieldId, label:'Plan name'}
            break;
            case 'select_field_plan_price_currency':
              element = {id: fieldId, label:'Currency'}
              jQuery(self.ui.currencyCheckbox).prop('checked', true)
            break;
            case 'select_field_plan_price':
              element = {id: fieldId, label:'Price'}
              jQuery(self.ui.priceCheckbox).prop('checked', true)
            break;
            case 'select_field_frequency':
              element = {id: fieldId, label:'Frequency'};
              jQuery(self.ui.frequencyCheckbox).prop('checked', true);
            break;
            case 'select_field_setup_price':
              element = {id: fieldId, label:'First payment amount'};
              jQuery(self.ui.setupPriceCheckbox).prop('checked', true);
              break
            case 'select_field_custom_text':
              element = {id: fieldId, label:'Custom text'}
            break;
            case 'select_field_custom_text_3':
              element = {id: fieldId, label:'Custom text 3'}
              break;
            case 'select_field_separator_text':
              element = {id: fieldId, label:'Custom text 2'}
            break;
          }
          self.addElement(element)
        })

        // Activate jquery sorted
       this.prepareSortedField()

      },
      prepareSortedField: function prepareSortedField(){
        this.ui.fieldsContainer.sortable();
        this.ui.fieldsContainer.disableSelection();
        this.ui.hiddenInput.change();
      },
      events: function events() {
        return {
          'sortout @ui.fieldsContainer': 'onSortEnd',
          'change @ui.hiddenInput': 'onHiddenChange',
          'change @ui.checkboxSwitcher': 'onChangeSwitcher',
          'keyup @ui.customTextInput': 'onChangeCustomText',
          'keyup @ui.separatorTextInput': 'onChangeSeparatorText',

        };
      },
      onChangeSeparatorText: function(element){
        // Get new value
        var customText = jQuery(element.currentTarget).val();

        // Add tag to sort field
        if( customText.length <= 0 ){
          this.deleteElement({id:'select_field_separator_text'})
        }else{
          this.addElement({id:'select_field_separator_text', label:'Custom Text 2'})
        }
      
        // Update sorted field by refresh
        jQuery(this.ui.fieldsContainer).sortable('refresh');
        var orderOfText = jQuery(this.ui.fieldsContainer).sortable('toArray');
        this.ui.hiddenInput.val( orderOfText.toString() )

        // Set new value for sort and custom text
        var _data = this.getControlValue();
        var data = {
          ..._data,
          sorted:orderOfText.toString(),
          separatorText:customText
        }
        this.setValue(data)
      },
      onChangeCustomText: function(element){
        // Get new value
        var customText = jQuery(element.currentTarget).val();

        // Add tag to sort field
        if( customText.length <= 0 ){
          this.deleteElement({id:'select_field_custom_text'})
        }else{
          this.addElement({id:'select_field_custom_text', label:'Custom text'})
        }
      
        // Update sorted field by refresh
        jQuery(this.ui.fieldsContainer).sortable('refresh');
        var orderOfText = jQuery(this.ui.fieldsContainer).sortable('toArray');
        this.ui.hiddenInput.val( orderOfText.toString() )

        // Set new value for sort and custom text
        var _data = this.getControlValue();
        var data = {
          ..._data,
          sorted:orderOfText.toString(),
          customText:customText
        }
        this.setValue(data)
      },
      prepareCheckbox: function(element){
        // Check or uncheck based on saved data
        jQuery('input[data-setting="'+element+'"]').prop("checked", true );
        
      },
      onChangeSwitcher: function(self){
        var isChecked = jQuery(self.currentTarget).prop('checked');
        var element = {
          id: jQuery(self.currentTarget).attr('data-fieldId'),
          label: jQuery(self.currentTarget).attr('data-fieldLabel')
        }

        
        if( isChecked ){
          this.addElement(element)
        }else{
          this.deleteElement(element)
        }
        this.ui.hiddenInput.change();

      },
      deleteElement: function(element){
        jQuery("#"+element.id).remove()
        this.ui.hiddenInput.change();
      },
      addElement :function(element){
        var elementContainer = jQuery('<div id="'+element.id +'"class="ui-state-default">'+element.label+'</div>') 
        if ( jQuery('#plan-fields-sorted-container').find('#'+element.id).length <= 0 ){
          jQuery(this.ui.fieldsContainer).append(elementContainer)
        }
     },
      onSortEnd: function (self){
        var orderOfText = this.ui.fieldsContainer.sortable('toArray');            
        
        this.ui.hiddenInput.val( orderOfText.toString() )
        this.ui.hiddenInput.change();
      },
      // Hidden input store all required fields to display
      onHiddenChange: function onHiddenChange(){
        var data = this.getControlValue();
        var orderOfText = this.ui.fieldsContainer.sortable('toArray');            

        var data = {
          ...data,
          sorted: orderOfText.toString()
        }

        this.setValue(  data );

      },
      onBeforeDestroy: function () {
  
      }
     
    });
    
    window.elementor.addControlView('pricing_selector_data', pricing_selector_data);

  } )
  

})(jQuery);