<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The core plugin class.
 */
class Spotfix {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		require_once SPOTFIX_PLUGIN_DIR . 'includes/class-spotfix-loader.php';
		require_once SPOTFIX_PLUGIN_DIR . 'admin/class-spotfix-admin.php';
		require_once SPOTFIX_PLUGIN_DIR . 'public/class-spotfix-public.php';
		require_once SPOTFIX_PLUGIN_DIR . 'includes/class-spotfix-status-checker.php';
		require_once SPOTFIX_PLUGIN_DIR . 'includes/class-spotfix-api.php';

		$this->loader = new Spotfix_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Spotfix_Admin();

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_ajax_spotfix_check_status', $plugin_admin, 'ajax_check_status' );
		$this->loader->add_action( 'wp_ajax_spotfix_create_account', $plugin_admin, 'ajax_create_account' );
		$this->loader->add_action( 'wp_ajax_spotfix_configure_account', $plugin_admin, 'ajax_configure_account' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( SPOTFIX_PLUGIN_DIR . 'spotfix-content-review.php' ), $plugin_admin, 'add_settings_link' );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'add_plugin_row_meta', 10, 2 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	private function define_public_hooks() {
		$plugin_public = new Spotfix_Public();

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_spotfix_script' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}
}

