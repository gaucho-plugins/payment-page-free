<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Widgets\PaymentFormSections;

use Elementor\Controls_Manager;
use Elementor\Core\Schemes;
use Paymentpage\includes;

class PricingPlans extends Skeleton {

  public $control_alias = "pricing_plan";

  public function attach_controls(){
    $this->elementorWidgetInstance->start_controls_section('plans_section', [
      'label' => __('Pricing Plans', 'payment-page'),
      'tab'   => \Elementor\Controls_Manager::TAB_CONTENT
    ]);

    $this->elementorWidgetInstance->add_control(
      'pricing_selector_section_label',
      [
        'label'       => __( 'Section Title', 'payment-page' ),
        'type'        => \Elementor\Controls_Manager::TEXT,
        'default'     => __( 'CHOOSE YOUR PLAN', 'payment-page' ),
        'placeholder' => __( 'Type your section title', 'payment-page' ),
      ]
    );

    $repeater = new \Elementor\Repeater();

    $repeater->add_control('plan_title', [
        'label' => __('Title', 'payment-page'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Plan Title', 'payment-page'),
        'label_block' => true
    ]);

    $prices_description = payment_page_fs()->is_free_plan()
      ? ( payment_page_admin_upgrade_format( payment_page_admin_upgrade_text_subscription() ) . '<br/>' .
          payment_page_admin_upgrade_format( payment_page_admin_upgrade_text_custom_payment_amount() ) )
      : "If left empty, the customer will be able to enter any amount.";

    $repeater->add_control('prices', [
      'type' => 'pricingplans',
      'label_block' => true,
      'description' => $prices_description
    ]);

    $this->elementorWidgetInstance->add_control('plans', [
      'label' => __('Plans List', 'payment-page'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => [
          [
              'plan_title' => __('Your first plan', 'payment-page')
          ],
          [
              'plan_title' => __('Your second plan', 'payment-page')
          ]
      ],
      'title_field' => '{{{ plan_title }}}'
    ]);

    $this->elementorWidgetInstance->add_control('pricing_selector_label', [
      'label' => __('Select Fields To Display:', 'payment-page'),
      'type' => 'pricing_selector_data'
    ]);

    if( !payment_page_fs()->is_free_plan() ){
      $this->elementorWidgetInstance->add_control(
        'custom_pricing_input_section_label',
        [
          'label'       => __( 'Custom Amount Title', 'payment-page' ),
          'type'        => \Elementor\Controls_Manager::TEXT,
          'default'     => __( 'ENTER YOUR AMOUNT', 'payment-page' ),
          'placeholder' => __( 'Type your custom amount title', 'payment-page' ),
        ]
      );
    }

    $this->elementorWidgetInstance->end_controls_section();

    $this->elementorWidgetInstance->start_controls_section('section_pricing_plan_styles', [
      'label' => __(' Pricing Plans', 'payment-page'),
      'tab' => Controls_Manager::TAB_STYLE
    ]);

    $this->elementorWidgetInstance->add_control('section_pricing_plan_styles_title', [
      'label' => __('Section Title', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);

    $this->elementorWidgetInstance->add_control('pricing_plan_select_title_color', [
      'label' => __('Title Color', 'payment-page'),
      'type' => Controls_Manager::COLOR,
      'default' => '#2676f1',
      'scheme' => [
        'type' => Schemes\Color::get_type(),
        'value' => Schemes\Color::COLOR_1
      ]
    ]);

    $defaults = array(
      'font_family'=> PAYMENT_PAGE_STYLE_DEFAULT_FONT_FAMILY,
      'font_size'=> array(
        'unit' => 'px',
        'size' => 12
      ),
      'font_weight'=> PAYMENT_PAGE_STYLE_DEFAULT_FONT_WEIGHT,
      'font_transform'=> 'none',
    );

    payment_page_elementor_builder_attach_popover_typography($this->elementorWidgetInstance, $this->control_alias , 'select_title',  $defaults);

    // Start custom amount section title

    if( !payment_page_fs()->is_free_plan() ){

      // Section title
      $this->elementorWidgetInstance->add_control('section_pricing_plan_custom_amount_styles_title', [
        'label' => __('Custom Amount Title', 'payment-page'),
        'type' => Controls_Manager::HEADING,
        'separator' => 'before'
      ]);

      $this->elementorWidgetInstance->add_control('pricing_plan_custom_amount_color', [
        'label' => __('Title Color', 'payment-page'),
        'type' => Controls_Manager::COLOR,
        'default' => '#2676f1',
        'scheme' => [
          'type' => Schemes\Color::get_type(),
          'value' => Schemes\Color::COLOR_1
        ]
      ]);


      $defaults = array(
        'font_family'=> PAYMENT_PAGE_STYLE_DEFAULT_FONT_FAMILY,
        'font_size'=> array(
          'unit' => 'px',
          'size' => 12
        ),
        'font_weight'=> PAYMENT_PAGE_STYLE_DEFAULT_FONT_WEIGHT,
        'font_transform'=> 'none',
      );

      payment_page_elementor_builder_attach_popover_typography($this->elementorWidgetInstance, $this->control_alias , 'custom_amount',  $defaults);
    }

    // End custom amount section title


    $this->elementorWidgetInstance->add_control('section_pricing_plan_styles_heading', [
      'label' => __('Border', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);
    $field_name = "select";
    $defaults = array(
      'border_color'=> '#cec3e6',
      'border_radius' =>   array(
        'unit' => 'px',
        'size' => 5
      ),
      'border_size'=> array(
        'unit' => 'px',
        'size' => 1
      ),
    );
    payment_page_elementor_builder_attach_border_control( $this->elementorWidgetInstance, $this->control_alias, $field_name, $defaults );

    $this->elementorWidgetInstance->add_control('section_pricing_plan_styles_dropdown_heading', [
      'label' => __('Pricing Plans Dropdown', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);
    payment_page_elementor_builder_attach_background_control($this->elementorWidgetInstance, $this->control_alias, 'select', null, '#dedef6' );
    payment_page_elementor_builder_attach_arrow_control($this->elementorWidgetInstance, $this->control_alias, 'select_arrow', __( "Arrow Color", "payment-page" ), '#5e3da8' );
    payment_page_elementor_builder_attach_color_control($this->elementorWidgetInstance, $this->control_alias , 'select_text', null, '#5e3da8');
    payment_page_elementor_builder_attach_popover_typography($this->elementorWidgetInstance, $this->control_alias , 'select_text',[
      'font_family' => PAYMENT_PAGE_STYLE_DEFAULT_FONT_FAMILY,
      'font_size'   => [
        'unit' => 'px',
        'size' => 16
      ],
      'font_weight'     => PAYMENT_PAGE_STYLE_DEFAULT_FONT_WEIGHT,
      'font_transform'  => 'none',
    ] );

    $this->elementorWidgetInstance->add_control('section_pricing_plan_styles_list_heading', [
      'label' => __('Pricing Plans Dropdown List', 'payment-page'),
      'type' => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);
    payment_page_elementor_builder_attach_background_control($this->elementorWidgetInstance, $this->control_alias , 'select_option', 'Background Color (Inactive)' , 'white');
    payment_page_elementor_builder_attach_background_control($this->elementorWidgetInstance, $this->control_alias , 'select_option_active', 'Background Color (Active)' , 'blue');

    $this->elementorWidgetInstance->end_controls_section();
  }

}