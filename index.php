<?php 
/**
Plugin Name: Zoho CRM Connector
Description: Automatic data synchronizing solution from Woocommerce to ZOHO 
Version: 1.3
Author: Dotsquares
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define('ZOHO_PLUGIN_URL',plugin_dir_url( __FILE__ ));
define('ZOHO_PLUGIN_PATH',plugin_dir_path( __FILE__ ));

define('ZOHO_CLIENT_ID',get_option('zoho_client_id'));
define('ZOHO_CLIENT_SECRET',get_option('zoho_client_secret'));
define('ZOHO_REDIRECT_URL',admin_url('admin-ajax.php').'?action=authzoho');
define('ZOHO_AUTH_URL','https://accounts.zoho.eu/oauth/v2/token');
define('ZOHO_AUTHTOKEN',get_option('wc_zoho_access_token'));
define('ZOHO_DOMAIN_URL','https://www.zohoapis.eu/crm/v2/');

function zoho_options_panel(){
 
	add_menu_page( 'Zoho Woo Sync', 'Zoho Woo Sync', 'manage_options', 'zoho_connect', 'callback_zoho_connect');
	add_action( 'admin_init', 'register_zoho_api_settings' );
}
add_action('admin_menu', 'zoho_options_panel');

function callback_zoho_connect(){
	include('admin/view/connect.php');
}

/* Register option setting */

function register_zoho_api_settings() {
	//register our settings
	register_setting( 'zoho-settings-group', 'zoho_client_id' );
	register_setting( 'zoho-settings-group', 'zoho_client_secret' );
}

/* Zoho authorization */
include('admin/process/auth.php');

if(!is_admin()){
	
	include('lib/process/sent_order_woo_zoho.php');
}

/* Update order status same in zoho sale order */

add_action( 'woocommerce_order_status_changed', 'zoho_woocommerce_order_status_changed_action', 99, 4 );

function zoho_woocommerce_order_status_changed_action($id ,$old_status,$new_status){
	
	
	global $wpdb,$woocommerce;
	
	$order 		   = wc_get_order( $id );
	$order_status  = $order->get_status();
	
	$saleOrderID = 	get_post_meta($id,'_zoho_sale_order_id',true);
	
	$saleStatusarray = custom_woocommerce_get_all_order_statuses();
	
	$saleStatus = $saleStatusarray[$order_status];

	$active_access_token = 1;
	if(strtotime(current_time('mysql')) > get_option('wc_zoho_access_token_expires_on')) 
	{
		$active_access_token = get_refresh_token();
	}
	
	$zohoParam = array('Status' => $saleStatus);
	
	if($active_access_token === 1){
		
		$ZOHOSaleOrderdata         = array();
		$ZOHOSaleOrderdata[]       = $zohoParam;
		$paramsSaleOrder      	  = array('data'=>$ZOHOSaleOrderdata);
		$ZohoSaleOrderStatus      = update_zoho_data('Sales_Orders', $paramsSaleOrder, $saleOrderID , 'PUT'); 
		
	}
	
}

function custom_woocommerce_get_all_order_statuses() {
	
  $order_statuses = get_posts( array('post_type'=>'wc_order_status', 'post_status'=>'publish', 'numberposts'=>-1) );
 
  $statuses = array();
  foreach ( $order_statuses as $status ) {
    $statuses[ $status->post_name ] = $status->post_title;
  }

  return $statuses;
}
?>