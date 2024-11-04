<div class="wrap">
<h1>Zoho CRM API Settings</h1>
<?php 
	if($_REQUEST['settings-updated'] == true){
?>
<div class="notice notice-success is-dismissible"> 
	<p><strong>Settings saved.</strong></p>
</div>
<?php }if(isset($_GET['oauth']) && $_GET['oauth'] == 1){
	
	?>
	<div class="notice notice-success is-dismissible"> 
		<p><strong>Zoho Account Connected.</strong></p>
	</div>
	<?php
}

$wc_zoho_client_id = ZOHO_CLIENT_ID;
$auth_redirect_url = ZOHO_REDIRECT_URL;

//$auth_url = 'https://accounts.zoho.eu/oauth/v2/auth?scope=ZohoCRM.modules.ALL&client_id='.$wc_zoho_client_id.'&response_type=code&access_type=offline&redirect_uri='.$auth_redirect_url;

//$auth_request .= '<a href="'.$auth_url.'" class="button-primary">'.__( 'Authorize Zoho App', 'wc-zoho' ).'</a>';

if($wc_zoho_client_id != '')
		{
			
			$auth_url = 'https://accounts.zoho.eu/oauth/v2/auth?scope=ZohoCRM.modules.ALL&client_id='.$wc_zoho_client_id.'&response_type=code&access_type=offline&redirect_uri='.$auth_redirect_url;

			$auth_request = ' ';
			if(get_option('wc_zoho_access_token') != '' && get_option('wc_zoho_refresh_token') != '')
				$auth_request .= '<span style="color: green;">You have authorized Zoho App.</span> <br><br> <a href="'.$auth_url.'" class="button-primary">'.__( 'Authorize New Zoho App', 'wc-zoho' ).'</a>';
			else
				$auth_request .= '<a href="'.$auth_url.'" class="button-primary">'.__( 'Authorize Zoho App', 'wc-zoho' ).'</a>';
		}
		
		echo '<br>For OAuth add this Redirect URI in Zoho App registration - <b>'.$auth_redirect_url.'</b>'.$auth_request;
?>


<form method="post" action="options.php">
    <?php settings_fields( 'zoho-settings-group' ); ?>
    <?php do_settings_sections( 'zoho-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
			<th scope="row">Client ID</th>
			<td><input type="text" name="zoho_client_id" value="<?php echo esc_attr( get_option('zoho_client_id') ); ?>" style="width:55%;" required /></td>
        </tr>
         
        <tr valign="top">
			<th scope="row">Client Secret</th>
			<td><input type="text" name="zoho_client_secret" value="<?php echo esc_attr( get_option('zoho_client_secret') ); ?>" style="width:55%;" required /></td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>
</div>