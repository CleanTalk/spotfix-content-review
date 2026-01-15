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
		$script_url = sprintf( 'https://spotfix.doboard.com/doboard-widget-bundle.min.js?projectToken=%s&projectId=%s&accountId=%s', $project_token, $project_id, $account_id );
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

        if ( !self::checkHomePage($script_url) ) {
            return array(
                'status' => 'offline',
                'error' => __( 'Spotfix script not found on home page.', 'spelling-grammar-typo-reviews' )
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

    /**
     * Check homepage to be sure that script url exists in the page code.
     * @param $script_url
     * @return bool
     */
    private static function checkHomePage($script_url)
    {
        $home_url_https = home_url('/', 'https');

        $response = wp_remote_get($home_url_https, array(
            'timeout' => 30,
            'redirection' => 5,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            // check is unavailable, return true
            return true;
        }

        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code === 200) {
            $html = wp_remote_retrieve_body($response);
            if (is_string($html) && !empty($html) && strpos($html, $script_url) !== false) {
                return true;
            }
        }

        return false;
    }
}

