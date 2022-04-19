<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Controls;

use  Elementor\Control_Base_Multiple as Elementor_Control_Base_Multiple ;
use  PaymentPage\PaymentGateway ;
class FormFields extends Elementor_Control_Base_Multiple
{
    public function get_type()
    {
        return 'payment_page_form_fields';
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
            'payment-page-elementor-form-fields',
            plugins_url( 'interface/elementor/controls/form-fields/style.css', PAYMENT_PAGE_BASE_FILE_PATH ),
            [],
            PAYMENT_PAGE_VERSION
        );
        wp_register_script(
            'payment-page-elementor-form-fields',
            plugins_url( 'interface/elementor/controls/form-fields/script.js', PAYMENT_PAGE_BASE_FILE_PATH ),
            [ 'jquery', 'jquery-ui-sortable' ],
            PAYMENT_PAGE_VERSION
        );
        wp_enqueue_script( 'payment-page-elementor-form-fields' );
    }
    
    private function get_core_fields()
    {
        $fields = [
            "email_address" => [
            'label'       => 'Email Address',
            'placeholder' => 'Email Address',
            'name'        => 'email_address',
            'order'       => 1,
            'type'        => 'core',
            'size'        => 6,
            'size_mobile' => 0,
        ],
            "first_name"    => [
            'label'       => 'First Name',
            'placeholder' => 'First Name',
            'name'        => 'first_name',
            'order'       => 2,
            'type'        => 'core',
            'size'        => 3,
            'size_mobile' => 0,
        ],
            "last_name"     => [
            'label'       => 'Last Name',
            'placeholder' => 'Last Name',
            'name'        => 'last_name',
            'type'        => 'core',
            'size'        => 3,
            'size_mobile' => 0,
            'order'       => 3,
        ],
        ];
        if ( PaymentGateway::get_integration( 'stripe' )->is_configured() && in_array( 'ccard', payment_page_setting_get( 'stripe_payment_methods' ) ) ) {
            $fields += [
                "card_number"          => [
                'label'       => 'Card Number',
                'name'        => 'card_number',
                'type'        => 'payment_method_card',
                'size'        => 6,
                'size_mobile' => 0,
                'order'       => 4,
            ],
                "card_expiration_date" => [
                'label'       => 'Expiration Date',
                'name'        => 'card_expiration_date',
                'type'        => 'payment_method_card',
                'size'        => 2,
                'size_mobile' => 0,
                'order'       => 5,
            ],
                "card_cvc"             => [
                'label'       => 'CVC',
                'name'        => 'card_cvc',
                'type'        => 'payment_method_card',
                'size'        => 2,
                'size_mobile' => 0,
                'order'       => 6,
            ],
                "card_zip_code"        => [
                'label'       => 'ZIP',
                'name'        => 'card_zip_code',
                'type'        => 'payment_method_card',
                'size'        => 2,
                'size_mobile' => 0,
                'order'       => 7,
            ],
            ];
        }
        if ( PaymentGateway::get_integration( 'stripe' )->is_configured() && in_array( 'sepa', payment_page_setting_get( 'stripe_payment_methods' ) ) ) {
            $fields += [
                "iban" => [
                'label'       => 'IBAN',
                'name'        => 'iban',
                'type'        => 'payment_method_iban',
                'size'        => 6,
                'size_mobile' => 0,
                'order'       => 7,
            ],
            ];
        }
        return $fields;
    }
    
    public function get_default_value()
    {
        return $this->get_core_fields();
    }
    
    public function get_value( $control, $settings )
    {
        $core_fields = $this->get_core_fields();
        return ($settings[$control['name']] ?? []) + $core_fields;
    }
    
    public function content_template()
    {
        $control_uid = $this->get_control_uid();
        ?>
    <div class="elementor-control-field payment-page-form-fields-container">
      <label for="<?php 
        echo  esc_attr( $control_uid ) ;
        ?>" class="elementor-control-title">{{{ data.label }}}</label>
      <div class="fields-repeater-container">
        <div class="field-group field-group-template">
          <div class="payment-page-draggable-container"><i class="payment-page-draggable eicon-handle"></i></div>
          <div class="information">
            <input data-setting="key" type="hidden">
            <input data-setting="type" type="hidden">
            <input data-setting="order" type="hidden">
            <div data-setting-container="label">
              <label><span><?php 
        echo  __( "Label", "payment-page" ) ;
        ?></span> <input data-setting="label" class="field" type="text"></label>
            </div>
            <div data-setting-container="placeholder">
              <label><span><?php 
        echo  __( "Placeholder", "payment-page" ) ;
        ?></span> <input data-setting="placeholder" class="field" type="text"></label>
            </div>
            <div data-setting-container="is_required">
              <label><span><?php 
        echo  __( "Is Required", "payment-page" ) ;
        ?></span> <input data-setting="is_required" class="field" type="checkbox"></label>
            </div>
            <div data-setting-container="size">
              <label>
                <?php 
        echo  __( "Width", "payment-page" ) ;
        ?>
                <select data-setting="size">
                  <?php 
        for ( $i = 1 ;  $i <= 6 ;  $i++ ) {
            ?>
                    <option value="<?php 
            echo  $i ;
            ?>"><?php 
            echo  $i . '/6' ;
            ?></option>
                  <?php 
        }
        ?>
                </select>
              </label>
            </div>
            <div data-setting-container="size_mobile">
              <label>
                <?php 
        echo  __( "Size Mobile", "payment-page" ) ;
        ?>
                <select data-setting="size_mobile">
                  <option value="0">Inherit</option>
                  <?php 
        for ( $i = 1 ;  $i <= 6 ;  $i++ ) {
            ?>
                    <option value="<?php 
            echo  $i ;
            ?>"><?php 
            echo  $i . '/6' ;
            ?></option>
                  <?php 
        }
        ?>
                </select>
              </label>
            </div>
            <p class="remove-field-container"><i class="eicon-close remove-field" aria-hidden="true"></i></p>
          </div>
        </div>
      </div>
      <?php 
        ?>
    </div>
    <# if ( data.description ) { #>
    <div class="elementor-control-field-description">{{{ data.description }}}</div>
    <# } #>
    <?php 
    }

}