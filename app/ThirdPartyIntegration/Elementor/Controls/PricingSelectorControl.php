<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Controls;

use  Elementor\Control_Base_Multiple as Elementor_Control_Base_Multiple ;
class PricingSelectorControl extends Elementor_Control_Base_Multiple
{
    public function get_type()
    {
        return 'pricing_selector_data';
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
            'pricingSelector-control',
            plugins_url( 'interface/elementor/controls/pricing-selector/style.css', PAYMENT_PAGE_BASE_FILE_PATH ),
            [],
            PAYMENT_PAGE_VERSION
        );
        wp_register_script(
            'pricingSelector-control',
            plugins_url( 'interface/elementor/controls/pricing-selector/script.js', PAYMENT_PAGE_BASE_FILE_PATH ),
            [ 'jquery', 'jquery-ui-sortable' ],
            PAYMENT_PAGE_VERSION
        );
        wp_enqueue_script( 'pricingSelector-control' );
    }
    
    public function get_default_value()
    {
        $default_values = [
            'select_field_plan_name',
            'select_field_separator_text',
            'select_field_plan_price_currency',
            'select_field_plan_price',
            'select_field_custom_text'
        ];
        return [
            "sorted"        => implode( ',', $default_values ),
            "customText"    => '/',
            "separatorText" => '-',
        ];
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
<div
	class="elementor-control elementor-label-block elementor-control-separator-default">
	<div class="elementor-control-content">
		<div class="elementor-control-field">
			<label class="elementor-control-title"
				for="<?php 
        echo  esc_attr( $control_uid ) ;
        ?>"
				class="elementor-control-title">{{{ data.label }}}</label>
			<div class="elementor-control-input-wrapper fields-enabled">
				<ul class="fields-list">
					<li>
									<?php 
        $display_plan_name_uid = $this->get_control_uid( 'display_plan_name' );
        ?>
									<input disabled class="display_field" checked type="checkbox"
						data-fieldId="planName" data-fieldLabel="planName"
						data-setting="display_plan_name"><label for="<?php 
        echo  esc_attr( $display_plan_name_uid ) ;
        ?>">Plan name
					</label>
					</li>
					<li>
									<?php 
        $display_currency_uid = $this->get_control_uid( 'display_price' );
        ?>
									<input class="display_field" type="checkbox"
						data-fieldId="select_field_plan_price" data-fieldLabel="Price"
						data-setting="display_price"> <label
						for="<?php 
        echo  esc_attr( $display_currency_uid ) ;
        ?>">Price </label>
					</li>
					<li>
									<?php 
        $display_currency_uid = $this->get_control_uid( 'display_currency' );
        ?>
									<input class="display_field" type="checkbox"
						data-fieldId="select_field_plan_price_currency"
						data-fieldLabel="Currency" data-setting="display_currency"> <label
						for="<?php 
        echo  esc_attr( $display_currency_uid ) ;
        ?>">Currency </label>
					</li>
          <?php 
        ?>
				</ul>
			</div>
		</div>
	</div>
</div>
<div id="pricing-selector-control">
	<div
		class="elementor-control elementor-label-block elementor-control-separator-default">
		<div class="elementor-control-content">
			<div class="elementor-control-field">
						<?php 
        $custom_text_id = $this->get_control_uid( 'custom_text_tag_input' );
        ?>
							<label class="elementor-control-title block"
					for="<?php 
        echo  $custom_text_id ;
        ?>">Custom text</label>
				<div
					class="elementor-control-input-wrapper elementor-control-unit-5 elementor-control-dynamic-switcher-wrapper">
					<input class="tooltip-target elementor-control-tag-area"
						data-fieldId="select_field_custom_text"
						data-fieldLabel="Custom text" type="text"
						id="<?php 
        echo  $custom_text_id ;
        ?>" data-setting="custom_text_tag_input" />
				</div>
			</div>
		</div>
	</div>
	<div
		class="elementor-control elementor-label-block elementor-control-separator-default">
		<div class="elementor-control-content">
			<div class="elementor-control-field">
						<?php 
        $separator_text_id = $this->get_control_uid( 'select_field_separator_text' );
        ?>
							<label class="elementor-control-title block"
					for="<?php 
        echo  $separator_text_id ;
        ?>">Custom Text 2</label>
				<div
					class="elementor-control-input-wrapper elementor-control-unit-5 elementor-control-dynamic-switcher-wrapper">
					<input class="tooltip-target elementor-control-tag-area"
						type="text" id="<?php 
        echo  $separator_text_id ;
        ?>"
						data-setting="select_field_separator_text" />
				</div>
			</div>
		</div>
	</div>
	<div
		class="elementor-control elementor-label-block elementor-control-separator-default">
		<div class="elementor-control-content">
			<div class="elementor-control-field fields-sorted">
						<?php 
        $control_uid_sorted_fields = $this->get_control_uid( 'select_field_sorted_fields_id' );
        ?>
						<label for="<?php 
        echo  esc_attr( $control_uid_sorted_fields ) ;
        ?>"
					class="block elementor-control-title">Drag to reorder </label>
				<div class="elementor-control-input-wrapper">
					<input type="hidden" id="<?php 
        echo  esc_attr( $control_uid_sorted_fields ) ;
        ?>"
						data-setting="select_field_sorted_fields">
					<div class="fields-repeater-container">
						<div class="field-group">
							<div id="plan-fields-sorted-container"
								class="field-input-simulated">
								<div id="select_field_plan_name" class="ui-state-default">Plan name</div>
                <?php 
        ?>
                <div id="select_field_plan_price" class="ui-state-default">Price</div>
                <div id="select_field_plan_price_currency" class="ui-state-default">Currency</div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
		<?php 
    }

}