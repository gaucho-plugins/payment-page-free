<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Widgets\PaymentFormSections;

use Elementor\Controls_Manager;
use PaymentPage\ThirdPartyIntegration\Freemius as TPI_Freemius;

class Upgrade extends Skeleton {

  public function is_enabled() :bool {
    return !TPI_Freemius::has_agency_plan();
  }

  public function attach_controls(){
    $this->elementorWidgetInstance->start_controls_section('pp_upgrade_section', [
      'label' => __( "Upgrade Options", "payment-page" ),
      'tab'   => Controls_Manager::TAB_CONTENT
    ]);

    if( !TPI_Freemius::has_personal_plan() ) {
      $this->elementorWidgetInstance->add_control('pp_personal_plan', [
        'label' => __('Personal Plan', 'payment-page'),
        'type'  => Controls_Manager::RAW_HTML,
        'raw'   => '<ul>
                      <li>' . __( "Recurring subscription payments.", "payment-page" ) . '</li>
                      <li>' . __( "Custom payment amounts.", "payment-page" ) . '</li>
                      <li>' . __( "Subscription frequency selector.", "payment-page" ) . '</li>
                      <li>' . __( "New payment gateways.", "payment-page" ) . '</li>
                      <li>' . __( "10 fully customizable templates.", "payment-page" ) . '</li>
                    </ul>' .
                   '<a target="_blank" class="elementor-button elementor-button-success" href="' . payment_page_fs()->get_upgrade_url().'">Upgrade ></a>'
      ]);
    }

    if( !TPI_Freemius::has_pro_plan() ) {
      $this->elementorWidgetInstance->add_control('pp_pro_plan', [
        'label' => __('Pro Plan (coming soon!)', 'payment-page'),
        'type'  => Controls_Manager::RAW_HTML,
        'raw'   => '<ul>
                      <li>' . __( "WooCommerce integration.", "payment-page" ) . '</li>
                      <li>' . __( "3rd party integrations.", "payment-page" ) . '</li>
                      <li>' . __( "Upsells / bundles.", "payment-page" ) . '</li>
                      <li>' . __( "Cart abandonment recovery.", "payment-page" ) . '</li>
                      <li>' . __( "More customizable templates.", "payment-page" ) . '</li>
                    </ul>'
      ]);
    }

    $this->elementorWidgetInstance->add_control('pp_agency_plan', [
      'label' => __( 'Agency Plan (coming soon!)', 'payment-page'),
      'type'  => Controls_Manager::RAW_HTML,
      'raw'   => '<ul>
                    <li>' . __( "Create a multi-vendor marketplace.", "payment-page" ) . '</li>
                    <li>' . __( "Onboard vendors to your marketplace with Stripe Connect.", "payment-page" ) . '</li>
                    <li>' . __( "Assign each payment form to a different vendor, and charge a marketplace fee.", "payment-page" ) . '</li>
                  </ul>'
    ]);

    $this->elementorWidgetInstance->add_control('pp_roadmap', [
      'type'  => Controls_Manager::RAW_HTML,
      'raw'   => '<p>' .
                    sprintf( __( "Vote for the next feature you want to see on our %s.", "payment-page" ),
                          '<a href="https://roadmap.payment.page/" target="_blank">' . __( 'Roadmap', 'payment-page' ) . '</a>' ) .
                  '</p>
 <style>
  .elementor-control-pp_upgrade_section {
  
  }
  
  .elementor-control-pp_personal_plan .elementor-control-title,
  .elementor-control-pp_pro_plan .elementor-control-title,
  .elementor-control-pp_agency_plan .elementor-control-title {
    font-size : 16px !important;
    margin: 0 0 10px 0;
  }       

  .elementor-control-pp_personal_plan .elementor-control-raw-html a.elementor-button {
    padding: 5px 10px;
  }

  .elementor-control-pp_personal_plan .elementor-control-raw-html ul,
  .elementor-control-pp_pro_plan .elementor-control-raw-html ul,
  .elementor-control-pp_agency_plan .elementor-control-raw-html ul {
    padding : 0 0 0 15px !important;
  }  
  
  .elementor-control-pp_personal_plan .elementor-control-raw-html ul > li,
  .elementor-control-pp_pro_plan .elementor-control-raw-html ul > li,
  .elementor-control-pp_agency_plan .elementor-control-raw-html ul > li {
    font-size : 13px !important;
    list-style-type: inherit !important;
    margin : 0 0 10px 0 !important;
  }        
  
  .elementor-control-pp_roadmap {
  
  }                  
</style>'
    ]);

    $this->elementorWidgetInstance->end_controls_section();
  }

}