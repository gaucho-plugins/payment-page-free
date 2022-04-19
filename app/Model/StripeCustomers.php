<?php

namespace PaymentPage\Model;

class StripeCustomers extends Skeleton {

  public static $table = PAYMENT_PAGE_TABLE_STRIPE_CUSTOMERS;
  public static $fields = [
    'email_address'         => 'string',
    'stripe_id'             => 'string',
    'stripe_account_id'     => 'string',
    'subscription_currency' => 'string',
    'is_live'               => 'int',
  ];
  public static $identifier = [ 'email_address', 'stripe_account_id', 'subscription_currency', 'is_live' ];
  public static $timestamps = [ 'created_at', 'updated_at' ];

  public $email_address         = '';
  public $stripe_id             = '';
  public $stripe_account_id     = '';
  public $subscription_currency = '';
  public $is_live               = 0;

}