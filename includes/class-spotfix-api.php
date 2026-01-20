<?php
/**
 * API class for doBoard integration.
 * Handles automatic account and project creation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Spotfix_API {

	/**
	 * CleanTalk API endpoint for registration.
	 */
	const CLEANTALK_API_URL = 'https://api.cleantalk.org/';
	const DOBOARD_API_URL = 'https://api.doboard.com/';

	/**
	 * Option name for storing API data.
	 */
	const OPTION_NAME = 'spotfix_api_data';

	/**
	 * Get current API data from options.
	 *
	 * @return array
	 */
	public static function get_api_data() {
		$defaults = array(
			'user_token' => '',
			'email'      => '',
            'account_id' => '',
            'session_id' => '',
			'created_at' => '',
		);

		return wp_parse_args( get_option( self::OPTION_NAME, array() ), $defaults );
	}

	/**
	 * Update API data in options.
	 *
	 * @param array $data Data to update.
	 * @return bool
	 */
	public static function update_api_data( $data ) {
		$current = self::get_api_data();
		$updated = wp_parse_args( $data, $current );
		return update_option( self::OPTION_NAME, $updated );
	}

	/**
	 * Register account via CleanTalk API.
	 * This will send confirmation email to admin.
	 *
	 * @return array Result with success/error.
	 */
	public static function create_account() {
		//$admin_email = get_option( 'admin_email' );
		$admin_email = 'test' . rand(1000, 9999) . '@example.com'; // For testing purposes only
		
		if ( empty( $admin_email ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Admin email is not configured in WordPress settings.', 'spotfix-content-review' ),
			);
		}

		// Call CleanTalk API to register
		$response = wp_remote_post( self::CLEANTALK_API_URL, array(
			'timeout' => 30,
			'body'    => array(
				'method_name'  => 'get_api_key',
				'email'        => $admin_email,
				'product_name' => 'doboard',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to connect to registration service: ', 'spotfix-content-review' ) . $response->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! $data ) {
			return array(
				'success' => false,
				'error'   => __( 'Invalid response from registration service.', 'spotfix-content-review' ),
			);
		}

		// Check for API error
		if ( isset( $data['error'] ) && ! empty( $data['error'] ) ) {
			return array(
				'success' => false,
				'error'   => $data['error'],
			);
		}

		// Store user_token for later use and authorize user
		if ( isset( $data['data']['user_token'] ) ) {
			$user_token = $data['data']['user_token'];
			self::update_api_data( array(
				'user_token' => $user_token,
				'email'      => $admin_email,
				'created_at' => current_time( 'mysql' ),
			) );

            // Authorize user with doBoard API
			$auth_response = wp_remote_post( self::DOBOARD_API_URL . 'user_authorize',  array(
				'timeout' => 30,
				'body'    => array(
				    'user_token' => $user_token
				),
			));

			$auth_data = null;
			if ( ! is_wp_error( $auth_response ) ) {
				$auth_body = wp_remote_retrieve_body( $auth_response );
				$auth_data = json_decode( $auth_body, true );
				$session_id = isset($auth_data['data']['session_id']) ? $auth_data['data']['session_id'] : '';
				$user_id    = isset($auth_data['data']['user_id']) ? $auth_data['data']['user_id'] : '';
				$account_id = '';
				if (isset($auth_data['data']['accounts'][0]['account_id'])) {
					$account_id = $auth_data['data']['accounts'][0]['account_id'];
				}
				self::update_api_data( array(
					'session_id' => $session_id,
					'user_id'    => $user_id,
					'account_id' => $account_id,
				) );
			}

			return array(
				'success' => true,
				'message' => sprintf(
					__( 'Account registration started! A confirmation email has been sent to %s. Please check your inbox and click the confirmation link. After confirming your email, click the button above to finish configuration.', 'spotfix-content-review' ),
					$admin_email
				),
				'email'   => $admin_email,
				'user_token' => $user_token,
				'session_id' => $session_id,
				'user_id' => $user_id,
                'account_id' => $account_id
			);
		}

		return array(
			'success' => false,
			'error'   => __( 'Unexpected response from registration service. Please try again.', 'spotfix-content-review' ),
		);
	}

    
    /**
	 * Configure account via doBoard API.
	 * @return array Result with success/error.
	 */
	public static function configurate_account() {
		$api_data = self::get_api_data();
		$account_id = isset($api_data['account_id']) ? $api_data['account_id'] : '';
		$session_id = isset($api_data['session_id']) ? $api_data['session_id'] : '';
		$org_name = get_bloginfo('name');
		$plugin_name = 'spotfix-content-review';

		if ($account_id === '' || $account_id === null || $session_id === '' || $session_id === null) {
			return array(
				'success' => false,
				'error' => __('Account ID or session ID is missing.', 'spotfix-content-review'),
			);
		}

		// account_add request
		$body = array(
			'account_id' => $account_id,
			'session_id' => $session_id,
			'org_name'   => $org_name,
		);
		$response = wp_remote_post(self::DOBOARD_API_URL . 'account_add', array(
			'timeout' => 30,
			'body'    => $body,
		));

		if (is_wp_error($response)) {
			return array(
				'success' => false,
				'error' => __('Failed to connect to account_add: ', 'spotfix-content-review') . $response->get_error_message(),
			);
		}

		$resp_body = wp_remote_retrieve_body($response);
		$data = json_decode($resp_body, true);

		if (!$data || (isset($data['error']) && !empty($data['error']))) {
			return array(
				'success' => false,
				'error' => isset($data['error']) ? $data['error'] : __('Invalid response from account_add.', 'spotfix-content-review'),
			);
		}

		self::update_api_data(array('account_add_response' => $data));

		// project_add request
		$project_add_url = self::DOBOARD_API_URL . $account_id . '/project_add';
		$project_body = array(
			'session_id'    => $session_id,
			'name'          => $plugin_name,
			'project_type'  => 'PUBLIC',
		);

		$project_response = wp_remote_post($project_add_url, array(
			'timeout' => 30,
			'body'    => $project_body,
		));

		if (is_wp_error($project_response)) {
			return array(
				'success' => false,
				'error' => __('Failed to connect to project_add: ', 'spotfix-content-review') . $project_response->get_error_message(),
			);
		}

		$project_resp_body = wp_remote_retrieve_body($project_response);
		$project_data = json_decode($project_resp_body, true);

		if (!$project_data || (isset($project_data['error']) && !empty($project_data['error']))) {
			return array(
				'success' => false,
				'error' => isset($project_data['error']) ? $project_data['error'] : __('Invalid response from project_add.', 'spotfix-content-review'),
			);
		}

		self::update_api_data(array('project_add_response' => $project_data));

		return array(
			'success' => true,
			'account_add' => $data,
			'project_add' => $project_data,
		);
	}
}
