<?php
/**
 * Plugin Name: Spotfix - proofreading, spell and grammar check by visitors
 * Plugin URI: https://wordpress.org/plugins/spell-grammar-typo-check/
 * Description: Collect questions, suggestions, and fix content directly on website pages.
 * Version: 1.0.0
 * Author: Spotfix Team
 * Author URI: https://doboard.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: spell-grammar-typo-check
 * Domain Path: /languages
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
function activate_spotfix() {
	require_once SPOTFIX_PLUGIN_DIR . 'includes/class-spotfix-activator.php';
	Spotfix_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_spotfix() {
	require_once SPOTFIX_PLUGIN_DIR . 'includes/class-spotfix-deactivator.php';
	Spotfix_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_spotfix' );
register_deactivation_hook( __FILE__, 'deactivate_spotfix' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SPOTFIX_PLUGIN_DIR . 'includes/class-spotfix.php';

/**
 * Begins execution of the plugin.
 */
function run_spotfix() {
	$plugin = new Spotfix();
	$plugin->run();
}
run_spotfix();

