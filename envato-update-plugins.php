<?php if(!defined('ABSPATH')) return; //#!-- Do not allow this file to be loaded unless in WP context
/*
Plugin Name: My Envato Plugins
Plugin URI: https://github.com/wp-kitten/envato-update-plugins
Description: This plugin extends the default WordPress plugin update functionality to include all plugins bought from Envato Marketplace so buyers can easily update them from inside WordPress.
Version: 1.0.0
Author: wp-kitten
Author URI: http://themeforest.net/user/wp-kitten
Text Domain: envato-update-plugins
Domain Path: /languages/
Network: true
License: GPL 3
*/
?>
<?php
/*  Copyright 2015  wp-kitten  (email : wp.kytten@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?><?php
/**
 * Set the system path to the plugin's directory
 */
define('EUP_PLUGIN_DIR', realpath(dirname(__FILE__)).'/');

//#!-- Load dependencies
require('lib/MyEnvatoBaseApi.php');
$myEnvato = new MyEnvatoBaseApi();

add_action('admin_init', array($myEnvato, 'onInit'));

//#!-- Register base hooks
register_deactivation_hook(__FILE__, array($myEnvato, 'onDeactivate'));
register_uninstall_hook(__FILE__, array('MyEnvatoBaseApi', 'onUninstall'));

//#!-- Load text domain
add_action('plugins_loaded', array($myEnvato, 'loadTextDomain'));

//#!-- Add sidebar menu
if(function_exists('is_multisite') && is_multisite()){
	add_action('network_admin_menu', array($myEnvato,'addPluginPages'));
}
else {
	add_action('admin_menu', array($myEnvato,'addPluginPages'));
}
