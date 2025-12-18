<?php
/**
 * Status checker for Spotfix connection.
 */
class Spotfix_Status_Checker {

	/**
	 * Check if Spotfix is online by validating the code.
	 */
	public static function check_status( $code ) {
		if ( empty( $code ) ) {
			return array(
				'status' => 'offline',
				'error' => 'Spotfix code is not configured.'
			);
		}

		// Extract project token, project ID, and account ID from the code
		$project_token = self::extract_parameter( $code, 'projectToken' );
		$project_id = self::extract_parameter( $code, 'projectId' );
		$account_id = self::extract_parameter( $code, 'accountId' );

		if ( empty( $project_token ) || empty( $project_id ) || empty( $account_id ) ) {
			return array(
				'status' => 'offline',
				'error' => 'Invalid Spotfix code. Missing required parameters (projectToken, projectId, or accountId).'
			);
		}

		// Try to validate by checking if the script URL is accessible
		$script_url = sprintf( 'https://doboard.com/1.0.0/spotfix.min.js?projectToken=%s&projectId=%s&accountId=%s', $project_token, $project_id, $account_id );
		$response = wp_remote_get( $script_url, array(
			'timeout' => 5,
			'sslverify' => true
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'offline',
				'error' => __( 'Cannot connect to doboard.com: ', 'spelling-grammar-typo-reviews' ) . esc_html( $response->get_error_message() )
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			return array(
				'status' => 'offline',
				'error' => __( 'Spotfix service returned error code: ', 'spelling-grammar-typo-reviews' ) . esc_html( $response_code )
			);
		}

		// Additional validation: try to verify project/account exists
		// This is a simplified check - in production you might want to call an API endpoint
		return array(
			'status' => 'online',
			'error' => ''
		);
	}

	/**
	 * Extract parameter value from JavaScript code.
	 */
	private static function extract_parameter( $code, $param_name ) {
		$pattern = '/' . preg_quote( $param_name, '/' ) . '=([^&\s\'"]+)/';
		if ( preg_match( $pattern, $code, $matches ) ) {
			return $matches[1];
		}
		return '';
	}
}

