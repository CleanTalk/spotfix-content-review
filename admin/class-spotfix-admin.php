<?php
/**
 * The admin-specific functionality of the plugin.
 */
class Spotfix_Admin {

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();
		if ( $screen->id === 'settings_page_spotfix-settings' ) {
			wp_enqueue_style( 'spotfix-admin', SPOTFIX_PLUGIN_URL . 'admin/css/spotfix-admin.css', array(), SPOTFIX_VERSION, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( $screen->id === 'settings_page_spotfix-settings' ) {
			wp_enqueue_script( 'spotfix-admin', SPOTFIX_PLUGIN_URL . 'admin/js/spotfix-admin.js', array( 'jquery' ), SPOTFIX_VERSION, false );
			wp_localize_script( 'spotfix-admin', 'spotfixAdmin', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'spotfix_check_status' )
			) );
		}
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			'Spotfix Settings',
			'Spotfix',
			'manage_options',
			'spotfix-settings',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting( 'spotfix_settings_group', 'spotfix_settings', array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'spotfix_general_section',
			'',
			array( $this, 'render_general_section' ),
			'spotfix-settings'
		);

		add_settings_field(
			'spotfix_code',
			'Spotfix Code',
			array( $this, 'render_code_field' ),
			'spotfix-settings',
			'spotfix_general_section'
		);

		add_settings_field(
			'spotfix_status',
			'Status',
			array( $this, 'render_status_field' ),
			'spotfix-settings',
			'spotfix_general_section'
		);

