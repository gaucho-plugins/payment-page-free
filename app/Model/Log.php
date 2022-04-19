<?php

namespace PaymentPage\Model;

class Log extends Skeleton {

  public static $table = PAYMENT_PAGE_TABLE_LOG;
  public static $fields = [
    'post_id'     => 'int',
    'namespace'   => 'string',
    'action'      => 'string',
    'content'     => 'json'
  ];
  public static $identifier = false;
  public static $timestamps = [ 'created_at' ];

  public $post_id   = '';
  public $namespace = '';
  public $action    = '';
  public $content   = [];
}