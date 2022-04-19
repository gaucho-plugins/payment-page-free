<?php

namespace PaymentPage\ThirdPartyIntegration\Elementor\Controls;

use Elementor\Control_Switcher as Control_Switcher;

class DisabledSwitcher extends Control_Switcher {

  /**
   * Get switcher control type.
   *
   * Retrieve the control type, in this case `switcher`.
   *
   * @since 1.0.0
   * @access public
   *
   * @return string Control type.
   */
  public function get_type() {
    return 'switcher_disabled';
  }

  public function content_template() {
    ?>
    <# if( data.label.indexOf( "span" ) === -1 ) { #>
    <div class="elementor-control-field elementor-control-type-switcher" style="opacity: 0.6;cursor: not-allowed;">
      <label for="<?php $this->print_control_uid(); ?>" class="elementor-control-title" style="cursor: not-allowed !important;opacity: 0.6;">{{{ data.label }}}</label>
      <div class="elementor-control-input-wrapper">
    <# } else { #>
    <div class="elementor-control-field elementor-control-type-switcher" style="cursor: not-allowed;">
        <label for="<?php $this->print_control_uid(); ?>" class="elementor-control-title" style="cursor: not-allowed !important;">{{{ data.label }}}</label>
        <div class="elementor-control-input-wrapper" style="opacity:0.6;">
    <# } #>
        <label class="elementor-switch elementor-control-unit-2">
          <input id="<?php $this->print_control_uid(); ?>" type="checkbox" data-setting="{{ data.name }}" class="elementor-switch-input" value="{{ data.return_value }}" disabled="disabled">
          <span class="elementor-switch-label" data-on="{{ data.label_on }}" data-off="{{ data.label_off }}" style="cursor: not-allowed !important;"></span>
          <span class="elementor-switch-handle" style="cursor: not-allowed !important;"></span>
        </label>
      </div>
    </div>
    <# if ( data.description ) { #>
    <div class="elementor-control-field-description">{{{ data.description }}}</div>
    <# } #>
    <?php
  }

}