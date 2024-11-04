<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/* Zoho App registration */ 

add_action('wp_ajax_authzoho','zoho_authzoho');
function zoho_authzoho(){

		if(isset($_REQUEST['code']))
		{
			$oauth = 0;
			$response = OAuth($_REQUEST['code']);
			
			if( !is_wp_error( $response ) ){
				$data = json_decode($response['body'], true);
				if(isset($data['access_token']))
				{
					update_option('wc_zoho_access_token', $data['access_token']);
					update_option('wc_zoho_access_token_expires_on', strtotime(current_time('mysql')) + 3600);
					
					if(isset($data['refresh_token']))
						update_option('wc_zoho_refresh_token', $data['refresh_token']);
					
					$oauth = 1;
				}
			}
			
			wp_redirect(admin_url('admin.php?page=zoho_connect&oauth='.$oauth));
			exit;
		}
	
die;	
}

/* Create authorization code */

function OAuth($code){
	
	if(ZOHO_CLIENT_ID != '')
		{
			$response = wp_remote_post( ZOHO_AUTH_URL, array(
				'body'	=> array(
					'code' 			=> $code,
					'redirect_uri' 	=> ZOHO_REDIRECT_URL,
					'client_id' 	=> ZOHO_CLIENT_ID,
					'client_secret' => ZOHO_CLIENT_SECRET,
					'grant_type' 	=> 'authorization_code',	
				)
			));
			
			return $response;
		}
}

/* Get refresh token */

function get_refresh_token()
{
		$status = 0;
		if(ZOHO_CLIENT_ID != '' && ZOHO_CLIENT_SECRET != '')
		{
			$response = wp_remote_post( ZOHO_AUTH_URL, array(
				'body'	=> array(
					'refresh_token'	=> get_option('wc_zoho_refresh_token'),
					'client_id' 	=> ZOHO_CLIENT_ID,
					'client_secret' => ZOHO_CLIENT_SECRET,
					'grant_type' 	=> 'refresh_token',	
				)
			));
			
			if( !is_wp_error( $response ) ){
				$data = json_decode($response['body'], true);
				if(isset($data['access_token']))
				{
					update_option('wc_zoho_access_token', $data['access_token']);
					update_option('wc_zoho_access_token_expires_on', strtotime(current_time('mysql')) + 3600);

					$status = 1;
				}
			}	
		}
		
		return $status;
}

/* Search contact by email */

function search_zoho_contact($module, $email ,$method = 'GET')
{
		
		$response = wp_remote_request( ZOHO_DOMAIN_URL.$module.'/search?email='.$email, array(
				'method' => $method,
				'headers' => array(
					'Authorization' => 'Zoho-oauthtoken ' . get_option('wc_zoho_access_token'),
				)
			));
			
		if( !is_wp_error( $response ) ){
			return array('status'=>1, 'body'=>json_decode($response['body'], true));
		}
		else{
			return array('status'=>0);
		}	
}

/* insert zoho recoard */

function request_zoho_data($module, $params, $method = 'GET')
{
		$response = wp_remote_request( ZOHO_DOMAIN_URL.$module, array(
				'method' => $method,
				'headers' => array(
					'Authorization' => 'Zoho-oauthtoken ' . get_option('wc_zoho_access_token'),
				),
				'body'	=> json_encode($params)
			));
			
		if( !is_wp_error( $response ) ){
			return array('status'=>1, 'body'=>json_decode($response['body'], true));
		}
		else{
			return array('status'=>0);
		}	
}

/* Update Recoard data */

function update_zoho_data($module, $params, $recoardID ,$method = 'PUT')
{
		$response = wp_remote_request( ZOHO_DOMAIN_URL.$module.'/'.$recoardID, array(
				'method' => $method,
				'headers' => array(
					'Authorization' => 'Zoho-oauthtoken ' . get_option('wc_zoho_access_token'),
				),
				'body'	=> json_encode($params)
			));
			
		if( !is_wp_error( $response ) ){
			return array('status'=>1, 'body'=>json_decode($response['body'], true));
		}
		else{
			return array('status'=>0);
		}	
}

/* Search recoard by given query  */

function search_zoho_recoard($module, $criteria ,$method = 'GET')
{
		
		$response = wp_remote_request( ZOHO_DOMAIN_URL.$module.'/search?criteria='.$criteria, array(
				'method' => $method,
				'headers' => array(
					'Authorization' => 'Zoho-oauthtoken ' . get_option('wc_zoho_access_token'),
				)
			));
			
		if( !is_wp_error( $response ) ){
			return array('status'=>1, 'body'=>json_decode($response['body'], true));
		}
		else{
			return array('status'=>0);
		}	
}

?>