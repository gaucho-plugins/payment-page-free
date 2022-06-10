<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Controls;

use  Elementor\Control_Base_Multiple as Elementor_Control_Base_Multiple ;
class PricingControl extends Elementor_Control_Base_Multiple
{
    /**
     * @since 1.0.0
     * @access public
     *        
     * @return string Control type.
     */
    public function get_type()
    {
        return 'pricingplans';
    }
    
    /**
     *
     * Used to register and enqueue custom scripts and styles used by the emoji one
     * area control.
     *
     * @since 1.0.0
     * @access public
     */
    public function enqueue()
    {
        wp_enqueue_style(
            'pricing-control-style',
            plugins_url( 'interface/elementor/controls/pricing/style.css', PAYMENT_PAGE_BASE_FILE_PATH ),
            [],
            PAYMENT_PAGE_VERSION
        );
        wp_register_script(
            'pricing-control',
            plugins_url( 'interface/elementor/controls/pricing/script.js', PAYMENT_PAGE_BASE_FILE_PATH ),
            [ 'jquery' ],
            PAYMENT_PAGE_VERSION
        );
        wp_enqueue_script( 'pricing-control' );
        wp_localize_script( 'pricing-control', 'stripe_data', [
            'FREE_VERSION_MODE' => ( payment_page_fs()->is_free_plan() ? 1 : 0 ),
            'currencies'        => payment_page_elementor_control_pricing_currencies(),
            'frequencies'       => payment_page_elementor_control_pricing_frequencies(),
        ] );
    }
    
    public function get_default_value()
    {
        return payment_page_elementor_control_pricing_default_payment_values();
    }
    
    public function get_value( $control, $settings )
    {
        if ( !isset( $control['default'] ) ) {
            $control['default'] = $this->get_default_value();
        }
        
        if ( isset( $settings[$control['name']] ) ) {
            $value = $settings[$control['name']];
        } else {
            $value = $control['default'];
        }
        
        return $value;
    }
    
    /**
     *
     * Used to generate the control HTML in the editor using Underscore JS
     * template. The variables for the class are available using `data` JS
     * object.
     *
     * @since 1.0.0
     * @access public
     */
    public function content_template()
    {
        $control_uid = $this->get_control_uid();
        ?>
  <div class="elementor-control-field pricing-plan-repeater">
    <label for="<?php 
        echo  esc_attr( $control_uid ) ;
        ?>" class="elementor-control-title">{{{ data.label }}}</label>
    <div class="elementor-control-input-wrapper">
      <div class="fields-repeater-container">
        <div class="field-group field-group-template">
          <div>
              <?php 
        $control_uid_price = $this->get_control_uid( 'price' );
        ?>
            <label> Price <input id="<?php 
        echo  $control_uid_price ;
        ?>" data-setting="price" class="field price-input" type="number" min="1"></label>
          </div>
          <div style="padding: 10px 0;font-weight: 500;font-size: 14px;"><?php 
        echo  __( "If left empty, the customer will be able to enter any amount.", "payment-page" ) ;
        ?></div>
          <?php 
        ?>
          <div>
            <?php 
        $control_uid_currency = $this->get_control_uid( 'currency' );
        ?>
            <label> Currency
                <select id="<?php 
        echo  esc_attr( $control_uid_currency ) ;
        ?>"
                        data-setting="currency"
                        class="field currency-selector">
                  <?php 
        foreach ( payment_page_elementor_control_pricing_currencies() as $currency ) {
            ?>
                    <option value="<?php 
            echo  $currency ;
            ?>"><?php 
            echo  strtoupper( $currency ) ;
            ?></option>
                  <?php 
        }
        ?>
                </select>
            </label>
          </div>
          <?php 
        ?>
            <div style="padding: 10px 0;font-weight: 500;font-size: 14px;"><?php 
        echo  payment_page_admin_upgrade_text_subscription() ;
        ?></div>
          <?php 
        ?>

          <p style="display: none;width: 100%;"><i class="eicon-close remove-price-row" aria-hidden="true" style="cursor:pointer;"></i></p>
        </div>
      </div>
            <div class="elementor-button-wrapper">
        <button
          class="elementor-button elementor-button-default add-more-options"
          type="button">
          <i class="eicon-plus" aria-hidden="true"></i>Add more options
        </button>
      </div>
        </div>
  </div>
  <# if ( data.description ) { #>
    <div class="elementor-control-field-description">{{{ data.description }}}</div>
  <# } #>
		<?php 
    }

}