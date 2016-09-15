<?php
/**
 * Adds group hierarchy functionality to your BuddyPress-powered community site.
 *
 * @package   HierarchicalGroupsForBP
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2016 David Cavins
 *
 * @wordpress-plugin
 * Plugin Name:       Hierarchical Groups for BP
 * Plugin URI:        @TODO
 * Description:       Adds group hierarchy functionality to your BuddyPress-powered community site.
 * Version:           1.0.0
 * Author:            dcavins
 * Text Domain:       hierarchical-groups-for-bp
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/dcavins/hierarchical-groups-for-bp
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

function hierarchical_groups_for_bp_init() {

	// Take an early out if the groups component isn't activated.
	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}

	// Helper functions
	require_once( plugin_dir_path( __FILE__ ) . 'includes/hgbp-functions.php' );

	// Template output functions
	require_once( plugin_dir_path( __FILE__ ) . 'public/views/template-tags.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'public/views/shortcodes.php' );

	// The BP_Group_Extension class
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-bp-group-extension.php' );

	// The main class
	require_once( plugin_dir_path( __FILE__ ) . 'public/class-hgbp.php' );
	HGBP_Public::get_instance();

	// Admin and dashboard functionality
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'admin/class-hgbp-admin.php' );
		HGBP_Admin::get_instance();
	}

}
add_action( 'bp_loaded', 'hierarchical_groups_for_bp_init' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-hgbp-activator.php';
register_activation_hook( __FILE__, array( 'HGBP_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HGBP_Activator', 'deactivate' ) );

/**
 * Helper function.
 *
 * @return Fully-qualified URI to the root of the plugin.
 */
function hgbp_get_plugin_base_uri(){
	return plugin_dir_url( __FILE__ );
}

/**
 * Helper function to return the current version of the plugin.
 *
 * @return string Current version of plugin.
 */
function hgbp_get_plugin_version(){
	return '1.0.0';
}
