<?php
/**
 * PV Events WordPress Plugin
 *
 * A plugin to handle Planview's live virtual events in WordPress
 *
 * @package   PV_Events
 * @author    Steve Crockett <crockett95@gmail.com
 * @license   GPL-2.0+
 * @link      https://github.com/Planview/pv-events
 * @copyright 2014 Planview, Inc.
 *
 * @wordpress-plugin
 * Plugin Name:       Planview Events
 * Plugin URI:        https://github.com/Planview/pv-events
 * Description:       Planview's plugin for live virtual events
 * Version:           0.0.0
 * Author:            Steve Crockett
 * Author URI:        https://github.com/crockett95
 * Text Domain:       pv-events
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/Planview/pv-events
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * - replace `class-plugin-name.php` with the name of the plugin's class file
 *
 */
require_once( plugin_dir_path( __FILE__ ) . 'public/class-pv-events.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'PV_Events', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PV_Events', 'deactivate' ) );

/*
 * Hook for `plugins_loaded`
 */
add_action( 'plugins_loaded', array( 'PV_Events', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * - replace `class-plugin-name-admin.php` with the name of the plugin's admin file
 * - replace Plugin_Name_Admin with the name of the class defined in
 *   `class-plugin-name-admin.php`
 *
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-pv-events-admin.php' );
	add_action( 'plugins_loaded', array( 'PV_Events_Admin', 'get_instance' ) );

}
