<?php

use  Elementor\Controls_Manager ;
use  Elementor\Core\Schemes ;
function _payment_page_elementor_setting_size_to_css( $size, $valid_after = '' )
{
    if ( isset( $size['top'] ) && isset( $size['right'] ) && isset( $size['bottom'] ) && isset( $size['left'] ) ) {
        return $size['top'] . $size['unit'] . ' ' . $size['right'] . $size['unit'] . ' ' . $size['bottom'] . $size['unit'] . ' ' . $size['left'] . $size['unit'];
    }
    if ( empty($size['size']) ) {
        return 0;
    }
    return $size['size'] . $size['unit'] . $valid_after;
}

function payment_page_elementor_setting_to_css_variable_border( $size, $color )
{
    return _payment_page_elementor_setting_size_to_css( $size, ' solid ' . $color );
}

function payment_page_elementor_setting_to_css_variable_border_radius( $size )
{
    return _payment_page_elementor_setting_size_to_css( $size );
}

function payment_page_elementor_setting_to_css_variable_box_shadow(
    $horizontal,
    $vertical,
    $blur,
    $spread,
    $color,
    $position
)
{
    return _payment_page_elementor_setting_size_to_css( $horizontal ) . ' ' . _payment_page_elementor_setting_size_to_css( $vertical ) . ' ' . _payment_page_elementor_setting_size_to_css( $blur ) . ' ' . _payment_page_elementor_setting_size_to_css( $spread ) . ' ' . $color . ' ' . $position;
}

function payment_page_elementor_setting_to_css_variable_font_size( $size )
{
    return _payment_page_elementor_setting_size_to_css( $size );
}

function payment_page_elementor_setting_to_css_variable_padding( $size )
{
    return _payment_page_elementor_setting_size_to_css( $size );
}

function payment_page_elementor_setting_to_css_variable_margin( $size )
{
    return _payment_page_elementor_setting_size_to_css( $size );
}

function payment_page_elementor_setting_to_css_variable_color( $settings, $index )
{
    if ( isset( $settings['__globals__'] ) && isset( $settings['__globals__'][$index] ) ) {
        
        if ( strpos( $settings['__globals__'][$index], '?id=' ) !== false ) {
            $identifier = substr( $settings['__globals__'][$index], strpos( $settings['__globals__'][$index], '?id=' ) + 4 );
            return 'var( --e-global-color-' . $identifier . ' )';
        }
    
    }
    if ( isset( $settings[$index] ) ) {
        return $settings[$index];
    }
    return 'inherit';
}

function payment_page_elementor_control_pricing_frequencies() : array
{
    $response = [ [
        'value' => 'one-time',
        'label' => 'One-time',
    ] ];
    return $response;
}

/**
 * This is the US List of Stripe supported currencies, which was previously returned through API call.
 * @return string[]
 */
function payment_page_elementor_control_pricing_currencies() : array
{
    return [
        'usd',
        'aed',
        'afn',
        'all',
        'amd',
        'ang',
        'aoa',
        'ars',
        'aud',
        'awg',
        'azn',
        'bam',
        'bbd',
        'bdt',
        'bgn',
        'bif',
        'bmd',
        'bnd',
        'bob',
        'brl',
        'bsd',
        'bwp',
        'byn',
        'bzd',
        'cad',
        'cdf',
        'chf',
        'clp',
        'cny',
        'cop',
        'crc',
        'cve',
        'czk',
        'djf',
        'dkk',
        'dop',
        'dzd',
        'egp',
        'etb',
        'eur',
        'fjd',
        'fkp',
        'gbp',
        'gel',
        'gip',
        'gmd',
        'gnf',
        'gtq',
        'gyd',
        'hkd',
        'hnl',
        'hrk',
        'htg',
        'huf',
        'idr',
        'ils',
        'inr',
        'isk',
        'jmd',
        'jpy',
        'kes',
        'kgs',
        'khr',
        'kmf',
        'krw',
        'kyd',
        'kzt',
        'lak',
        'lbp',
        'lkr',
        'lrd',
        'lsl',
        'mad',
        'mdl',
        'mga',
        'mkd',
        'mmk',
        'mnt',
        'mop',
        'mro',
        'mur',
        'mvr',
        'mwk',
        'mxn',
        'myr',
        'mzn',
        'nad',
        'ngn',
        'nio',
        'nok',
        'npr',
        'nzd',
        'pab',
        'pen',
        'pgk',
        'php',
        'pkr',
        'pln',
        'pyg',
        'qar',
        'ron',
        'rsd',
        'rub',
        'rwf',
        'sar',
        'sbd',
        'scr',
        'sek',
        'sgd',
        'shp',
        'sll',
        'sos',
        'srd',
        'std',
        'szl',
        'thb',
        'tjs',
        'top',
        'try',
        'ttd',
        'twd',
        'tzs',
        'uah',
        'ugx',
        'uyu',
        'uzs',
        'vnd',
        'vuv',
        'wst',
        'xaf',
        'xcd',
        'xof',
        'xpf',
        'yer',
        'zar',
        'zmw'
    ];
}

