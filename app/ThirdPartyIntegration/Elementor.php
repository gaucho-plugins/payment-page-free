<?php

namespace PaymentPage\ThirdPartyIntegration;

use Elementor\Plugin as Elementor_Plugin;
use PaymentPage\ThirdPartyIntegration\Elementor\Controls\DisabledSwitcher as PP_Controls_DisabledSwitcher;
use PaymentPage\ThirdPartyIntegration\Elementor\Controls\FieldsSortedControl as PP_Controls_FSC;
use PaymentPage\ThirdPartyIntegration\Elementor\Controls\FormFields as PP_Controls_FormFields;
use PaymentPage\ThirdPartyIntegration\Elementor\Controls\PricingControl as PP_Controls_PC;
use PaymentPage\ThirdPartyIntegration\Elementor\Controls\PricingSelectorControl as PP_Controls_PSC;
use PaymentPage\ThirdPartyIntegration\Elementor\Widgets\PaymentForm as PP_PaymentForm_Widget;

class Elementor {

  /**
   * @var null|Elementor;
   */
  protected static $_instance = null;

  /**
   * @return Elementor
   */
  public static function instance(): Elementor {
    if( self::$_instance === null )
      self::$_instance = new self();

    return self::$_instance;
  }

  public function setup() {
    add_action('elementor/controls/controls_registered', [ $this, 'register_controls' ] );
    add_action('elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );
    add_action('elementor/elements/categories_registered', [ $this, 'register_category' ] );
  }

  public function register_controls() {
    $disabled_switcher_instance = new PP_Controls_DisabledSwitcher();
    $fsc_instance = new PP_Controls_FSC();
    $form_fields_instance = new PP_Controls_FormFields();
    $pc_instance = new PP_Controls_PC();
    $psc_instance = new PP_Controls_PSC();

    $controls_manager = Elementor_Plugin::instance()->controls_manager;

    $controls_manager->register( $disabled_switcher_instance, $disabled_switcher_instance->get_type() );
    $controls_manager->register($fsc_instance, $fsc_instance->get_type() );
    $controls_manager->register($pc_instance, $pc_instance->get_type() );
    $controls_manager->register($psc_instance, $psc_instance->get_type() );
    $controls_manager->register($form_fields_instance, $form_fields_instance->get_type() );
  }

  public function register_widgets() {
    Elementor_Plugin::instance()->widgets_manager->register(new PP_PaymentForm_Widget() );
  }

  public function register_category($elements_manager) {
    $elements_manager->add_category('payment-page', [
      'title' => __('Payment Page', 'payment-page'),
      'icon' => 'fa fa-plug'
    ]);
  }

}
