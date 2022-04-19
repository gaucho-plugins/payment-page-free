<?php

namespace PaymentPage\ThirdPartyIntegration;

use  Freemius as Freemius_Class ;
class Freemius
{
    /**
     * @var Freemius_Class|null
     */
    protected static  $_instance = null ;
    public static function instance() : Freemius_Class
    {
        if ( self::$_instance !== null ) {
            return self::$_instance;
        }
        if ( !class_exists( 'fs_dynamic_init' ) ) {
            require_once PAYMENT_PAGE_BASE_PATH . '/lib/freemius/wordpress-sdk/start.php';
        }
        self::$_instance = fs_dynamic_init( array(
            'id'              => PAYMENT_PAGE_FREEMIUS_ID,
            'slug'            => PAYMENT_PAGE_FREEMIUS_SLUG,
            'type'            => 'plugin',
            'public_key'      => PAYMENT_PAGE_FREEMIUS_PUBLIC_KEY,
            'is_premium'      => false,
            'has_addons'      => false,
            'has_paid_plans'  => true,
            'has_affiliation' => 'selected',
            'menu'            => array(
            'slug'       => PAYMENT_PAGE_MENU_SLUG,
            'first-path' => PAYMENT_PAGE_DEFAULT_URL_PATH,
        ),
            'is_live'         => true,
        ) );
        self::$_instance->add_filter( 'plugin_icon', function () {
            return PAYMENT_PAGE_BASE_PATH . '/interface/img/logo.jpg';
        } );
        do_action( 'payment_page_fs_loaded' );
        return self::$_instance;
    }
    
    public static function api_request_details()
    {
        $site = self::instance()->get_site();
        return [
            'site_url'              => get_site_url(),
            'site_rest_url'         => rest_url(),
            'freemius_anonymous_id' => self::instance()->get_anonymous_id(),
            'freemius_site_id'      => ( empty($site) ? 0 : $site->id ),
            'freemius_plan_id'      => ( empty($site) ? 0 : self::instance()->get_plan_id() ),
            'freemius_license_id'   => ( empty($site) ? 0 : self::instance()->_get_license()->id ),
        ];
    }
    
    public static function has_personal_plan() : bool
    {
        $site = self::instance()->get_site();
        return !empty($site) && in_array( intval( self::instance()->get_plan_id() ), [ 11498, 12982 ] );
    }
    
    public static function has_pro_plan() : bool
    {
        $site = self::instance()->get_site();
        return !empty($site) && in_array( intval( self::instance()->get_plan_id() ), [ 11499 ] );
    }
    
    public static function has_agency_plan() : bool
    {
        $site = self::instance()->get_site();
        return !empty($site) && in_array( intval( self::instance()->get_plan_id() ), [ 11500, 11559 ] );
    }

}