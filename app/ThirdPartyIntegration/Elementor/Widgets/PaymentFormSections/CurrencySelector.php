<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Widgets\PaymentFormSections;

use  Elementor\Controls_Manager ;
class CurrencySelector extends Skeleton
{
    public  $control_alias = "switcher" ;
    private  $_defaultBorderStyle = array(
        'border_color'  => '#cec3e6',
        'border_radius' => array(
        'unit' => 'px',
        'size' => 0,
    ),
        'border_size'   => array(
        'unit' => 'px',
        'size' => 0,
    ),
    ) ;
    public function attach_controls()
    {
        $this->elementorWidgetInstance->start_controls_section( 'currency_selector_section', [
            'label' => 'Currency Options',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );
        
        if ( payment_page_fs()->is_free_plan() ) {
            $this->elementorWidgetInstance->add_control( 'currency_selector', [
                'label'        => __( 'Currency Filter', 'payment-page' ),
                'type'         => 'switcher_disabled',
                'label_on'     => __( 'On', 'payment-page' ),
                'label_off'    => __( 'Off', 'payment-page' ),
                'default'      => 'no',
                'description'  => payment_page_admin_upgrade_format( payment_page_admin_upgrade_currency_filter() ),
                'return_value' => 'usd',
            ] );
        } else {
            $this->elementorWidgetInstance->add_control( 'currency_selector', [
                'label'     => __( 'Currency Filter', 'payment-page' ),
                'type'      => \Elementor\Controls_Manager::SWITCHER,
                'label_on'  => __( 'On', 'payment-page' ),
                'label_off' => __( 'Off', 'payment-page' ),
                'default'   => 'yes',
            ] );
        }
        
        $this->elementorWidgetInstance->add_control( 'currency_symbol', [
            'label'     => __( 'Currency Symbol', 'payment-page' ),
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'label_on'  => __( 'Symbol', 'payment-page' ),
            'label_off' => __( 'Text', 'payment-page' ),
            'default'   => 'no',
        ] );
        $this->elementorWidgetInstance->end_controls_section();
    }

}