<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Widgets\PaymentFormSections;

use Elementor\Controls_Manager;

class SubscriptionSelector extends Skeleton {

  public function is_enabled() :bool {
    return true;
  }

  public function attach_controls(){
    $this->elementorWidgetInstance->start_controls_section('subscription_selector_section', [
      'label' => 'Subscription Filter',
      'tab' => Controls_Manager::TAB_CONTENT
    ]);

    if( payment_page_fs()->is_free_plan() ) {
      $this->elementorWidgetInstance->add_control('subscription_selector', [
        'label'         => __('Subscription Filter', 'payment-page'),
        'type'          => 'switcher_disabled',
        'label_on'      => __('On', 'payment-page'),
        'label_off'     => __('Off', 'payment-page'),
        'default'       => 'no',
        'description'   => payment_page_admin_upgrade_format( payment_page_admin_upgrade_subscription_frequency_filter() )
      ]);
    } else {
      $this->elementorWidgetInstance->add_control('subscription_selector', [
        'label'     => __('Subscription Filter', 'payment-page'),
        'type'      => Controls_Manager::SWITCHER,
        'label_on'  => __('On', 'payment-page'),
        'label_off' => __('Off', 'payment-page'),
        'default'   => 'yes'
      ]);
    }

    $this->elementorWidgetInstance->end_controls_section();
  }
}