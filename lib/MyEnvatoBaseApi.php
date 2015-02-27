<?php
//#!-- Load dependencies
include_once('class-envato-api.php');

/**
 * class MyEnvatoBaseApi
 *
 * Base plugin class. Extends the Envato_Protected_API and provides the common methods to update Envato plugins.
 */
class MyEnvatoBaseApi extends Envato_Protected_API
{

//<editor-fold desc="ENVATO API">
	/**
	 * Class constructor. Sets error messages if any. Registers the 'pre_set_site_transient_update_plugins' filter.
	 *
	 * @param string $user_name The buyer's Username
	 * @param string $api_key   The buyer's API Key can be accessed on the marketplaces via My Account -> My Settings -> API Key
	 */
	public function __construct( $user_name = '', $api_key = '' )
	{
		$this->user_name = $user_name;
		$this->api_key = $api_key;
	}

	/**
	 * Set up the filter for plugins in order to include Envato plugins
	 *
	 * @private
	 */
	public function onInit()
	{
		if(empty($this->user_name) || empty($this->api_key)){
			$userId = get_current_user_id();
			if(! empty($userId)){
				$this->user_name = get_user_meta($userId, self::USER_META_NAME_KEY, true);
				$this->api_key = get_user_meta($userId, self::USER_META_API_KEY, true);

			}
		}
		// Setup parent class with the correct credentials, if we have them
		if(!empty($this->user_name) && !empty($this->api_key)) {
			parent::__construct( $this->user_name, $this->api_key );
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'checkPluginUpdates' ) );
		}
	}

	/**
	 * Update the plugins list to include the Envato plugins that require update. Triggered by the
	 * pre_set_site_transient_update_plugins filter.
	 *
	 * @param $plugins
	 *
	 * @return mixed
	 */
	public function checkPluginUpdates($plugins)
	{
		if(empty($plugins) || !isset($plugins->checked)){
			return $plugins; // No plugins
		}

		$wpPlugins = $plugins->checked;
		if(! is_array($wpPlugins) || empty($wpPlugins)){
			return $plugins; // No plugins
		}

		// Get user's plugins list from Envato
		$envatoPlugins = $this->wp_list_plugins();
		if(empty($envatoPlugins)){
			return $plugins; // No plugins from Envato Marketplace found
		}

		// Find the plugins directory
		if(defined('WP_PLUGIN_DIR') && WP_PLUGIN_DIR !== ''){
			$pluginsDir = trailingslashit(WP_PLUGIN_DIR);
		}
		else {
			if(defined('WP_CONTENT_DIR') && WP_CONTENT_DIR !== ''){
				$pluginsDir = trailingslashit(WP_CONTENT_DIR).'plugins/';
			}
			else {
				$pluginsDir = realpath(dirname(__FILE__).'/../../').'/';
			}
		}

		// Loop over the plugins and see which needs update
		foreach($wpPlugins as $path => $version)
		{
			$pluginData = get_plugin_data($pluginsDir.$path);
			$wpPluginName = isset($pluginData['Name']) ? $pluginData['Name'] : '';
			$wpPluginVersion = isset($pluginData['Version']) ? $pluginData['Version'] : null;
			if(empty($wpPluginName) || is_null($wpPluginVersion)){
				continue;
			}
			// Check plugin in Envato plugins
			foreach($envatoPlugins as $i => $pluginObj){
				// We have a match
				if(isset($pluginObj->plugin_name) && $pluginObj->plugin_name == $wpPluginName){
					// Check plugin to see if it needs to be updated
					$v = isset($pluginObj->version) ? $pluginObj->version : null;
					if(! is_null($v)){
						// Needs update - prepare entry
						if(version_compare( $v, $wpPluginVersion, '>' )){
							// Get the update zip file
							$update_zip = $this->wp_download( $pluginObj->item_id );

							if ( ! $update_zip || empty( $update_zip ) ) {
								// Error ?
								$errors = $this->api_errors();
								if(! empty($errors)){
									error_log('ERRORS: '.var_export($errors,1));
								}
								break; // No need to go any further
							}

							// Add plugin to WordPress' list
							$plugins->response[ $path ] = (object) array(
								'id' => $pluginObj->item_id,
								'slug' => str_replace(' ', '-', trim($pluginObj->plugin_name)),
								'plugin' => $path,
								'new_version' => $v,
								'upgrade_notice' => null,
								'url' => (isset($pluginData['PluginURI']) ? $pluginData['PluginURI'] : null),
								'package' => $update_zip
							);
						}
					}
				}
			}
		}
		return $plugins;
	}

	/**
	 * Retrieve user's list of plugins from Envato
	 *
	 * @param bool $allow_cache Whether or not to allow caching of the result
	 * @param int $timeout Request timeout
	 *
	 * @return array
	 */
	protected function wp_list_plugins( $allow_cache = true, $timeout = 300 )
	{
		return $this->private_user_data(
			'wp-list-plugins',
			$this->user_name,
			'',
			$allow_cache,
			$timeout
		);
	}
//</editor-fold desc="ENVATO API">

//<editor-fold desc="WORDPRESS API">
	/**
	 * Holds the name of the user meta key that will store the Envato user name
	 *
	 * @type string
	 */
	const USER_META_NAME_KEY = 'my_envato_user_name';
	/**
	 * Holds the name of the user meta key that will store the Envato api key
	 *
	 * @type string
	 */
	const USER_META_API_KEY = 'my_envato_api_key';


	/**
	 * Triggered when the plugin is deactivated
	 */
	public function onDeactivate()
	{
		// Remove plugins filter
		if(has_filter('pre_set_site_transient_update_plugins', array( $this, 'checkPluginUpdates' ))) {
			remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'checkPluginUpdates' ) );
		}
	}
	/**
	 * Triggered when the plugin is uninstalled. Must be static.
	 * @static
	 * @public
	 */
	public static function onUninstall()
	{
		// Remove user meta
		$userId = get_current_user_id();
		if(! empty($userId)) {
			delete_user_meta( $userId, self::USER_META_NAME_KEY );
			delete_user_meta( $userId, self::USER_META_API_KEY );
		}
	}
	public function loadTextDomain()
	{
		load_plugin_textdomain('envato-update-plugins', '', 'envato-update-plugins/languages');
	}

	/**
	 * Creates the sidebar menu
	 */
	public function addPluginPages()
	{
		add_menu_page('My Envato Plugins', 'My Envato Plugins', 'manage_options', 'eup_');
		add_submenu_page('eup_', __('Settings', 'envato-update-plugins'), __('Settings', 'envato-update-plugins'),
			'manage_options', 'eup_', array($this,'pageDashboard'));
	}

	public function pageDashboard(){ include(EUP_PLUGIN_DIR.'pages/index.php'); }

//</editor-fold desc="WORDPRESS API">
}