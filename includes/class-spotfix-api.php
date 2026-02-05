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
	const DOBOARD_API_URL   = 'https://api.doboard.com/';

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
			'user_token'    => '',
			'email'         => '',
			'account_id'    => '',
			'session_id'    => '',
			'project_token' => '',
			'project_id'    => '',
			'created_at'    => '',
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
	 * Reset API data (clear all stored data).
	 *
	 * @return bool
	 */
	private static function resetApiData() {
		return delete_option( self::OPTION_NAME );
	}

	/*
	|--------------------------------------------------------------------------
	| HTTP Request Helper
	|--------------------------------------------------------------------------
	*/

	/**
	 * Make HTTP POST request to API.
	 *
	 * @param string $url  API endpoint URL.
	 * @param array  $body Request body.
	 * @return array|WP_Error Decoded response data or WP_Error.
	 */
	private static function makeRequest( $url, $body = array() ) {
		$response = wp_remote_post( $url, array(
			'timeout' => 30,
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( ! $data ) {
			return new WP_Error( 'invalid_response', __( 'Invalid response from API.', 'spotfix-content-review' ) );
		}

		if ( isset( $data['error'] ) && ! empty( $data['error'] ) ) {
			return new WP_Error( 'api_error', $data['error'] );
		}

		return $data;
	}

	/*
	|--------------------------------------------------------------------------
	| API Operation Methods (Private)
	|--------------------------------------------------------------------------
	*/

	/**
	 * Request API key from CleanTalk.
	 *
	 * @param string $email Admin email.
	 * @return array Result with success/error and user_token.
	 */
	private static function requestApiKey( $email ) {
		$response = self::makeRequest( self::CLEANTALK_API_URL, array(
			'method_name'  => 'get_api_key',
			'email'        => $email,
			'product_name' => 'doboard',
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to connect to registration service: ', 'spotfix-content-review' ) . $response->get_error_message(),
			);
		}

		if ( ! isset( $response['data']['user_token'] ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Unexpected response from registration service.', 'spotfix-content-review' ),
			);
		}

		return array(
			'success'    => true,
			'user_token' => $response['data']['user_token'],
		);
	}

	/**
	 * Authorize user in doBoard.
	 *
	 * @param string $user_token User token from CleanTalk.
	 * @return array Result with success/error and session data.
	 */
	private static function authorizeUser( $user_token ) {
		$response = self::makeRequest( self::DOBOARD_API_URL . 'user_authorize', array(
			'user_token' => $user_token,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to authorize user: ', 'spotfix-content-review' ) . $response->get_error_message(),
			);
		}

		$session_id = isset( $response['data']['session_id'] ) ? $response['data']['session_id'] : '';
		$user_id    = isset( $response['data']['user_id'] ) ? $response['data']['user_id'] : '';
		$account_id = '';

		if ( isset( $response['data']['accounts'][0]['account_id'] ) ) {
			$account_id = $response['data']['accounts'][0]['account_id'];
		}

		return array(
			'success'    => true,
			'session_id' => $session_id,
			'user_id'    => $user_id,
			'account_id' => $account_id,
		);
	}

	/**
	 * Add/create account in doBoard.
	 *
	 * @param string $account_id Account ID.
	 * @param string $session_id Session ID.
	 * @param string $org_name   Organization name.
	 * @return array Result with success/error and account data.
	 */
	private static function addAccount( $account_id, $session_id, $org_name ) {
		$response = self::makeRequest( self::DOBOARD_API_URL . 'account_add', array(
			'account_id' => $account_id,
			'session_id' => $session_id,
			'org_name'   => $org_name,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to add account: ', 'spotfix-content-review' ) . $response->get_error_message(),
			);
		}

		// Extract new account_id from response
		$new_account_id = $account_id;
		if ( isset( $response['data']['account_id'] ) && $response['data']['account_id'] ) {
			$new_account_id = $response['data']['account_id'];
		} elseif ( isset( $response['data']['accounts'][0]['account_id'] ) && $response['data']['accounts'][0]['account_id'] ) {
			$new_account_id = $response['data']['accounts'][0]['account_id'];
		}

		return array(
			'success'    => true,
			'account_id' => $new_account_id,
			'response'   => $response,
		);
	}

	/**
	 * Add project in doBoard.
	 *
	 * @param string $account_id   Account ID.
	 * @param string $session_id   Session ID.
	 * @param string $project_name Project name.
	 * @return array Result with success/error and project data.
	 */
	private static function addProject( $account_id, $session_id, $project_name ) {
		$url = self::DOBOARD_API_URL . $account_id . '/project_add';

		$response = self::makeRequest( $url, array(
			'session_id'   => $session_id,
			'name'         => $project_name,
			'project_type' => 'PUBLIC',
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to add project: ', 'spotfix-content-review' ) . $response->get_error_message(),
			);
		}

		$project_id = isset( $response['data']['project_id'] ) ? $response['data']['project_id'] : '';

		return array(
			'success'    => true,
			'project_id' => $project_id,
			'response'   => $response,
		);
	}

	/**
	 * Get project data from doBoard.
	 *
	 * @param string $account_id   Account ID.
	 * @param string $session_id   Session ID.
	 * @param string $project_name Project name.
	 * @return array Result with success/error and project data.
	 */
	private static function getProject( $account_id, $session_id, $project_name ) {
		$url = self::DOBOARD_API_URL . $account_id . '/project_get';

		$response = self::makeRequest( $url, array(
			'session_id' => $session_id,
			'name'       => $project_name,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to get project: ', 'spotfix-content-review' ) . $response->get_error_message(),
			);
		}

		$project_token = '';
		if ( isset( $response['data']['projects'][0]['project_token'] ) ) {
			$project_token = $response['data']['projects'][0]['project_token'];
		}

		return array(
			'success'       => true,
			'project_token' => $project_token,
			'response'      => $response,
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Snippet Generation Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Build JavaScript snippet code for the widget.
	 *
	 * @return string JavaScript code.
	 */
	private static function buildSnippetCode() {
		$api_data      = self::get_api_data();
		$project_token = isset( $api_data['project_token'] ) ? $api_data['project_token'] : '';
		$project_id    = isset( $api_data['project_id'] ) ? $api_data['project_id'] : '';
		$account_id    = isset( $api_data['account_id'] ) ? $api_data['account_id'] : '';

		$snippet = "(function () {\n"
			. "    window.SpotfixWidgetConfig = {verticalPosition: '0'};\n"
			. "    let apbctScript = document.createElement('script');\n"
			. "    apbctScript.type = 'text/javascript';\n"
			. "    apbctScript.async = 'true';\n"
			. "    apbctScript.src = 'https://spotfix.doboard.com/doboard-widget-bundle.min.js?"
			. "projectToken=" . esc_js( $project_token )
			. "&projectId=" . esc_js( $project_id )
			. "&accountId=" . esc_js( $account_id ) . "';\n"
			. "    let firstScriptNode = document.getElementsByTagName('script')[0];\n"
			. "    firstScriptNode.parentNode.insertBefore(apbctScript, firstScriptNode);\n"
			. "})();";

		return $snippet;
	}

	/**
	 * Save snippet code to spotfix_settings option.
	 *
	 * @param string $snippet_code JavaScript code.
	 * @return bool
	 */
	private static function saveSnippetToSettings( $snippet_code ) {
		$settings         = get_option( 'spotfix_settings', array() );
		$settings['code'] = $snippet_code;
		return update_option( 'spotfix_settings', $settings );
	}

	/*
	|--------------------------------------------------------------------------
	| Public Entry Point Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Register account via CleanTalk API and authorize in doBoard.
	 * This will send confirmation email to admin.
	 *
	 * Flow:
	 * 1. Reset previous data
	 * 2. Request API key from CleanTalk
	 * 3. Save user token
	 * 4. Authorize user in doBoard
	 * 5. Save session data
	 *
	 * @return array Result with success/error.
	 */
	public static function create_account() {
		// Step 1: Reset previous data
		self::resetApiData();

		// Step 2: Get admin email
		// $admin_email = get_option( 'admin_email' );
		$admin_email = 'test' . rand( 1000, 9999 ) . '@example.com'; // For testing purposes only

		if ( empty( $admin_email ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Admin email is not configured in WordPress settings.', 'spotfix-content-review' ),
			);
		}

		// Step 3: Request API key from CleanTalk
		$api_key_result = self::requestApiKey( $admin_email );

		if ( ! $api_key_result['success'] ) {
			return $api_key_result;
		}

		$user_token = $api_key_result['user_token'];

		// Step 4: Save initial data
		self::update_api_data( array(
			'user_token' => $user_token,
			'email'      => $admin_email,
			'created_at' => current_time( 'mysql' ),
		) );

		// Step 5: Authorize user in doBoard
		$auth_result = self::authorizeUser( $user_token );

		$session_id = '';
		$user_id    = '';
		$account_id = '';

		if ( $auth_result['success'] ) {
			$session_id = $auth_result['session_id'];
			$user_id    = $auth_result['user_id'];
			$account_id = $auth_result['account_id'];

			self::update_api_data( array(
				'session_id' => $session_id,
				'user_id'    => $user_id,
				'account_id' => $account_id,
			) );
		}

		// Step 6: Return success response
		return array(
			'success'    => true,
			'message'    => sprintf(
				__( 'Account registration started! A confirmation email has been sent to %s. Please check your inbox and click the confirmation link. After confirming your email, click the button above to finish configuration.', 'spotfix-content-review' ),
				$admin_email
			),
			'email'      => $admin_email,
			'user_token' => $user_token,
			'session_id' => $session_id,
			'user_id'    => $user_id,
			'account_id' => $account_id,
		);
	}

	/**
	 * Configure account via doBoard API.
	 * Creates account, project and generates snippet code.
	 *
	 * Flow:
	 * 1. Validate stored data
	 * 2. Add/configure account
	 * 3. Add project
	 * 4. Get project details (token)
	 * 5. Build and save snippet code
	 *
	 * @return array Result with success/error.
	 */
	public static function configurate_account() {
		// Step 1: Get stored API data
		$api_data    = self::get_api_data();
		$account_id  = $api_data['account_id'];
		$session_id  = $api_data['session_id'];
		$org_name    = get_bloginfo( 'name' );
		$plugin_name = 'spotfix-content-review';

		// Step 2: Validate required data
		if ( $account_id === '' || $session_id === '' ) {
			return array(
				'success' => false,
				'error'   => __( 'Account ID or session ID is missing. Please create account first.', 'spotfix-content-review' ),
			);
		}

		// Step 3: Add/configure account
		$account_result = self::addAccount( $account_id, $session_id, $org_name );

		if ( ! $account_result['success'] ) {
			return $account_result;
		}

		$new_account_id = $account_result['account_id'];
		self::update_api_data( array( 'account_id' => $new_account_id ) );

		// Step 4: Add project
		$project_add_result = self::addProject( $new_account_id, $session_id, $plugin_name );

		if ( ! $project_add_result['success'] ) {
			return $project_add_result;
		}

		$project_id = $project_add_result['project_id'];
		self::update_api_data( array( 'project_id' => $project_id ) );

		// Step 5: Get project details (including token)
		$project_get_result = self::getProject( $new_account_id, $session_id, $plugin_name );

		if ( ! $project_get_result['success'] ) {
			return $project_get_result;
		}

		$project_token = $project_get_result['project_token'];
		self::update_api_data( array( 'project_token' => $project_token ) );

		// Step 6: Build and save snippet code
		$snippet_code = self::buildSnippetCode();
		self::saveSnippetToSettings( $snippet_code );

		// Step 7: Return success response
		return array(
			'success'     => true,
			'message'     => __( 'Account configured successfully!', 'spotfix-content-review' ),
			'account_add' => $account_result['response'],
			'project_add' => $project_add_result['response'],
			'project_get' => $project_get_result['response'],
		);
	}
}