function payment_page_elementor_control_pricing_default_payment_values() : array
{
    $response = [ [
        "price"     => 99,
        "currency"  => "usd",
        "frequency" => [
        'value' => 'one-time',
        'label' => 'One-time',
    ],
    ] ];
    return $response;
}

function payment_page_elementor_builder_font_weight_assoc() : array
{
    return [
        ''       => __( "Default", 'payment-page' ),
        'normal' => __( "Normal", 'payment-page' ),
        'bold'   => __( "Bold", 'payment-page' ),
        100      => 100,
        200      => 200,
        300      => 300,
        400      => 400,
        500      => 500,
        600      => 600,
        700      => 700,
        800      => 800,
        900      => 900,
    ];
}

function payment_page_elementor_builder_text_transform_assoc() : array
{
    return [
        ''           => __( 'Default', 'elementor' ),
        'uppercase'  => __( 'Uppercase', 'elementor' ),
        'lowercase'  => _x( 'Lowercase', 'Typography Control', 'elementor' ),
        'capitalize' => _x( 'Capitalize', 'Typography Control', 'elementor' ),
        'none'       => _x( 'Normal', 'Typography Control', 'elementor' ),
    ];
}

if ( !function_exists( 'payment_page_elementor_builder_attach_heading_control' ) ) {
    function payment_page_elementor_builder_attach_heading_control(
        $elementor,
        $control_name,
        $field_name,
        $field_label = null
    )
    {
        $elementor->add_control( $control_name . '_' . $field_name . '_styles_heading', [
            'label'     => __( ( $field_label ? $field_label : 'Field labels' ), 'payment-page' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );
    }

}
if ( !function_exists( 'payment_page_elementor_builder_attach_border_control' ) ) {
    /**
     * @param $elementor
     * @param $control_name
     * @param $field_name
     * @param null $defaults
     */
    function payment_page_elementor_builder_attach_border_control(
        $elementor,
        $control_name,
        $field_name,
        $defaults = null,
        $extended_border = false
    )
    {
        $elementor->add_control( $control_name . '_' . $field_name . '_border_color', [
            'label'   => __( 'Color', 'payment-page' ),
            'type'    => Controls_Manager::COLOR,
            'default' => $defaults['border_color'] ?? '#cec3e6',
            'scheme'  => [
            'type'  => Schemes\Color::get_type(),
            'value' => Schemes\Color::COLOR_1,
        ],
        ] );
        
        if ( $extended_border ) {
            $default_border_radius = $defaults['border_size'] ?? [
                'unit' => 'px',
                'size' => 1,
            ];
            $elementor->add_control( $control_name . '_' . $field_name . '_border_size', [
                'label'      => esc_html__( 'Width', 'elementor' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'default'    => [
                'unit'     => $default_border_radius['unit'],
                'top'      => $default_border_radius['size_top'] ?? $default_border_radius['size'],
                'right'    => $default_border_radius['size_right'] ?? $default_border_radius['size'],
                'bottom'   => $default_border_radius['size_bottom'] ?? $default_border_radius['size'],
                'left'     => $default_border_radius['size_left'] ?? $default_border_radius['size'],
                'isLinked' => ($default_border_radius['size_top'] ?? $default_border_radius['size']) === ($default_border_radius['size_right'] ?? $default_border_radius['size']),
            ],
            ] );
        } else {
            $elementor->add_control( $control_name . '_' . $field_name . '_border_size', [
                'label'      => __( 'Width', 'payment-page' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'default'    => $defaults['border_size'] ?? [
                'unit' => 'px',
                'size' => 1,
            ],
                'range'      => [
                'px' => [
                'min' => 0,
                'max' => 20,
            ],
            ],
                'responsive' => true,
            ] );
        }
        
        $default_border_radius = $defaults['border_radius'] ?? [
            'unit' => 'px',
            'size' => 3,
        ];
        $elementor->add_control( $control_name . '_' . $field_name . '_border_radius', [
            'label'      => esc_html__( 'Border Radius', 'elementor' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%' ],
            'default'    => [
            'unit'     => $default_border_radius['unit'],
            'top'      => $default_border_radius['size_top'] ?? $default_border_radius['size'],
            'right'    => $default_border_radius['size_right'] ?? $default_border_radius['size'],
            'bottom'   => $default_border_radius['size_bottom'] ?? $default_border_radius['size'],
            'left'     => $default_border_radius['size_left'] ?? $default_border_radius['size'],
            'isLinked' => ($default_border_radius['size_top'] ?? $default_border_radius['size']) === ($default_border_radius['size_right'] ?? $default_border_radius['size']),
        ],
        ] );
    }

}
if ( !function_exists( 'payment_page_elementor_builder_attach_background_control' ) ) {
    function payment_page_elementor_builder_attach_background_control(
        $elementor,
        $control_name,
        $field_name,
        $field_label = null,
        $defaults = null
    )
    {
        $elementor->add_control( $control_name . '_' . $field_name . '_background_color', [
            'label'   => ( empty($field_label) ? __( 'Background', 'payment-page' ) : $field_label ),
            'type'    => Controls_Manager::COLOR,
            'default' => ( $defaults ? $defaults : "#ffffff" ),
            'scheme'  => [
            'type'  => Schemes\Color::get_type(),
            'value' => Schemes\Color::COLOR_1,
        ],
        ] );
    }

}
if ( !function_exists( 'payment_page_elementor_builder_attach_arrow_control' ) ) {
    function payment_page_elementor_builder_attach_arrow_control(
        $elementor,
        $control_name,
        $field_name,
        $field_label = null,
        $defaults = null
    )
    {
        $elementor->add_control( $control_name . '_' . $field_name . '_color', [
            'label'   => ( empty($field_label) ? __( 'Background', 'payment-page' ) : $field_label ),
            'type'    => Controls_Manager::COLOR,
            'default' => ( $defaults ? $defaults : "#ffffff" ),
            'scheme'  => [
            'type'  => Schemes\Color::get_type(),
            'value' => Schemes\Color::COLOR_1,
        ],
        ] );
    }

}
if ( !function_exists( 'payment_page_elementor_builder_attach_switch_control' ) ) {
    function payment_page_elementor_builder_attach_switch_control(
        $elementor,
        $control_name,
        $field_name,
        $defaults = null
    )
    {
        $elementor->add_control( $control_name . '_' . $field_name . '_display', [
            'label'     => __( 'On/Off', 'payment-page' ),
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'label_on'  => __( 'On', 'payment-page' ),
            'label_off' => __( 'Off', 'payment-page' ),
            'default'   => ( $defaults ? $defaults : 'yes' ),
        ] );
    }

}
if ( !function_exists( '_payment_page_elementor_builder_attach_spacing_control' ) ) {
    function _payment_page_elementor_builder_attach_spacing_control(
        $elementor,
        $control_name,
        $field_name,
        $field_label = null,
        $default = null,
        $option_key = 'spacing'
    )
    {
        $default_padding = $default ?? [
            'unit' => 'px',
            'size' => 0,
        ];
        $elementor->add_control( $control_name . '_' . $field_name . '_' . $option_key, [
            'label'      => ( empty($field_label) ? __( 'Spacing', 'payment-page' ) : $field_label ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'default'    => [
            'unit'     => $default_padding['unit'],
            'top'      => $default_padding['size_top'] ?? $default_padding['size'],
            'right'    => $default_padding['size_right'] ?? $default_padding['size'],
            'bottom'   => $default_padding['size_bottom'] ?? $default_padding['size'],
            'left'     => $default_padding['size_left'] ?? $default_padding['size'],
            'isLinked' => ($default_padding['size_top'] ?? $default_padding['size']) === ($default_padding['size_right'] ?? $default_padding['size']),
        ],
        ] );
    }

}
if ( !function_exists( 'payment_page_elementor_builder_attach_padding_control' ) ) {
    function payment_page_elementor_builder_attach_padding_control(
        $elementor,
        $control_name,
        $field_name,
        $field_label = null,
        $default = null
    )
    {
        _payment_page_elementor_builder_attach_spacing_control(
            $elementor,
            $control_name,
            $field_name,
            ( empty($field_label) ? __( 'Padding', 'payment-page' ) : $field_label ),
            $default,
            'padding'
        );
    }

}
if ( !function_exists( 'payment_page_elementor_builder_attach_margin_control' ) ) {
    function payment_page_elementor_builder_attach_margin_control(
        $elementor,
        $control_name,
        $field_name,
        $field_label = null,
        $default = null
    )
    {
        _payment_page_elementor_builder_attach_spacing_control(
            $elementor,
            $control_name,
            $field_name,
            ( empty($field_label) ? __( 'Margin', 'payment-page' ) : $field_label ),
            $default,
            'margin'
        );
    }

}
if ( !function_exists( 'payment_page_elementor_builder_attach_color_control' ) ) {
    function payment_page_elementor_builder_attach_color_control(
        $elementor,
        $control_name,
        $field_name,
        $field_label = null,
        $defaults = null
    )
    {
        return $elementor->add_control( $control_name . '_' . $field_name . '_color', [
            'label'   => ( empty($field_label) ? __( 'Text Color', 'elementor' ) : $field_label ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => ( empty($defaults) ? '#8676aa' : $defaults ),
            'scheme'  => [
            'type'  => \Elementor\Core\Schemes\Color::get_type(),
            'value' => \Elementor\Core\Schemes\Color::COLOR_1,
        ],
        ] );
    }

}
if ( !function_exists( 'payment_page_elementor_builder_attach_popover_typography' ) ) {
    function payment_page_elementor_builder_attach_popover_typography(
        $elementor,
        $control_name,
        $field_name,
        $defaults = null
    )
    {
        $elementor->add_control( $control_name . '_' . $field_name . '_typography', [
            'label'        => __( 'Typography', 'payment-page' ),
            'type'         => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            'label_off'    => __( 'Default', 'payment-page' ),
            'label_on'     => __( 'Custom', 'payment-page' ),
            'return_value' => 'yes',
        ] );
        $elementor->start_popover();
        $elementor->add_control( $control_name . '_' . $field_name . '_font_family', [
            'label'   => __( 'Font Family', 'payment-page' ),
            'type'    => \Elementor\Controls_Manager::FONT,
            'default' => $defaults['font_family'] ?? PAYMENT_PAGE_STYLE_DEFAULT_FONT_FAMILY,
        ] );
        $elementor->add_control( $control_name . '_' . $field_name . '_font_size', [
            'label'      => __( 'Size', 'payment-page' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%', 'em' ],
            'default'    => $defaults['font_size'] ?? [
            'unit' => 'px',
            'size' => 15,
        ],
            'range'      => [
            'px' => [
            'min' => 1,
            'max' => 200,
        ],
        ],
            'responsive' => true,
        ] );
        $elementor->add_control( $control_name . '_' . $field_name . '_font_weight', [
            'label'   => _x( 'Weight', 'Typography Control', 'elementor' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => $defaults['font_weight'] ?? PAYMENT_PAGE_STYLE_DEFAULT_FONT_WEIGHT,
            'options' => payment_page_elementor_builder_font_weight_assoc(),
        ] );
        $elementor->add_control( $control_name . '_' . $field_name . '_font_transform', [
            'label'   => _x( 'Transform', 'Typography Control', 'elementor' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => $defaults['font_transform'] ?? 'none',
            'options' => payment_page_elementor_builder_text_transform_assoc(),
        ] );
        $elementor->end_popover();
        return $elementor;
    }

}
if ( !function_exists( 'payment_page_elementor_builder_attach_popover_box_shadow' ) ) {
    function payment_page_elementor_builder_attach_popover_box_shadow(
        $elementor,
        $control_name,
        $field_name,
        $field_label = null
    )
    {
        $elementor->add_control( $control_name . '_' . $field_name . '_box_shadow', [
            'label'        => __( 'Box Shadow', 'payment-page' ),
            'type'         => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            'label_off'    => __( 'Default', 'payment-page' ),
            'label_on'     => __( 'Custom', 'payment-page' ),
            'return_value' => 'yes',
        ] );
        $elementor->start_popover();
        $elementor->add_control( $control_name . '_' . $field_name . '_box_shadow_color', [
            'label'   => __( ( $field_label ? $field_label : 'Color' ), 'elementor' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => 'transparent',
            'scheme'  => [
            'type'  => \Elementor\Core\Schemes\Color::get_type(),
            'value' => \Elementor\Core\Schemes\Color::COLOR_1,
        ],
        ] );
        foreach ( [
            'horizontal',
            'vertical',
            'blur',
            'spread'
        ] as $property ) {
            $elementor->add_control( $control_name . '_' . $field_name . '_box_shadow_' . $property, [
                'label'      => __( ucwords( $property ), 'payment-page' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'em' ],
                'default'    => [
                'unit' => 'px',
                'size' => 0,
            ],
                'range'      => [
                'px' => [
                'min' => -100,
                'max' => 100,
            ],
            ],
                'responsive' => true,
            ] );
        }
        $elementor->add_control( $control_name . '_' . $field_name . '_box_shadow_position', [
            'label'       => __( 'Position', 'elementor-pro' ),
            'type'        => Controls_Manager::SELECT,
            'options'     => [
            'inset'   => 'Inset',
            'outline' => 'Outline',
        ],
            'render_type' => 'none',
            'label_block' => true,
        ] );
        $elementor->end_popover();
        return $elementor;
    }

}