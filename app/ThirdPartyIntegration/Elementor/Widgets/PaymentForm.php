<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Widgets;

use PaymentPage\PaymentForm as PP_PaymentForm;

class PaymentForm extends \Elementor\Widget_Base{

  /**
   * @var PaymentFormSections\Skeleton[]
   */
  public $_sectionsInstances = [];

  public function __construct($data = [], $args = null){
    parent::__construct($data, $args);

    $this->_sectionsInstances = [
      new PaymentFormSections\PaymentFormDisplay( $this ),

      new PaymentFormSections\GlobalOptions( $this ),

      new PaymentFormSections\PricingPlans( $this ),

      new PaymentFormSections\PaymentGateways( $this ),

      new PaymentFormSections\Form( $this ),

      new PaymentFormSections\CurrencySelector( $this ),
      new PaymentFormSections\SubscriptionSelector( $this ),

      new PaymentFormSections\SubmitButtonControl( $this ),
      new PaymentFormSections\ActionsForm( $this ),

      new PaymentFormSections\Upgrade( $this ),
    ];

    $file_version = payment_page_frontend_file_version();

    wp_register_style(PAYMENT_PAGE_PREFIX,plugins_url( 'interface/app/style.css', PAYMENT_PAGE_BASE_FILE_PATH ), [], $file_version );
    wp_enqueue_style(PAYMENT_PAGE_PREFIX );

    wp_register_script( PAYMENT_PAGE_PREFIX, plugins_url( 'interface/app/app.min.js', PAYMENT_PAGE_BASE_FILE_PATH ), [ 'jquery', 'wp-util', 'lodash' ], $file_version, true );

    wp_localize_script( PAYMENT_PAGE_PREFIX, 'payment_page_data', [
      'configuration' => payment_page_frontend_configuration(),
      'lang'          => payment_page_frontend_language()
    ] );
  }

  public function get_name(){
      return 'payment-form';
  }

  public function get_title(){
      return __('Payment Page - Form', 'payment-page');
  }

  public function get_icon(){
      return 'payment-page-elementor-icon';
  }

  public function get_categories(){
      return [
          'payment-page'
      ];
  }

  public function get_script_depends() {
    return [ PAYMENT_PAGE_PREFIX ];
  }

  protected function register_controls(){
    foreach( $this->_sectionsInstances as $sectionsInstance )
      if( $sectionsInstance->is_enabled() )
        $sectionsInstance->attach_controls();
  }

  protected function render(){
    $settings = $this->get_settings_for_display();

    echo PP_PaymentForm::instance()->get_from_elementor_settings( $settings, get_the_ID() );

    if( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
      echo '<script type="text/javascript">
              if( typeof PaymentPage === "undefined" ) {
                setTimeout( function() { PaymentPage.Init( jQuery("body" ) ); }, 500 );
              } else {
                PaymentPage.Init( jQuery("body" ) );
              }
            </script>';
    }
  }
}