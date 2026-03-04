<?php
/**
 * The public-facing functionality of the plugin.
 */
class Spotfix_Public {

	/**
	 * Enqueue Spotfix script in footer if conditions are met.
	 */
	public function enqueue_spotfix_script() {
		// Only show on public pages
		if ( is_admin() ) {
			return;
		}

		$settings = get_option( 'spotfix_settings', array() );
		$code = isset( $settings['code'] ) ? $settings['code'] : '';
		$visibility = isset( $settings['visibility'] ) ? $settings['visibility'] : 'everyone';

		// Check if code is configured
		if ( empty($code) ) {
			return;
		}

		// Check visibility settings
		$should_show = false;

		switch ( $visibility ) {
			case 'everyone':
				$should_show = true;
				break;

			case 'logged_in':
				$should_show = is_user_logged_in();
				break;

			case 'admin':
				$should_show = current_user_can( 'administrator' );
				break;
		}

		if ( ! $should_show ) {
			return;
		}

		$sanitized_query = Spotfix_Status_Checker::extractSanitizedQuery(
			$code,
			array( 'projectToken', 'projectId', 'accountId' ),
			false
		);

		if ( empty( $sanitized_query ) ) {
			return;
		}

		$widget_url = 'https://spotfix.doboard.com/doboard-widget-bundle.min.js?' . http_build_query($sanitized_query);

		wp_enqueue_script(
			'spotfix-widget',
			$widget_url,
			array(),
			SPOTFIX_VERSION,
			true
		);

		wp_localize_script(
			'spotfix-widget',
			'spotfixConfig',
			array(
				'projectToken' => isset( $sanitized_query['projectToken'] ) ? $sanitized_query['projectToken'] : '',
				'projectId' => isset( $sanitized_query['projectId'] ) ? $sanitized_query['projectId'] : '',
				'accountId' => isset( $sanitized_query['accountId'] ) ? $sanitized_query['accountId'] : '',
			)
		);
	}
}

