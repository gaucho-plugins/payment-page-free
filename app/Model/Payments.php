<?php

namespace PaymentPage\Model;

class Payments extends Skeleton {

  public static $table = PAYMENT_PAGE_TABLE_PAYMENTS;
  public static $fields = [
    'post_id'               => 'int',
    'user_id'               => 'int',
    'email_address'         => 'string',
    'first_name'            => 'string',
    'last_name'             => 'string',
    'payment_gateway'       => 'string',
    'payment_method'        => 'string',
    'metadata_json'         => 'json',
    'amount'                => 'int',
    'amount_received'       => 'int',
    'currency'              => 'string',
    'is_paid'               => 'int',
    'is_live'               => 'int',
  ];
  public static $identifier = false;
  public static $timestamps = [ 'created_at', 'updated_at' ];

  public $post_id           = 0;
  public $user_id           = 0;
  public $email_address     = '';
  public $first_name        = '';
  public $last_name         = '';
  public $payment_gateway   = '';
  public $payment_method    = '';
  public $metadata_json     = [];
  public $amount            = 0;
  public $amount_received   = 0;
  public $currency          = '';
  public $is_paid           = 0;
  public $is_live           = 0;

}


