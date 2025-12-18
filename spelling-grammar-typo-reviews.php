<?php
/**
 * Plugin Name: Spotfix - proofreading, spelling and grammar reviews by visitors
 * Plugin URI: https://wordpress.org/plugins/spelling-grammar-typo-reviews/
 * Description: Collect questions, suggestions, and fix content directly on website pages.
 * Version: 1.0.0
 * Author: doBoard
 * Author URI: https://doboard.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: spelling-grammar-typo-reviews
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'SPOTFIX_VERSION', '1.0.0' );
define( 'SPOTFIX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPOTFIX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function spotfix_activate() {
	require_once SPOTFIX_PLUGIN_DIR . 'includes/class-spotfix-activator.php';
	Spotfix_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function spotfix_deactivate() {
	require_once SPOTFIX_PLUGIN_DIR . 'includes/class-spotfix-deactivator.php';
	Spotfix_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'spotfix_activate' );
register_deactivation_hook( __FILE__, 'spotfix_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SPOTFIX_PLUGIN_DIR . 'includes/class-spotfix.php';

/**
 * Begins execution of the plugin.
 */
function spotfix_run() {
	$plugin = new Spotfix();
	$plugin->run();
}
spotfix_run();

