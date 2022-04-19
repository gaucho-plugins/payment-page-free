window.addEventListener( 'elementor/init', () => {
  var fieldsSorted = window.elementor.modules.controls.BaseData.extend({
    // Set presaved values
    onReady: function () {
      var data = this.getControlValue();
      // Initialize data from backend if exists
      this.initFieldsData(data);
    },
    initFieldsData: function initFieldsData(data) {
      let customText_1 = data.payment_button_text_1;
      let customText_2 = data.payment_button_text_2;
      let customText_3 = data.payment_button_text_3;

      let sortedFields = data.sorted;
      var self = this;

      // First load input data
      jQuery(this.ui.payment_button_text_1).val(customText_1);
      jQuery(this.ui.payment_button_text_2).val(customText_2);
      jQuery(this.ui.payment_button_text_3).val(customText_3);

      // Initialize with empty sorted container
      jQuery(this.ui.fieldsContainer).empty();

      sortedFields.split(",").forEach(function (fieldId) {
        var element = { id: "", label: "" };
        switch (fieldId) {
          case "currency":
            element = { id: fieldId, label: "Currency" };
            break;
          case "totalPrice":
            element = { id: fieldId, label: "Price" };
            break;
          case "frequency":
            element = { id: fieldId, label: "Frequency" };
            break;
          case "customText1":
            element = { id: fieldId, label: "Custom text 1" };
            break;
          case "customText2":
            element = { id: fieldId, label: "Custom text 2" };
            break;
          case "customText3":
            element = { id: fieldId, label: "Custom text 3" };
            break;
        }
        self.addElement(element);
      });

      // Activate jquery sorted
      this.prepareSortedField();
    },
    prepareSortedField: function prepareSortedField() {
      this.ui.fieldsContainer.sortable();
      this.ui.fieldsContainer.disableSelection();
      this.ui.hiddenInput.change();
    },
    ui: function ui() {
      return {
        fieldsContainer: "#fields-sorted-container",
        hiddenInput: 'input[data-setting="price"]',
        customTextInput: 'input[data-setting="payment_button_text"]',
        payment_button_text_1: 'input[data-custom-text-number="1"]',
        payment_button_text_2: 'input[data-custom-text-number="2"]',
        payment_button_text_3: 'input[data-custom-text-number="3"]',
        separatorTextInput:
          'input[data-setting="payment_button_separator_text"]',
      };
    },

    events: function events() {
      return {
        "sortout @ui.fieldsContainer": "onSortEnd",
        "change @ui.hiddenInput": "onHiddenChange",
        "keyup @ui.customTextInput": "onCustomTextChange",
        "keyup @ui.separatorTextInput": "onSeparatorTextChange",
      };
    },
    onCustomTextChange: function (element) {
      var prevData = this.getControlValue();
      var customTextValue = jQuery(element.currentTarget).val();
      var customTextId = jQuery(element.currentTarget).attr(
        "data-custom-text-number"
      );

      if (customTextValue.length <= 0) {
        this.deleteElement({ id: "customText" + customTextId });
      } else {
        this.addElement({
          id: "customText" + customTextId,
          label: "Custom text" + customTextId,
        });
      }
      jQuery(this.ui.fieldsContainer).sortable("refresh");
      var orderOfText = jQuery(this.ui.fieldsContainer).sortable("toArray");
      this.ui.hiddenInput.val(orderOfText.toString());

      var data = {
        sorted: orderOfText.toString(),
      };

      switch (customTextId) {
        case "1":
          data["payment_button_text_1"] = customTextValue;
          break;
        case "2":
          data["payment_button_text_2"] = customTextValue;
          break;
        case "3":
          data["payment_button_text_3"] = customTextValue;
          break;
      }

      this.setValue({ ...prevData, ...data });
    },
    deleteElement: function (element) {
      jQuery("#" + element.id).remove();
      this.ui.hiddenInput.change();
    },
    addElement: function (element) {
      var elementContainer = jQuery(
        '<div id="' +
        element.id +
        '"class="ui-state-default">' +
        element.label +
        "</div>"
      );
      if (
        jQuery("#fields-sorted-container").find("#" + element.id).length <= 0
      ) {
        jQuery(this.ui.fieldsContainer).append(elementContainer);
      }
    },
    onSortEnd: function (self) {
      var orderOfText = this.ui.fieldsContainer.sortable("toArray");
      this.ui.hiddenInput.val(orderOfText.toString());
      this.ui.hiddenInput.val(orderOfText.toString());
      this.ui.hiddenInput.change();
    },
    onHiddenChange: function onHiddenChange() {
      var data = this.getControlValue();
      var orderOfText = this.ui.fieldsContainer.sortable("toArray");

      var data = {
        ...data,
        sorted: orderOfText.toString(),
      };
      this.setValue(data);
    },
    onBeforeDestroy: function () {},
  });

  window.elementor.addControlView("fieldssorted", fieldsSorted);
} );