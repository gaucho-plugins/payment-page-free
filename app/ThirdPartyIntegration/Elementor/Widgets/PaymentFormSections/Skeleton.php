<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Widgets\PaymentFormSections;

use \Elementor\Widget_Base;

abstract class Skeleton {

  /**
   * @var Widget_Base
   */
  public $elementorWidgetInstance;
  public $control_alias = null;

  public function __construct($elementor){
    $this->elementorWidgetInstance = $elementor;
  }

  abstract public function attach_controls();

  public function is_enabled() :bool {
    return true;
  }

  public function get_style_map( $settings ) {
    return [];
  }

}