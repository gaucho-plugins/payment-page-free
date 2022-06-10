<?php

namespace PaymentPage;

use  PaymentPage\Migration\Admin as Payment_Page_Migration_Admin ;
use  PaymentPage\ThirdPartyIntegration\Freemius as FS_Integration ;
class PaymentForm
{
    /**
     * @var null|PaymentForm;
     */
    protected static  $_instance = null ;
    /**
     * @return PaymentForm
     */
    public static function instance() : PaymentForm
    {
        if ( self::$_instance === null ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function get_from_elementor_settings( $settings, $post_id )
    {
        $uniqid = uniqid();
        $stripeIntegration = PaymentGateway::get_integration_from_settings( 'stripe' );
        $paypalIntegration = PaymentGateway::get_integration_from_settings( 'paypal' );
        $payment_methods_map = [
            'stripe' => Settings::instance()->get( 'stripe_payment_methods' ),
            'paypal' => Settings::instance()->get( 'paypal_payment_methods' ),
        ];
        foreach ( $payment_methods_map as $_gateway => $payment_methods ) {
            foreach ( $payment_methods as $payment_method_key => $payment_method ) {
                if ( !isset( $settings['payment_method_' . $_gateway . '_' . $payment_method] ) || $settings['payment_method_' . $_gateway . '_' . $payment_method] !== 'yes' ) {
                    unset( $payment_methods[$payment_method_key] );
                }
            }
            $payment_methods_map[$_gateway] = array_values( $payment_methods );
        }
        foreach ( $settings['form_fields_map'] as $field_key => $field ) {
            if ( isset( $field['is_hidden'] ) ) {
                
                if ( intval( $field['is_hidden'] ) ) {
                    unset( $settings['form_fields_map'][$field_key] );
                } else {
                    unset( $settings['form_fields_map'][$field_key]['is_hidden'] );
                }
            
            }
        }
        $component_args = [
            'uniqid'                      => $uniqid,
            'is_free_version'             => ( payment_page_fs()->is_free_plan() ? 1 : 0 ),
            'currency_symbol'             => ( isset( $settings['currency_symbol'] ) && ($settings['currency_symbol'] === 'no' || empty($settings['currency_symbol'])) ? 0 : 1 ),
            'subscription_selector'       => ( payment_page_fs()->is_free_plan() ? 0 : (( isset( $settings['subscription_selector'] ) && $settings['subscription_selector'] === 'yes' ? 1 : 0 )) ),
            'currency_selector'           => ( payment_page_fs()->is_free_plan() ? 0 : (( isset( $settings['currency_selector'] ) && $settings['currency_selector'] === 'yes' ? 1 : 0 )) ),
            'post_id'                     => $post_id,
            'pricing_plans'               => [],
            'field_map'                   => $settings['form_fields_map'],
            'submit_actions'              => $settings['submit_actions'],
            'submit_trigger_label'        => [
            'token_order' => array_map( 'trim', explode( ",", $settings['sorted_text_button']['sorted'] ) ),
            'token_map'   => [
            'customText1' => $settings['sorted_text_button']['payment_button_text_1'] ?? '',
            'customText2' => $settings['sorted_text_button']['payment_button_text_2'] ?? '',
            'customText3' => $settings['sorted_text_button']['payment_button_text_3'] ?? '',
        ],
        ],
            'pricing_plan_option_label'   => [
            'token_order' => array_map( 'trim', explode( ",", $settings['pricing_selector_label']['sorted'] ) ),
            'token_map'   => [
            'select_field_custom_text'    => $settings['pricing_selector_label']['customText'] ?? '',
            'select_field_separator_text' => $settings['pricing_selector_label']['separatorText'] ?? '',
        ],
        ],
            'lang'                        => [
            'invalid_email_address'                => __( "Valid Email Address required", "payment-page" ),
            'pricing_plan_title'                   => $settings['pricing_selector_section_label'] ?? "CHOOSE YOUR PLAN",
            'payment_information'                  => $settings['form_data_section_label'] ?? "PAYMENT METHOD",
            'payment_method'                       => $settings['payment_method_title'] ?? "PAYMENT METHOD",
            'pricing_custom_amount'                => $settings['custom_pricing_input_section_label'] ?? "Enter your Amount",
            'confirmation_page_title'              => $settings['payment_details_title'] ?? "",
            'confirmation_page_message'            => $settings['success_message'] ?? "",
            'confirmation_page_item'               => __( "Item", "payment-page" ),
            'confirmation_page_customer_name'      => __( "Customer Name", "payment-page" ),
            'confirmation_page_email'              => __( "Email", "payment-page" ),
            'confirmation_page_payment_date'       => __( "Payment Date", "payment-page" ),
            'confirmation_page_payment_amount'     => __( "Payment Amount", "payment-page" ),
            'notification_payment_failed'          => $settings['failure_message'] ?? __( "Payment Failed", "payment-page" ),
            'payment_method_wallet_prerender'      => __( "If the button does not appear, there's no cards detected by the payment wallet.", "payment-page" ),
            'payment_method_wallet_incompatible'   => __( "This Wallet is not compatible with your browser.", "payment-page" ),
            'payment_method_one_time_only_tooltip' => __( "Only supports one-time payments", "payment-page" ),
        ],
            'payment_gateways'            => (( empty($payment_methods_map['stripe']) && empty($payment_methods_map['paypal']) ? [
            'skeleton' => [
            'warning'         => ( current_user_can( PAYMENT_PAGE_ADMIN_CAP ) ? '<div data-payment-page-notification="danger" style="text-align:center !important;">' . __( "No payment gateway connected.", "payment-page" ) . '<br/>' . '<a href="' . esc_url( admin_url( 'admin.php?page=' . PAYMENT_PAGE_MENU_SLUG ) ) . '#payment-gateways" target="_blank">' . __( "Connect a payment gateway >", "payment-page" ) . '</a>' . '</div>' : '<div data-payment-page-notification="danger">' . __( "No payment gateway connected.", "payment-page" ) . '</div>' ),
            'payment_methods' => [ 'skeleton' ],
        ],
        ] : [] )) + (( $stripeIntegration->is_configured() ? [
            'stripe' => [
            'warning'         => ( $stripeIntegration->is_live() ? '' : '<div data-payment-page-notification="warning">' . sprintf( __( "When in TEST mode, use %s 4242 4242 4242 4242 with any exp date and CVV.", "payment-page" ), '<a href="https://stripe.com/docs/testing" target="_blank">' . __( "Stripe card testing details >", "payment-page" ) . '</a>' ) . '</div>' ),
            'publishable_key' => $stripeIntegration->get_public_key(),
            'payment_methods' => $stripeIntegration->get_payment_methods_frontend( $payment_methods_map['stripe'] ),
            'country_code'    => $stripeIntegration->get_account_country_code(),
        ],
        ] : [] )) + (( $paypalIntegration->is_configured() ? [
            'paypal' => [
            'client_id'       => $paypalIntegration->get_client_id(),
            'payment_methods' => $paypalIntegration->get_payment_methods_frontend( $payment_methods_map['paypal'] ),
        ],
        ] : [] )),
            'success_redirect_location'   => ( isset( $settings['redirect_to_url'] ) && isset( $settings['redirect_to_url']['url'] ) ? $settings['redirect_to_url']['url'] : '' ),
            'success_has_payment_details' => ( isset( $settings['payment_details'] ) && $settings['payment_details'] === 'no' ? 0 : 1 ),
        ];
        foreach ( $settings['plans'] as $key => $plan ) {
            if ( !isset( $plan['prices'] ) || !is_array( $plan['prices'] ) || empty($plan['prices']) ) {
                continue;
            }
            foreach ( $plan['prices'] as $price_key => $price ) {
                
                if ( !isset( $price['currency'] ) || empty($price['currency']) ) {
                    unset( $plan['prices'][$price_key] );
                    continue;
                }
                
                if ( !isset( $price['frequency'] ) || $price['frequency'] === 'none' ) {
                    $price['frequency'] = [
                        'value' => 'one-time',
                        'label' => 'One-time',
                    ];
                }
            }
            if ( empty($plan['prices']) ) {
                continue;
            }
            
            if ( payment_page_fs()->is_free_plan() ) {
                $plan['prices'] = [ $plan['prices'][0] ];
                $plan['prices'][0]['frequency']['value'] = 'one-time';
                $plan['prices'][0]['frequency']['label'] = 'One-time';
            }
            
            unset( $plan['_id'] );
            $component_args['pricing_plans'][] = $plan;
        }
        $response = '<link rel="stylesheet" href="https://pagecdn.io/lib/easyfonts/fonts.css">';
        $response .= '<div id="payment-form-' . (( $settings['display_form'] === 'popup' ? 'popup-' : '' )) . $uniqid . '"
                      data-payment-page-component="' . (( $settings['display_form'] === 'popup' ? 'popup-' : '' )) . 'payment-form" 
                      data-payment-page-component-args="' . esc_attr( json_encode( $component_args ) ) . '">...</div>';
        $response .= '<style>
            #payment-form-' . $uniqid . ' {';
        if ( isset( $settings['field_label_color'] ) ) {
            $response .= '--payment-page-element-form-label-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'field_label_color' ) . ';' . "\n";
        }
        if ( isset( $settings['field_input_color'] ) ) {
            $response .= '--payment-page-element-form-input-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'field_input_color' ) . ';' . "\n";
        }
        $response .= $this->_css_variable_form_headings( $settings );
        $response .= $this->_css_variables_pricing_filters( $settings );
        $response .= $this->_css_variable_form_pricing_plan_select( $settings );
        $response .= $this->_css_variable_form_payment_methods( $settings );
        $response .= $this->_css_variables_form_field_container( $settings );
        $response .= $this->_css_variables_form_label( $settings );
        $response .= $this->_css_variables_form_field_input( $settings );
        $response .= $this->_css_variables_form_submit_button( $settings );
        $response .= $this->_css_variable_success_page( $settings );
        $response .= '  }
          </style>';
        
        if ( $settings['display_form'] === 'popup' ) {
            $response .= '<style>
            #payment-form-popup-' . $uniqid . ' {';
            $response .= $this->_css_variable_form_payment_methods( $settings );
            $response .= '  }
          </style>';
        }
        
        return $response;
    }
    
    private function _css_variable_form_pricing_plan_select( $settings )
    {
        $response = '';
        if ( isset( $settings['pricing_plan_select_border_color'] ) && isset( $settings['pricing_plan_select_border_size'] ) ) {
            $response .= '--payment-page-element-form-select-border : ' . payment_page_elementor_setting_to_css_variable_border( $settings['pricing_plan_select_border_size'], $settings['pricing_plan_select_border_color'] ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_border_radius'] ) ) {
            $response .= '--payment-page-element-form-select-border-radius : ' . payment_page_elementor_setting_to_css_variable_border_radius( $settings['pricing_plan_select_border_radius'] ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_arrow_color'] ) ) {
            $response .= '--payment-page-element-form-select-arrow-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'pricing_plan_select_arrow_color' ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_background_color'] ) ) {
            $response .= '--payment-page-element-form-select-background : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'pricing_plan_select_background_color' ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_text_color'] ) ) {
            $response .= '--payment-page-element-form-select-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'pricing_plan_select_text_color' ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_text_font_transform'] ) ) {
            $response .= '--payment-page-element-form-select-text-transform : ' . $settings['pricing_plan_select_text_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_text_font_size'] ) ) {
            $response .= '--payment-page-element-form-select-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['pricing_plan_select_text_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_text_font_weight'] ) ) {
            $response .= '--payment-page-element-form-select-font-weight : ' . $settings['pricing_plan_select_text_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_text_font_family'] ) ) {
            $response .= '--payment-page-element-form-select-font-family : ' . $settings['pricing_plan_select_text_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_option_background_color'] ) ) {
            $response .= '--payment-page-element-form-select-option-background : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'pricing_plan_select_option_background_color' ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_option_active_background_color'] ) ) {
            $response .= '--payment-page-element-form-select-option-active-background : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'pricing_plan_select_option_active_background_color' ) . ';' . "\n";
        }
        return $response;
    }
    
    private function _css_variable_form_payment_methods( $settings )
    {
        $response = '';
        if ( isset( $settings['payment_method_items_per_row'] ) ) {
            $response .= '--payment-page-element-form-payment-method-per-row : ' . $settings['payment_method_items_per_row'] . ';' . "\n";
        }
        if ( isset( $settings['payment_method_item_image_height'] ) ) {
            $response .= '--payment-page-element-form-payment-method-option-image-height : ' . _payment_page_elementor_setting_size_to_css( $settings['payment_method_item_image_height'] ) . ';' . "\n";
        }
        if ( isset( $settings['payment_method_items_spacing'] ) ) {
            $response .= '--payment-page-element-form-payment-method-spacing : ' . _payment_page_elementor_setting_size_to_css( $settings['payment_method_items_spacing'] ) . ';' . "\n";
        }
        if ( isset( $settings['payment_method_section_title_style_color'] ) ) {
            $response .= '--payment-page-element-form-payment-method-title-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_section_title_style_color' ) . ';' . "\n";
        }
        if ( isset( $settings['payment_method_section_title_style_font_family'] ) ) {
            $response .= '--payment-page-element-form-payment-method-title-font-family : ' . $settings['payment_method_section_title_style_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['payment_method_section_title_style_font_size'] ) ) {
            $response .= '--payment-page-element-form-payment-method-title-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['payment_method_section_title_style_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['payment_method_section_title_style_font_weight'] ) ) {
            $response .= '--payment-page-element-form-payment-method-title-font-weight : ' . $settings['payment_method_section_title_style_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['payment_method_section_title_style_font_transform'] ) ) {
            $response .= '--payment-page-element-form-payment-method-title-text-transform : ' . $settings['payment_method_section_title_style_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['global_options_title_margin'] ) ) {
            $response .= '--payment-page-element-form-payment-method-title-margin : ' . payment_page_elementor_setting_to_css_variable_margin( $settings['global_options_title_margin'] ) . ';' . "\n";
        }
        if ( isset( $settings['payment_method_item_inactive_padding'] ) ) {
            $response .= '--payment-page-element-form-payment-method-option-padding : ' . payment_page_elementor_setting_to_css_variable_padding( $settings['payment_method_item_inactive_padding'] ) . ';' . "\n";
        }
        if ( isset( $settings['payment_method_item_inactive_border_radius'] ) ) {
            $response .= '--payment-page-element-form-payment-method-option-border-radius : ' . payment_page_elementor_setting_to_css_variable_border_radius( $settings['payment_method_item_inactive_border_radius'] ) . ';' . "\n";
        }
        
        if ( isset( $settings['payment_method_item_inactive_border_color'] ) && isset( $settings['payment_method_item_inactive_border_size'] ) ) {
            $response .= '--payment-page-element-form-payment-method-option-border-top : ' . payment_page_elementor_setting_to_css_variable_border( [
                'unit' => $settings['payment_method_item_inactive_border_size']['unit'],
                'size' => $settings['payment_method_item_inactive_border_size']['top'],
            ], payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_item_inactive_border_color' ) ) . ';' . "\n";
            $response .= '--payment-page-element-form-payment-method-option-border-right : ' . payment_page_elementor_setting_to_css_variable_border( [
                'unit' => $settings['payment_method_item_inactive_border_size']['unit'],
                'size' => $settings['payment_method_item_inactive_border_size']['right'],
            ], payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_item_inactive_border_color' ) ) . ';' . "\n";
            $response .= '--payment-page-element-form-payment-method-option-border-bottom : ' . payment_page_elementor_setting_to_css_variable_border( [
                'unit' => $settings['payment_method_item_inactive_border_size']['unit'],
                'size' => $settings['payment_method_item_inactive_border_size']['bottom'],
            ], payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_item_inactive_border_color' ) ) . ';' . "\n";
            $response .= '--payment-page-element-form-payment-method-option-border-left : ' . payment_page_elementor_setting_to_css_variable_border( [
                'unit' => $settings['payment_method_item_inactive_border_size']['unit'],
                'size' => $settings['payment_method_item_inactive_border_size']['left'],
            ], payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_item_inactive_border_color' ) ) . ';' . "\n";
            $response .= '--payment-page-element-form-payment-method-option-border-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_item_inactive_border_color' ) . ';' . "\n";
        }
        
        if ( isset( $settings['payment_method_item_active_padding'] ) ) {
            $response .= '--payment-page-element-form-payment-method-option-active-padding : ' . payment_page_elementor_setting_to_css_variable_padding( $settings['payment_method_item_active_padding'] ) . ';' . "\n";
        }
        if ( isset( $settings['payment_method_item_active_border_radius'] ) ) {
            $response .= '--payment-page-element-form-payment-method-option-active-border-radius : ' . payment_page_elementor_setting_to_css_variable_border_radius( $settings['payment_method_item_active_border_radius'] ) . ';' . "\n";
        }
        
        if ( isset( $settings['payment_method_item_active_border_color'] ) && isset( $settings['payment_method_item_active_border_size'] ) ) {
            $response .= '--payment-page-element-form-payment-method-option-active-border-top : ' . payment_page_elementor_setting_to_css_variable_border( [
                'unit' => $settings['payment_method_item_active_border_size']['unit'],
                'size' => $settings['payment_method_item_active_border_size']['top'],
            ], payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_item_active_border_color' ) ) . ';' . "\n";
            $response .= '--payment-page-element-form-payment-method-option-active-border-right : ' . payment_page_elementor_setting_to_css_variable_border( [
                'unit' => $settings['payment_method_item_active_border_size']['unit'],
                'size' => $settings['payment_method_item_active_border_size']['right'],
            ], payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_item_active_border_color' ) ) . ';' . "\n";
            $response .= '--payment-page-element-form-payment-method-option-active-border-bottom : ' . payment_page_elementor_setting_to_css_variable_border( [
                'unit' => $settings['payment_method_item_active_border_size']['unit'],
                'size' => $settings['payment_method_item_active_border_size']['bottom'],
            ], payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_item_active_border_color' ) ) . ';' . "\n";
            $response .= '--payment-page-element-form-payment-method-option-active-border-left : ' . payment_page_elementor_setting_to_css_variable_border( [
                'unit' => $settings['payment_method_item_active_border_size']['unit'],
                'size' => $settings['payment_method_item_active_border_size']['left'],
            ], payment_page_elementor_setting_to_css_variable_color( $settings, 'payment_method_item_active_border_color' ) ) . ';' . "\n";
        }
        
        return $response;
    }
    
    private function _css_variable_form_headings( $settings )
    {
        $response = '';
        if ( isset( $settings['pricing_plan_select_title_color'] ) ) {
            $response .= '--payment-page-element-form-pricing-plans-title-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'pricing_plan_select_title_color' ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_title_font_family'] ) ) {
            $response .= '--payment-page-element-form-pricing-plans-title-font-family : ' . $settings['pricing_plan_select_title_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_title_font_size'] ) ) {
            $response .= '--payment-page-element-form-pricing-plans-title-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['pricing_plan_select_title_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_title_font_weight'] ) ) {
            $response .= '--payment-page-element-form-pricing-plans-title-font-weight : ' . $settings['pricing_plan_select_title_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_select_title_font_transform'] ) ) {
            $response .= '--payment-page-element-form-pricing-plans-title-text-transform : ' . $settings['pricing_plan_select_title_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['global_options_title_margin'] ) ) {
            $response .= '--payment-page-element-form-pricing-plans-title-margin : ' . payment_page_elementor_setting_to_css_variable_margin( $settings['global_options_title_margin'] ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_custom_amount_color'] ) ) {
            $response .= '--payment-page-element-form-custom-amount-title-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'pricing_plan_custom_amount_color' ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_custom_amount_font_family'] ) ) {
            $response .= '--payment-page-element-form-custom-amount-title-font-family : ' . $settings['pricing_plan_custom_amount_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_custom_amount_font_size'] ) ) {
            $response .= '--payment-page-element-form-custom-amount-title-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['pricing_plan_custom_amount_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_custom_amount_font_weight'] ) ) {
            $response .= '--payment-page-element-form-custom-amount-title-font-weight : ' . $settings['pricing_plan_custom_amount_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['pricing_plan_custom_amount_font_transform'] ) ) {
            $response .= '--payment-page-element-form-custom-amount-title-text-transform : ' . $settings['pricing_plan_custom_amount_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['global_options_title_margin'] ) ) {
            $response .= '--payment-page-element-form-custom-amount-title-margin : ' . payment_page_elementor_setting_to_css_variable_margin( $settings['global_options_title_margin'] ) . ';' . "\n";
        }
        if ( isset( $settings['form_section_title_style_color'] ) ) {
            $response .= '--payment-page-element-form-payment-information-title-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'form_section_title_style_color' ) . ';' . "\n";
        }
        if ( isset( $settings['form_section_title_style_font_family'] ) ) {
            $response .= '--payment-page-element-form-payment-information-title-font-family : ' . $settings['form_section_title_style_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['form_section_title_style_font_size'] ) ) {
            $response .= '--payment-page-element-form-payment-information-title-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['form_section_title_style_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['form_section_title_style_font_weight'] ) ) {
            $response .= '--payment-page-element-form-payment-information-title-font-weight : ' . $settings['form_section_title_style_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['form_section_title_style_font_transform'] ) ) {
            $response .= '--payment-page-element-form-payment-information-title-text-transform : ' . $settings['form_section_title_style_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['global_options_title_margin'] ) ) {
            $response .= '--payment-page-element-form-payment-information-title-margin : ' . payment_page_elementor_setting_to_css_variable_margin( $settings['global_options_title_margin'] ) . ';' . "\n";
        }
        return $response;
    }
    
    private function _css_variables_form_label( $settings )
    {
        $response = '';
        if ( isset( $settings['form_field_label_color'] ) ) {
            $response .= '--payment-page-element-form-label-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'form_field_label_color' ) . ';' . "\n";
        }
        if ( isset( $settings['form_field_label_font_family'] ) ) {
            $response .= '--payment-page-element-form-label-font-family : ' . $settings['form_field_label_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_label_font_size'] ) ) {
            $response .= '--payment-page-element-form-label-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['form_field_label_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['form_field_label_font_weight'] ) ) {
            $response .= '--payment-page-element-form-label-font-weight : ' . $settings['form_field_label_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_label_font_transform'] ) ) {
            $response .= '--payment-page-element-form-label-text-transform : ' . $settings['form_field_label_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_label_active_color'] ) ) {
            $response .= '--payment-page-element-form-label-active-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'form_field_label_active_color' ) . ';' . "\n";
        }
        if ( isset( $settings['form_field_label_active_font_family'] ) ) {
            $response .= '--payment-page-element-form-label-active-font-family : ' . $settings['form_field_label_active_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_label_active_font_size'] ) ) {
            $response .= '--payment-page-element-form-label-active-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['form_field_label_active_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['form_field_label_active_font_weight'] ) ) {
            $response .= '--payment-page-element-form-label-active-font-weight : ' . $settings['form_field_label_active_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_label_active_font_transform'] ) ) {
            $response .= '--payment-page-element-form-label-active-text-transform : ' . $settings['form_field_label_active_font_transform'] . ';' . "\n";
        }
        return $response;
    }
    
    private function _css_variables_form_field_input( $settings )
    {
        $response = '';
        if ( isset( $settings['form_field_input_color'] ) ) {
            $response .= '--payment-page-element-form-input-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'form_field_input_color' ) . ';' . "\n";
        }
        if ( isset( $settings['form_field_input_font_family'] ) ) {
            $response .= '--payment-page-element-form-input-font-family : ' . $settings['form_field_input_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_input_font_size'] ) ) {
            $response .= '--payment-page-element-form-input-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['form_field_input_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['form_field_input_font_weight'] ) ) {
            $response .= '--payment-page-element-form-input-font-weight : ' . $settings['form_field_input_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_input_font_transform'] ) ) {
            $response .= '--payment-page-element-form-input-text-transform : ' . $settings['form_field_input_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_input_placeholder_color'] ) ) {
            $response .= '--payment-page-element-form-input-placeholder-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'form_field_input_placeholder_color' ) . ';' . "\n";
        }
        if ( isset( $settings['form_field_input_placeholder_font_family'] ) ) {
            $response .= '--payment-page-element-form-input-placeholder-font-family : ' . $settings['form_field_input_placeholder_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_input_placeholder_font_size'] ) ) {
            $response .= '--payment-page-element-form-input-placeholder-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['form_field_input_placeholder_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['form_field_input_placeholder_font_weight'] ) ) {
            $response .= '--payment-page-element-form-input-placeholder-font-weight : ' . $settings['form_field_input_placeholder_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['form_field_input_placeholder_font_transform'] ) ) {
            $response .= '--payment-page-element-form-input-placeholder-text-transform : ' . $settings['form_field_input_placeholder_font_transform'] . ';' . "\n";
        }
        return $response;
    }
    
    private function _css_variables_form_field_container( $settings )
    {
        $response = '';
        
        if ( isset( $settings['form_fields_border_color'] ) && isset( $settings['form_fields_border_size'] ) ) {
            $response .= '--payment-page-element-form-input-container-border : ' . payment_page_elementor_setting_to_css_variable_border( $settings['form_fields_border_size'], payment_page_elementor_setting_to_css_variable_color( $settings, 'form_fields_border_color' ) ) . ';' . "\n";
            $response .= '--payment-page-element-form-input-container-border-size: ' . _payment_page_elementor_setting_size_to_css( $settings['form_fields_border_size'] ) . ';' . "\n";
        }
        
        if ( isset( $settings['form_fields_border_radius'] ) ) {
            $response .= '--payment-page-element-form-input-container-border-radius : ' . payment_page_elementor_setting_to_css_variable_border_radius( $settings['form_fields_border_radius'] ) . ';' . "\n";
        }
        if ( isset( $settings['form_fields_background_color'] ) ) {
            $response .= '--payment-page-element-form-input-container-background : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'form_fields_background_color' ) . ';' . "\n";
        }
        return $response;
    }
    
    private function _css_variables_pricing_filters( $settings )
    {
        $response = '';
        //// Container
        if ( isset( $settings['switcher_button_selector_border_color'] ) && isset( $settings['switcher_button_selector_border_size'] ) ) {
            $response .= '--payment-page-element-pricing-filter-container-border : ' . payment_page_elementor_setting_to_css_variable_border( $settings['switcher_button_selector_border_size'], payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_button_selector_border_color' ) ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_button_selector_border_radius'] ) ) {
            $response .= '--payment-page-element-pricing-filter-container-border-radius : ' . payment_page_elementor_setting_to_css_variable_border_radius( $settings['switcher_button_selector_border_radius'] ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_button_selector_padding'] ) ) {
            $response .= '--payment-page-element-pricing-filter-container-padding : ' . payment_page_elementor_setting_to_css_variable_padding( $settings['switcher_button_selector_padding'] ) . ';' . "\n";
        }
        //// Triggers
        if ( isset( $settings['switcher_text_font_family'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-font-family : ' . $settings['switcher_text_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['switcher_text_font_size'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['switcher_text_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_text_font_weight'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-font-weight : ' . $settings['switcher_text_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['switcher_text_font_transform'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-text-transform : ' . $settings['switcher_text_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['switcher_button_background_color'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-background : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_button_background_color' ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_text_inactive_background_color'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_text_inactive_background_color' ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_button_padding'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-padding : ' . payment_page_elementor_setting_to_css_variable_padding( $settings['switcher_button_padding'] ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_button_border_color'] ) && isset( $settings['switcher_button_border_size'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-border : ' . payment_page_elementor_setting_to_css_variable_border( $settings['switcher_button_border_size'], payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_button_border_color' ) ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_button_border_radius'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-border-radius : ' . payment_page_elementor_setting_to_css_variable_border_radius( $settings['switcher_button_border_radius'] ) . ';' . "\n";
        }
        //// Active Trigger
        if ( isset( $settings['switcher_text_active_background_color'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-active-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_text_active_background_color' ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_button_active_background_color'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-active-background : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_button_active_background_color' ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_button_active_border_color'] ) && isset( $settings['switcher_button_active_border_size'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-active-border : ' . payment_page_elementor_setting_to_css_variable_border( $settings['switcher_button_active_border_size'], $settings['switcher_button_active_border_color'] ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_button_active_border_radius'] ) ) {
            $response .= '--payment-page-element-pricing-filter-trigger-active-border-radius : ' . payment_page_elementor_setting_to_css_variable_border_radius( $settings['switcher_button_active_border_radius'] ) . ';' . "\n";
        }
        //// Select
        if ( isset( $settings['switcher_select_background_color'] ) ) {
            $response .= '--payment-page-element-pricing-filter-select-background : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_select_background_color' ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_select_border_color'] ) && isset( $settings['switcher_select_border_size'] ) ) {
            $response .= '--payment-page-element-pricing-filter-select-border : ' . payment_page_elementor_setting_to_css_variable_border( $settings['switcher_select_border_size'], payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_select_border_color' ) ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_select_border_radius'] ) ) {
            $response .= '--payment-page-element-pricing-filter-select-border-radius : ' . payment_page_elementor_setting_to_css_variable_border_radius( $settings['switcher_select_border_radius'] ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_select_color'] ) ) {
            $response .= '--payment-page-element-pricing-filter-select-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_select_color' ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_select_font_size'] ) ) {
            $response .= '--payment-page-element-pricing-filter-select-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['switcher_select_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['switcher_select_font_family'] ) ) {
            $response .= '--payment-page-element-pricing-filter-select-font-family : ' . $settings['switcher_select_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['switcher_select_font_weight'] ) ) {
            $response .= '--payment-page-element-pricing-filter-select-font-weight : ' . $settings['switcher_select_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['switcher_select_font_transform'] ) ) {
            $response .= '--payment-page-element-pricing-filter-select-text-transform : ' . $settings['switcher_select_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['switcher_select_arrow_color'] ) ) {
            $response .= '--payment-page-element-pricing-filter-select-arrow-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'switcher_select_arrow_color' ) . ';' . "\n";
        }
        return $response;
    }
    
    private function _css_variables_form_submit_button( $settings )
    {
        $response = '';
        if ( isset( $settings['submit_button_background_color'] ) ) {
            $response .= '--payment-page-element-form-submit-background : ' . $settings['submit_button_background_color'] . ';' . "\n";
        }
        if ( isset( $settings['submit_button_text_color'] ) ) {
            $response .= '--payment-page-element-form-submit-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, 'submit_button_text_color' ) . ';' . "\n";
        }
        if ( isset( $settings['submit_button_text_font_size'] ) ) {
            $response .= '--payment-page-element-form-submit-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['submit_button_text_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['submit_button_text_font_family'] ) ) {
            $response .= '--payment-page-element-form-submit-font-family : ' . $settings['submit_button_text_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['submit_button_text_font_weight'] ) ) {
            $response .= '--payment-page-element-form-submit-font-weight : ' . $settings['submit_button_text_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['submit_button_text_font_transform'] ) ) {
            $response .= '--payment-page-element-form-submit-text-transform : ' . $settings['submit_button_text_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['submit_button_padding'] ) ) {
            $response .= '--payment-page-element-form-submit-padding : ' . payment_page_elementor_setting_to_css_variable_padding( $settings['submit_button_padding'] ) . ';' . "\n";
        }
        if ( isset( $settings['submit_button_border_color'] ) && isset( $settings['submit_button_border_size'] ) ) {
            $response .= '--payment-page-element-form-submit-border : ' . payment_page_elementor_setting_to_css_variable_border( $settings['submit_button_border_size'], $settings['submit_button_border_color'] ) . ';' . "\n";
        }
        if ( isset( $settings['submit_button_border_radius'] ) ) {
            $response .= '--payment-page-element-form-submit-border-radius : ' . payment_page_elementor_setting_to_css_variable_border_radius( $settings['submit_button_border_radius'] ) . ';' . "\n";
        }
        if ( isset( $settings['submit_button_box_shadow'] ) && $settings['submit_button_box_shadow'] === 'yes' ) {
            $response .= '--payment-page-element-form-submit-box-shadow : ' . payment_page_elementor_setting_to_css_variable_box_shadow(
                $settings['submit_button_box_shadow_horizontal'],
                $settings['submit_button_box_shadow_vertical'],
                $settings['submit_button_box_shadow_blur'],
                $settings['submit_button_box_shadow_spread'],
                $settings['submit_button_box_shadow_color'],
                $settings['submit_button_box_shadow_position']
            ) . ';' . "\n";
        }
        if ( isset( $settings['submit_button_margin_top'] ) ) {
            $response .= '--payment-page-element-form-submit-spacing-top : ' . payment_page_elementor_setting_to_css_variable_margin( $settings['submit_button_margin_top'] ) . ';' . "\n";
        }
        return $response;
    }
    
    private function _css_variable_success_page( $settings )
    {
        $response = '';
        if ( isset( $settings['_payment_success_title_color'] ) ) {
            $response .= '--payment-page-success-title-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, '_payment_success_title_color' ) . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_title_font_family'] ) ) {
            $response .= '--payment-page-success-title-font-family : ' . $settings['_payment_success_title_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_title_font_size'] ) ) {
            $response .= '--payment-page-success-title-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['_payment_success_title_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_title_font_weight'] ) ) {
            $response .= '--payment-page-success-title-font-weight : ' . $settings['_payment_success_title_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_title_font_transform'] ) ) {
            $response .= '--payment-page-success-title-text-transform : ' . $settings['_payment_success_title_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_content_color'] ) ) {
            $response .= '--payment-page-success-message-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, '_payment_success_content_color' ) . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_content_font_family'] ) ) {
            $response .= '--payment-page-success-message-font-family : ' . $settings['_payment_success_content_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_content_font_size'] ) ) {
            $response .= '--payment-page-success-message-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['_payment_success_content_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_content_font_weight'] ) ) {
            $response .= '--payment-page-success-message-font-weight : ' . $settings['_payment_success_content_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_content_font_transform'] ) ) {
            $response .= '--payment-page-success-message-text-transform : ' . $settings['_payment_success_content_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_label_color'] ) ) {
            $response .= '--payment-page-success-payment-detail-label-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, '_payment_success_details_label_color' ) . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_label_font_family'] ) ) {
            $response .= '--payment-page-success-payment-detail-label-font-family : ' . $settings['_payment_success_details_label_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_label_font_size'] ) ) {
            $response .= '--payment-page-success-payment-detail-label-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['_payment_success_details_label_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_label_font_weight'] ) ) {
            $response .= '--payment-page-success-payment-detail-label-font-weight : ' . $settings['_payment_success_details_label_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_label_font_transform'] ) ) {
            $response .= '--payment-page-success-payment-detail-label-text-transform : ' . $settings['_payment_success_details_label_font_transform'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_color'] ) ) {
            $response .= '--payment-page-success-payment-detail-color : ' . payment_page_elementor_setting_to_css_variable_color( $settings, '_payment_success_details_color' ) . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_font_family'] ) ) {
            $response .= '--payment-page-success-payment-detail-font-family : ' . $settings['_payment_success_details_font_family'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_font_size'] ) ) {
            $response .= '--payment-page-success-payment-detail-font-size : ' . payment_page_elementor_setting_to_css_variable_font_size( $settings['_payment_success_details_font_size'] ) . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_font_weight'] ) ) {
            $response .= '--payment-page-success-payment-detail-font-weight : ' . $settings['_payment_success_details_font_weight'] . ';' . "\n";
        }
        if ( isset( $settings['_payment_success_details_font_transform'] ) ) {
            $response .= '--payment-page-success-payment-detail-text-transform : ' . $settings['_payment_success_details_font_transform'] . ';' . "\n";
        }
        return $response;
    }

}