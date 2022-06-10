<?php

use PaymentPage\PaymentGateway as InstancePaymentGateway;
use PaymentPage\Settings as PaymentPage_Settings;

if( !function_exists( 'payment_page_admin_register_menu' ) ) {

  /**
   * @param $page_title
   * @param $menu_title
   * @param $capability
   * @param $menu_slug
   * @param $function
   * @param int|null $position
   */
  function payment_page_admin_register_menu( $page_title, $menu_title, $capability, $menu_slug, $function, ?int $position = null ) {
    add_submenu_page(
      PAYMENT_PAGE_MENU_SLUG,
      $page_title,
      $menu_title,
      $capability,
      $menu_slug,
      $function,
      $position
    );
  }

}

function _payment_page_stripe_payment_methods_background_setup( $payment_methods = false ) :bool {
  if( $payment_methods === false )
    $payment_methods = PaymentPage_Settings::instance()->get( 'stripe_payment_methods' );

  if( !in_array( 'apple_pay', $payment_methods ) )
    return false;

  $stripeInstanceFromSettings = InstancePaymentGateway::get_integration_from_settings( 'stripe' );
  $option = 'stripe_apple_pay_verification_type_' . ( $stripeInstanceFromSettings->is_live() ? 'live' : 'test' );

  delete_transient( PAYMENT_PAGE_ALIAS . '_stripe_apple_pay_domain' );

  if( PaymentPage_Settings::instance()->get( $option ) === PAYMENT_PAGE_STRIPE_APPLE_PAY_VERIFICATION_TYPE_NONE ) {
    $domain_name = payment_page_domain_name();

    PaymentPage_Settings::instance()->update( [ $option => PAYMENT_PAGE_STRIPE_APPLE_PAY_VERIFICATION_TYPE_AUTO ] );

    try {
      $response = $stripeInstanceFromSettings->stripeClient()->applePayDomains->create( [
        'domain_name' => $domain_name
      ] );
    } catch ( \Exception $e ) {
      PaymentPage_Settings::instance()->update( [
        $option => PAYMENT_PAGE_STRIPE_APPLE_PAY_VERIFICATION_TYPE_AUTO_FAILED
      ] );
    }

    return true;
  }

  return false;
}

function payment_page_admin_upgrade_format( $text ) {
  return '<p style="font-style: normal;font-size: 14px;color: #fff;font-weight:500;">' . $text . '</p>';
}

function payment_page_admin_upgrade_custom_fields() {
  return 'To add custom fields, please <a target="_blank" href="' . payment_page_fs()->get_upgrade_url().'">Upgrade ></a>';
}

function payment_page_admin_upgrade_text_currency() {
  return 'To change the default currency, please <a target="_blank" href="' . payment_page_fs()->get_upgrade_url().'">Upgrade ></a>';
}

function payment_page_admin_upgrade_text_subscription() {
  return 'To accept recurring subscription payments, please <a target="_blank" href="' . payment_page_fs()->get_upgrade_url().'">Upgrade ></a>';
}

function payment_page_admin_upgrade_currency_filter() {
  return 'To allow customers to filter by currency, please <a target="_blank" href="' . payment_page_fs()->get_upgrade_url().'">Upgrade ></a>';
}

function payment_page_admin_upgrade_subscription_frequency_filter() {
  return 'To allow customers to filter by billing frequency (for recurring payments), please <a target="_blank" href="' . payment_page_fs()->get_upgrade_url().'">Upgrade ></a>';
}