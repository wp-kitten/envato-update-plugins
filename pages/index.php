<?php if ( ! defined( 'ABSPATH' ) ) { return; } /*#!-- Do not allow this file to be loaded unless in WP context*/
/**
 * This is the plugin's default page
 */

// Defaults
	$userId = get_current_user_id();
	$user_name = $api_key = '';
	if(! empty($userId)){
		$user_name = get_user_meta($userId, MyEnvatoBaseApi::USER_META_NAME_KEY, true);
		$api_key = get_user_meta($userId, MyEnvatoBaseApi::USER_META_API_KEY, true);
	}
?>
<?php
/**
 * Validate the $_POST request
 *
 * @param int $userId The current user's ID
 * @param string $name The Envato Marketplace user name
 * @param string $key The Envato Marketplace api key
 *
 * @return bool
 */
function eup_fnValidateForm($userId, $name, $key)
{
	if(!empty($userId) && !empty($name) && !empty($key))
	{
		update_user_meta($userId, MyEnvatoBaseApi::USER_META_NAME_KEY, $name);
		update_user_meta($userId, MyEnvatoBaseApi::USER_META_API_KEY, $key);
		return true;
	}
	return false;
}
?>
<div class="wrap">
	<h2>Envato Update Plugins</h2>

<?php
	$rm = strtoupper($_SERVER['REQUEST_METHOD']);
	if('POST' == $rm)
	{
		if (! isset( $_POST['eup_save_credentials'] )|| ! wp_verify_nonce( $_POST['eup_save_credentials'],
				'eup_save_credentials_action' )) { ?>
			<div class="error below-h2">
				<p><?php _e('Invalid request.', 'envato-update-plugins');?></p>
			</div>
		<?php }
		else {
			if(isset($_POST['envato-update-plugins_user_name']) && isset($_POST['envato-update-plugins_api_key'])){
				$user_name = trim(esc_sql($_POST['envato-update-plugins_user_name']));
				$api_key = trim(esc_sql($_POST['envato-update-plugins_api_key']));
				if(! empty($user_name) && !empty($api_key)) {
					$result = eup_fnValidateForm( $userId, $user_name, $api_key );
					if($result){ ?>
						<div class="updated below-h2">
							<p><?php _e('Settings saved.', 'envato-update-plugins');?></p>
						</div>
					<?php }
					else { ?>
						<div class="error below-h2">
							<p><?php _e('Error saving data.', 'envato-update-plugins');?></p>
						</div>
					<?php }
				}
				else { ?>
					<div class="error below-h2">
						<p><?php _e('Please fill in all fields.', 'envato-update-plugins');?></p>
					</div>
				<?php }
			}
			else { ?>
				<div class="error below-h2">
					<p><?php _e('Please fill in all fields.', 'envato-update-plugins');?></p>
				</div>
			<?php }
		}
	}
?>

	<h3 class="nav-tab-wrapper">
		<span class="nav-tab nav-tab-active"><?php _e('Settings', 'envato-update-plugins');?></span>
	</h3>

	<form id="envato-update-plugins-form" method="post" style="margin: 0 20px 40px 0; max-width: 700px;">
		<h3><?php _e('User Account Information', 'envato-update-plugins');?></h3>

		<div>
			<p><?php _e('To obtain your API Key, visit your "My Settings" page on any of the Envato Marketplaces.',
					'envato-update-plugins');?></p>
		</div>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><?php _e('Marketplace Username', 'envato-update-plugins');?></th>
				<td><input type="text" class="regular-text" name="envato-update-plugins_user_name"
				           value="<?php echo $user_name;?>" id="envato-update-plugins_user_name"/></td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Secret API Key', 'envato-update-plugins');?></th>
				<td><input type="password" class="regular-text" name="envato-update-plugins_api_key"
				           value="<?php echo $api_key;?>" id="envato-update-plugins_api_key"/></td>
			</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="button" class="button-primary" id="envato-update-plugins_submit"
			       value="<?php _e( 'Save Settings', 'envato-update-plugins');?>" />
		</p>
		<?php wp_nonce_field( 'eup_save_credentials_action', 'eup_save_credentials');?>
	</form>
	<script type="application/javascript">
		jQuery(function($){
			$('#envato-update-plugins_submit').on('click', function(e){
				var user = $('#envato-update-plugins_user_name'),
					api = $('#envato-update-plugins_api_key');
				if(user.val().length <= 0){
					alert("<?php _e('User name is required.', 'envato-update-plugins');?>");
					e.preventDefault();
					return false;
				}
				if(api.val().length <= 0){
					alert("<?php _e('API key is required.', 'envato-update-plugins');?>");
					e.preventDefault();
					return false;
				}
				$('#envato-update-plugins-form').submit();
			});
		});
	</script>
</div>
