<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Widgets\PaymentFormSections;

use Elementor\Controls_Manager;

class GlobalOptions extends Skeleton {

  public $control_alias = 'global_options';

  public function attach_controls(){
    $this->elementorWidgetInstance->start_controls_section( $this->control_alias.'_section_title_global_options', [
      'label' => __('Global Options', 'payment-page'),
      'tab'   => Controls_Manager::TAB_STYLE
    ]);

    $this->elementorWidgetInstance->add_control('section_'.$this->control_alias.'_section_title', [
      'label'     => __( 'Section Titles', 'payment-page' ),
      'type'      => Controls_Manager::HEADING,
      'separator' => 'before'
    ]);

    payment_page_elementor_builder_attach_margin_control($this->elementorWidgetInstance, $this->control_alias, 'title', __( "Spacing", "payment-page" ), [
      'unit'        => 'px',
      'size_top'    => 10,
      'size_right'  => 0,
      'size_bottom' => 10,
      'size_left'   => 0
    ] );

    $this->elementorWidgetInstance->end_controls_section();
  }
}