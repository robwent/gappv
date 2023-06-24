<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://robertwent.com
 * @since             1.0.0
 * @package           Gappv
 *
 * @wordpress-plugin
 * Plugin Name:       Google Analytics Post Page Views
 * Plugin URI:        https://robertwent.com
 * Description:       Retrieves and displays the pageviews for each post by linking to your Google Analytics V4 account.
 * Version:           1.0.0
 * Author:            Robert Went
 * Author URI:        https://robertwent.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gappv
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'GAPPV_VERSION', '1.0.0' );

/**
 * Require the Google Api Client Library
 */
require 'vendor/autoload.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-gappv-activator.php
 */
function activate_gappv() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gappv-activator.php';
	Gappv_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-gappv-deactivator.php
 */
function deactivate_gappv() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gappv-deactivator.php';
	Gappv_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_gappv' );
register_deactivation_hook( __FILE__, 'deactivate_gappv' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-gappv.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_gappv() {

	$plugin = new Gappv();
	$plugin->run();

}
run_gappv();
