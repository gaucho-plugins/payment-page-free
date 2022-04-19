<?php

define( "PAYMENT_PAGE_NAME", 'Payment Page' );
define( "PAYMENT_PAGE_ALIAS", 'payment_page' );
define( "PAYMENT_PAGE_PREFIX", 'payment-page' );
define( "PAYMENT_PAGE_MENU_SLUG", 'payment-page' );
define( "PAYMENT_PAGE_DEFAULT_URL_PATH", 'admin.php?page=' . PAYMENT_PAGE_MENU_SLUG );

define( "PAYMENT_PAGE_ADMIN_CAP", 'payment_page_settings' );
define( "PAYMENT_PAGE_REST_API_PREFIX", PAYMENT_PAGE_PREFIX );
define( 'PAYMENT_PAGE_LANGUAGE_DIRECTORY', basename( PAYMENT_PAGE_BASE_PATH ) . '/languages/'  );

define( "PAYMENT_PAGE_STYLE_DEFAULT_FONT_WEIGHT", "normal" );
define( "PAYMENT_PAGE_STYLE_DEFAULT_FONT_FAMILY", "'Open Sans', sans-serif" );

define( "PAYMENT_PAGE_FREEMIUS_ID", "6031" );
define( "PAYMENT_PAGE_FREEMIUS_SLUG", "payment-page" );
define( "PAYMENT_PAGE_FREEMIUS_PUBLIC_KEY", "pk_8cf4a66a0e5efcb9c6c2f3972679d" );

define( "PAYMENT_PAGE_TABLE_STRIPE_CUSTOMERS", 'payment_page_stripe_customers' );
define( "PAYMENT_PAGE_TABLE_STRIPE_PRODUCTS", 'payment_page_stripe_products' );
define( "PAYMENT_PAGE_TABLE_STRIPE_PRICES", 'payment_page_stripe_prices' );
define( "PAYMENT_PAGE_TABLE_LOG", 'payment_page_log' );

define( "PAYMENT_PAGE_STRIPE_APPLE_PAY_VERIFICATION_TYPE_NONE", 'none' );
define( "PAYMENT_PAGE_STRIPE_APPLE_PAY_VERIFICATION_TYPE_MANUAL", 'manual' );
define( "PAYMENT_PAGE_STRIPE_APPLE_PAY_VERIFICATION_TYPE_AUTO", 'auto' );
define( "PAYMENT_PAGE_STRIPE_APPLE_PAY_VERIFICATION_TYPE_AUTO_FAILED", 'auto_failed' );