		add_settings_field(
			'spotfix_visibility',
			'How work with the widget?',
			array( $this, 'render_visibility_field' ),
			'spotfix-settings',
			'spotfix_general_section'
		);
	}

	/**
	 * Sanitize settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['code'] ) ) {
			$sanitized['code'] = $input['code']; // Allow JavaScript code
		}

		if ( isset( $input['visibility'] ) ) {
			$allowed = array( 'everyone', 'logged_in', 'admin' );
			$sanitized['visibility'] = in_array( $input['visibility'], $allowed ) ? $input['visibility'] : 'everyone';
		}

		// Check status when code is saved
		if ( isset( $sanitized['code'] ) ) {
			$status_check = Spotfix_Status_Checker::check_status( $sanitized['code'] );
			$sanitized['status'] = $status_check['status'];
			$sanitized['error'] = $status_check['error'];
		} else {
			$current_settings = get_option( 'spotfix_settings', array() );
			$sanitized['status'] = isset( $current_settings['status'] ) ? $current_settings['status'] : 'offline';
			$sanitized['error'] = isset( $current_settings['error'] ) ? $current_settings['error'] : '';
		}

		return $sanitized;
	}

	/**
	 * Render general section.
	 */
	public function render_general_section() {
		echo sprintf( '<h3>%s</h3>', __( 'Spotfix - proofreading, spell and grammar check by visitors', 'spell-grammar-typo-check' ) );
		echo sprintf( '<p>%s</p>', __( 'Collect questions, suggestions, and fix content directly on website pages.', 'spell-grammar-typo-check' ) );
	}

	/**
	 * Render code field.
	 */
	public function render_code_field() {
		$settings = get_option( 'spotfix_settings', array() );
		$code = isset( $settings['code'] ) ? $settings['code'] : '';
		$example_code = "(function () {\n      let apbctScript = document.createElement('script');\n      apbctScript.type = 'text/javascript';\n      apbctScript.async = \"true\";\n      apbctScript.src = 'https://doboard.com/1.0.0/spotfix.min.js?projectToken=4d335d7b8eff587d9002d90db78f90b6&projectId=103&accountId=1'; \n      let firstScriptNode = document.getElementsByTagName('script')[0];\n      firstScriptNode.parentNode.insertBefore(apbctScript, firstScriptNode);\n    })();";
		?>
		<textarea name="spotfix_settings[code]" rows="8" class="large-text code-textarea" id="spotfix-code"><?php echo esc_textarea( $code ); ?></textarea>
		<p class="description">Example code:</p>
		<pre class="code-example"><code><?php echo esc_html( $example_code ); ?></code></pre>
		<?php
	}

	/**
	 * Render status field.
	 */
	public function render_status_field() {
		$settings = get_option( 'spotfix_settings', array() );
		$status = isset( $settings['status'] ) ? $settings['status'] : 'offline';
		$error = isset( $settings['error'] ) ? $settings['error'] : '';
		?>
		<div class="spotfix-status-container">
			<span class="spotfix-status-indicator status-<?php echo esc_attr( $status ); ?>">
				<span class="status-dot"></span>
				<strong><?php echo sprintf( __( 'Spotfix is %s', 'spell-grammar-typo-check' ), esc_html( $status ) ); ?></strong>
			</span>
			<?php if ( $status === 'offline' && ! empty( $error ) ) : ?>
				<p class="spotfix-error-message"><?php echo esc_html( $error ); ?></p>
			<?php endif; ?>
			<button type="button" class="button button-secondary" id="spotfix-check-status"><?php _e( 'Check Status', 'spell-grammar-typo-check' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render visibility field.
	 */
	public function render_visibility_field() {
		$settings = get_option( 'spotfix_settings', array() );
		$visibility = isset( $settings['visibility'] ) ? $settings['visibility'] : 'everyone';
		?>
		<p class="description"><?php _e( 'Choose scope of visibility for the widget.', 'spell-grammar-typo-check' ); ?></p>
		<fieldset>
			<label>
				<input type="radio" name="spotfix_settings[visibility]" value="everyone" <?php checked( $visibility, 'everyone' ); ?> />
				<?php _e( 'Everyone including unauthorized visitors.', 'spell-grammar-typo-check' ); ?>
			</label><br>
			<label>
				<input type="radio" name="spotfix_settings[visibility]" value="logged_in" <?php checked( $visibility, 'logged_in' ); ?> />
				<?php _e( 'Authorized in WordPress users.', 'spell-grammar-typo-check' ); ?>
			</label><br>
			<label>
				<input type="radio" name="spotfix_settings[visibility]" value="admin" <?php checked( $visibility, 'admin' ); ?> />
				<?php _e( 'Users with the admin role.', 'spell-grammar-typo-check' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e( 'The widget is visible only on public pages of WordPress.', 'spell-grammar-typo-check' ); ?></p>
		<?php
	}

	/**
	 * Display settings page.
	 */
	public function display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = get_option( 'spotfix_settings', array() );
		?>
		<div class="wrap">
			<h1><?php echo sprintf( '<h1>%s</h1>', esc_html( get_admin_page_title() ) ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'spotfix_settings_group' );
				do_settings_sections( 'spotfix-settings' );
				submit_button( __( 'Save Settings', 'spell-grammar-typo-check' ) );
				?>
			</form>

			<div class="spotfix-info-section">
				<h2><?php _e( 'Instructions', 'spell-grammar-typo-check' ); ?></h2>
				<p><?php _e( 'Instructions to obtain the code <a href="https://doboard.com/spotfix#doboard_settings" target="_blank">doboard.com/spotfix#doboard_settings</a>.', 'spell-grammar-typo-check' ); ?></p>
				<p><?php _e( 'To run the widget, you need a <a href="https://doboard.com" target="_blank">doBoard account</a>. doBoard is the task management system that serves as the backend for Spotfix.', 'spell-grammar-typo-check' ); ?></p>
				<p><?php _e( 'Have questions? We are ready to support you at <a href="https://wordpress.org/support/plugin/spell-grammar-typo-check" target="_blank">wordpress.org/support/plugin/spell-grammar-typo-check</a>.', 'spell-grammar-typo-check' ); ?></p>
				<p><?php _e( 'Like the plugin? Review us please <a href="https://wordpress.org/support/plugin/spell-grammar-typo-check/reviews" target="_blank">wordpress.org/support/plugin/spell-grammar-typo-check/reviews</a>.', 'spell-grammar-typo-check' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for status check.
	 */
	public function ajax_check_status() {
		check_ajax_referer( 'spotfix_check_status', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'spell-grammar-typo-check' ) ) );
		}

		$settings = get_option( 'spotfix_settings', array() );
		$code = isset( $settings['code'] ) ? $settings['code'] : '';

		$status_check = Spotfix_Status_Checker::check_status( $code );

		// Update settings with new status
		$settings['status'] = $status_check['status'];
		$settings['error'] = $status_check['error'];
		update_option( 'spotfix_settings', $settings );

		wp_send_json_success( $status_check );
	}
}
