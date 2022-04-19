<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Widgets\PaymentFormSections;

use Elementor\Controls_Manager;
use Elementor\Core\Schemes;
use PaymentPage\PaymentGateway as PaymentGateway;

class Form extends Skeleton {

  public $control_alias = "form";

	public function attach_controls(){
		$this->elementorWidgetInstance->start_controls_section($this->control_alias, [
      'label' => __('Form Fields', 'payment-page')
		]);
	
		$this->elementorWidgetInstance->add_control($this->control_alias."_data_section_label", [
      'label'       => __( 'Section Title', 'payment-page' ),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'default'     => __( 'PAYMENT METHOD', 'payment-page' ),
      'placeholder' => __( 'Type your section title', 'payment-page' ),
    ]);

    $payment_page_form_fields_description = '';

    if( payment_page_fs()->is_free_plan() ) {
      $payment_page_form_fields_description .= payment_page_admin_upgrade_format( payment_page_admin_upgrade_custom_fields() ) . '<br/>';
    }

    $payment_page_form_fields_description .=
      '<p style="font-style: normal;font-size: 13px;color: #fff;">' .
        __( "Custom fields are stored at your payment gateway in the relevant transaction details. E.g. - You might want to collect an invoice ID when a customer is entering a unique Custom Amount for an invoice.", "payment-page" ) .
      '</p>';

    if( PaymentGateway::get_integration_from_settings( 'paypal' )->is_configured() )
      $payment_page_form_fields_description .= '<p style="font-style: normal;font-size: 13px;color: #fff;">' . __( "PayPal does not support storing Custom Fields.", "payment-page" ) . '</p>';

    $this->elementorWidgetInstance->add_control($this->control_alias . "_fields_map", [
			'type'   => 'payment_page_form_fields',
      'description' => $payment_page_form_fields_description
		]);

		$this->elementorWidgetInstance->end_controls_section();

    $this->elementorWidgetInstance->start_controls_section( $this->control_alias.'_section_title_style', [
      'label' => __('Form', 'payment-page'),
      'tab'   => Controls_Manager::TAB_STYLE
    ]);

    $field_name_title = "section_title";
    $this->elementorWidgetInstance->add_control( $this->control_alias. '_' . $field_name_title . '_style_title', [
      'label' => __('Section Title', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);

    $this->elementorWidgetInstance->add_control ( $this->control_alias. '_' . $field_name_title . '_style_color', [
      'label' => __('Title Color', 'payment-page'),
      'type' => Controls_Manager::COLOR,
      'default' => '#2676f1',
      'scheme' => [
        'type' => Schemes\Color::get_type(),
        'value' => Schemes\Color::COLOR_1
      ]
    ]);
    $this->elementorWidgetInstance->add_control( $this->control_alias. '_' . $field_name_title . '_style_typography', [
      'label' => __('Typography', 'payment-page'),
      'type' => \Elementor\Controls_Manager::POPOVER_TOGGLE,
      'label_off' => __('Default', 'payment-page'),
      'label_on' => __('Custom', 'payment-page'),
      'return_value' => 'yes'
    ]);
    $this->elementorWidgetInstance->start_popover();
    $this->elementorWidgetInstance->add_control( $this->control_alias. '_' . $field_name_title . '_style_font_family', [
      'label' => __('Font Family', 'payment-page'),
      'type' => \Elementor\Controls_Manager::FONT,
      'default' => PAYMENT_PAGE_STYLE_DEFAULT_FONT_FAMILY
    ]);

    $this->elementorWidgetInstance->add_control( $this->control_alias. '_' . $field_name_title . '_style_font_size', [
      'label'      => __('Size', 'payment-page'),
      'type'       => Controls_Manager::SLIDER,
      'size_units' => [ 'px', '%', 'em' ],
      'default'    => [ 'unit' => 'px', 'size' => 12 ],
      'range'      => [ 'px' => [ 'min' => 1, 'max' => 200 ] ],
      'responsive' => true
    ]);

    $this->elementorWidgetInstance->add_control( $this->control_alias. '_' . $field_name_title . '_style_font_weight', [
      'label'   => _x('Weight', 'Typography Control', 'elementor'),
      'type'    => Controls_Manager::SELECT,
      'default' => 'normal',
      'options' => payment_page_elementor_builder_font_weight_assoc()
    ]);

    $this->elementorWidgetInstance->add_control( $this->control_alias. '_' . $field_name_title . '_style_font_transform', [
      'label'   => _x('Transform', 'Typography Control', 'elementor'),
      'type'    => Controls_Manager::SELECT,
      'default' => 'none',
      'options' => payment_page_elementor_builder_text_transform_assoc()
    ]);
    $this->elementorWidgetInstance->end_popover();

    $field_name = "field_label";
    $this->elementorWidgetInstance->add_control( $this->control_alias. '_'.$field_name.'_styles_heading', [
      'label' => __('Field Labels (Inactive)', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);

    payment_page_elementor_builder_attach_color_control($this->elementorWidgetInstance, $this->control_alias , $field_name);
    payment_page_elementor_builder_attach_popover_typography($this->elementorWidgetInstance, $this->control_alias,  $field_name);

    $field_name = "field_label_active";
    $this->elementorWidgetInstance->add_control( $this->control_alias. '_'.$field_name.'_styles_heading', [
      'label' => __('Field Labels (Active)', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);

    payment_page_elementor_builder_attach_color_control($this->elementorWidgetInstance, $this->control_alias , $field_name);
    payment_page_elementor_builder_attach_popover_typography($this->elementorWidgetInstance, $this->control_alias,  $field_name);

    $field_name = 'field_input';
    $defaults = array(
      'font_family'=> PAYMENT_PAGE_STYLE_DEFAULT_FONT_FAMILY,
      'font_size'=> array(
        'unit' => 'px',
        'size' => 12
      ),
      'font_weight'=> PAYMENT_PAGE_STYLE_DEFAULT_FONT_WEIGHT,
      'font_transform'=> 'none',
    );
    $this->elementorWidgetInstance->add_control($this->control_alias. '_'.$field_name.'_heading', [
      'label' => __('Input Text', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);
    payment_page_elementor_builder_attach_color_control($this->elementorWidgetInstance, $this->control_alias , $field_name, null , '#8676aa');
    payment_page_elementor_builder_attach_popover_typography($this->elementorWidgetInstance, $this->control_alias , $field_name,  $defaults);

    $field_name = 'field_input_placeholder';
    $defaults = array(
      'font_family'=> PAYMENT_PAGE_STYLE_DEFAULT_FONT_FAMILY,
      'font_size'=> array(
        'unit' => 'px',
        'size' => 13
      ),
      'font_weight'=> PAYMENT_PAGE_STYLE_DEFAULT_FONT_WEIGHT,
      'font_transform'=> 'none',
    );
    $this->elementorWidgetInstance->add_control($this->control_alias. '_'.$field_name.'_heading', [
      'label' => __('Placeholder Text', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);
    payment_page_elementor_builder_attach_color_control($this->elementorWidgetInstance, $this->control_alias, $field_name, null, '#32325d' );
    payment_page_elementor_builder_attach_popover_typography($this->elementorWidgetInstance, $this->control_alias , $field_name,  $defaults);

    $field_name = 'fields';

    $this->elementorWidgetInstance->add_control( $this->control_alias. '_'.$field_name.'_style', [
      'label' => __('Border', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);
    payment_page_elementor_builder_attach_border_control($this->elementorWidgetInstance, $this->control_alias, $field_name );

    $this->elementorWidgetInstance->add_control('hr', [
      'type' => \Elementor\Controls_Manager::DIVIDER
    ]);

    payment_page_elementor_builder_attach_background_control($this->elementorWidgetInstance, $this->control_alias, $field_name);

    $this->elementorWidgetInstance->end_controls_section();
  }

}