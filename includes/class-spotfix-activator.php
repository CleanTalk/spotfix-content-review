<?php
/**
 * Fired during plugin activation.
 */
class Spotfix_Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		// Set default options
		$default_options = array(
			'spotfix_code' => '',
			'spotfix_visibility' => 'everyone',
			'spotfix_status' => 'offline',
			'spotfix_error' => ''
		);

		if ( ! get_option( 'spotfix_settings' ) ) {
			add_option( 'spotfix_settings', $default_options );
		}
	}
}

