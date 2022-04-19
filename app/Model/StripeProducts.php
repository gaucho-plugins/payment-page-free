<?php

namespace PaymentPage\Model;

class StripeProducts extends Skeleton {

  public static $table = PAYMENT_PAGE_TABLE_STRIPE_PRODUCTS;
  public static $fields = [
    'title'             => 'string',
    'stripe_id'         => 'string',
    'stripe_account_id' => '',
    'is_live'           => 'int',
  ];
  public static $identifier = [ 'title', 'stripe_account_id', 'is_live' ];
  public static $timestamps = [ 'created_at', 'updated_at' ];

  public $title             = '';
  public $stripe_id         = '';
  public $stripe_account_id = '';
  public $is_live           = 0;

}