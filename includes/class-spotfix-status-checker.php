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

		// Extract query
        $spotfix_script_query = self::extractSanitizedQueryString($code, ['projectToken', 'projectId', 'accountId']);

		if ( empty( $spotfix_script_query )  ) {
			return array(
				'status' => 'offline',
				'error' => 'Invalid Spotfix code. Missing or invalid required parameters (projectToken, projectId, or accountId).'
			);
		}

		// Try to validate by checking if the script URL is accessible
		$script_url = sprintf( 'https://spotfix.doboard.com/doboard-widget-bundle.min.js?%s', $spotfix_script_query);
		$response = wp_remote_get( $script_url, array(
			'timeout' => 5,
			'sslverify' => true
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'offline',
				'error' => __( 'Cannot connect to doboard.com: ', 'spotfix-content-review' ) . esc_html( $response->get_error_message() )
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			return array(
				'status' => 'offline',
				'error' => __( 'Spotfix service returned error code: ', 'spotfix-content-review' ) . esc_html( $response_code )
			);
		}

        if ( !self::checkHomePage($script_url) ) {
            return array(
                'status' => 'offline',
                'error' => __( 'Spotfix script not found on home page.', 'spotfix-content-review' )
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

    /**
     * Extract and sanitize query string from provided code. Apply esc_js for every required param.
     * @param string $code Code string
     * @return string Extracted query string or empty string on errors
     */
    public static function extractSanitizedQueryString($code, $required_params = array()) {
        if (empty($required_params)) {
            return '';
        }

        if (!preg_match('/apbctScript\.src\s*=\s*["\'](https:\/\/spotfix\.doboard\.com\/[^"\']+)["\']/', $code, $matches)) {
            return '';
        }

        $query_string = wp_parse_url($matches[1], PHP_URL_QUERY);
        if (!$query_string) {
            return '';
        }

        parse_str($query_string, $params);

        foreach ($required_params as $key) {
            if (empty($params[$key])) {
                return '';
            }
            $params[$key] = esc_js($params[$key]);
        }

        return http_build_query($params);
    }
}

