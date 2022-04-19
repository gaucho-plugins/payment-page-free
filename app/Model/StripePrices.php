<?php

namespace PaymentPage\Model;

class StripePrices extends Skeleton {

  public static $table = PAYMENT_PAGE_TABLE_STRIPE_PRICES;
  public static $fields = [
    'product_id'         => 'int',
    'stripe_id'          => 'string',
    'stripe_product_id'  => 'string',
    'stripe_account_id'  => 'string',
    'price'              => 'int',
    'currency'           => 'string',
    'frequency'          => 'string',
    'is_live'            => 'int',
  ];
  public static $identifier = [ 'product_id', 'price', 'currency', 'frequency', 'stripe_account_id', 'is_live' ];
  public static $timestamps = [ 'created_at', 'updated_at' ];

  public $product_id        = 0;
  public $stripe_id         = '';
  public $stripe_product_id = '';
  public $stripe_account_id = '';
  public $price             = 0;
  public $currency          = '';
  public $frequency         = '';
  public $is_live           = 0;

}