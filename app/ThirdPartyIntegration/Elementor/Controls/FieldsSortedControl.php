<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Controls;

use  Elementor\Control_Base_Multiple as Elementor_Control_Base_Multiple ;
/**
 * Pricing emoji one area control.
 *
 * A control for displaying a pricing repeater for plans
 *
 * @since 1.0.0
 */
class FieldsSortedControl extends Elementor_Control_Base_Multiple
{
    /**
     * @since 1.0.0
     * @access public
     *
     * @return string Control type.
     */
    public function get_type()
    {
        return 'fieldssorted';
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
        // Styles
        wp_enqueue_style(
            'fieldssorted-control-style',
            plugins_url( 'interface/elementor/controls/fields-sorted/style.css', PAYMENT_PAGE_BASE_FILE_PATH ),
            [],
            PAYMENT_PAGE_VERSION
        );
        wp_enqueue_style(
            'fieldssorted-control-ui-style',
            '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
            [],
            '3.4.1'
        );
        // Scripts
        wp_register_script(
            'fieldssorted-control',
            plugins_url( 'interface/elementor/controls/fields-sorted/script.js', PAYMENT_PAGE_BASE_FILE_PATH ),
            [ 'jquery', 'jquery-ui-sortable' ],
            '1.0.0'
        );
        wp_enqueue_script( 'fieldssorted-control' );
    }
    
    public function get_default_value()
    {
        $default_fields = [
            'customText1',
            'currency',
            'totalPrice',
            'customText2'
        ];
        return [
            "sorted"                => implode( ',', $default_fields ),
            "payment_button_text"   => '',
            "payment_button_text_1" => 'PAY',
            "payment_button_text_2" => 'NOW',
            "payment_button_text_3" => '',
            "separatorText"         => '',
        ];
    }
    
    /**
     * Render emoji one area control output in the editor.
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
        $control_uid_price = $this->get_control_uid( 'price' );
        $control_uid_custom_text_1 = $this->get_control_uid( 'custom_text_1' );
        $control_uid_custom_text_2 = $this->get_control_uid( 'custom_text_2' );
        $control_uid_custom_text_3 = $this->get_control_uid( 'custom_text_3' );
        ?>
<div class="elementor-control-field  field-group-sorted">
	<label for="<?php 
        echo  $control_uid_custom_text_1 ;
        ?>"
		class="elementor-control-title">Enter custom Text 1:</label>
	<div
		class="elementor-control-input-wrapper elementor-control-unit-5 elementor-control-dynamic-switcher-wrapper">
		<input id="<?php 
        echo  $control_uid_custom_text_1 ;
        ?>" type="text"
			class="tooltip-target elementor-control-tag-area" data-tooltip=""
            data-setting="payment_button_text"
            data-custom-text-number="1"
			placeholder="Type your custom text here" original-title="">
	</div>
</div>

<div class="elementor-control-field  field-group-sorted">
	<label for="<?php 
        echo  $control_uid_custom_text_2 ;
        ?>"
		class="elementor-control-title">Enter custom Text 2:</label>
	<div
		class="elementor-control-input-wrapper elementor-control-unit-5 elementor-control-dynamic-switcher-wrapper">
		<input id="<?php 
        echo  $control_uid_custom_text_2 ;
        ?>" type="text"
			class="tooltip-target elementor-control-tag-area" data-tooltip=""
            data-setting="payment_button_text"
            data-custom-text-number="2"
			placeholder="Type your custom text here" original-title="">
	</div>
</div>

<div class="elementor-control-field  field-group-sorted">
	<label for="<?php 
        echo  $control_uid_custom_text_3 ;
        ?>"
		class="elementor-control-title">Enter custom Text 3:</label>
	<div
		class="elementor-control-input-wrapper elementor-control-unit-5 elementor-control-dynamic-switcher-wrapper">
		<input id="<?php 
        echo  $control_uid_custom_text_3 ;
        ?>" type="text"
			class="tooltip-target elementor-control-tag-area" data-tooltip=""
            data-setting="payment_button_text"
            data-custom-text-number="3"
			placeholder="Type your custom text here" original-title="">
	</div>
</div>

<div class="elementor-control-field  fields-sorted field-group-sorted">
	<label for="<?php 
        echo  esc_attr( $control_uid_price ) ;
        ?>"
		class="elementor-control-title">{{{ data.label }}}</label>
	<div class="elementor-control-input-wrapper">
		<input type="hidden" id="<?php 
        echo  $control_uid_price ;
        ?>"
			data-setting="price">
		<div class="fields-repeater-container">
			<div class="field-group-sorted">
				<div id="fields-sorted-container" class="field-input-simulated">
                    <div id="currency" class="ui-state-default">Currency</div>
					<div id="totalPrice" class="ui-state-default">Price</div>
								<?php 
        ?>
							</div>
			</div>
		</div>
	</div>
</div>
<# if ( data.description ) { #>
<div class="elementor-control-field-description">{{{ data.description
	}}}</div>
<# } #>
		<?php 
    }

}