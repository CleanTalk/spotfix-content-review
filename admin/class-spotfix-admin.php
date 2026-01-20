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
				'nonce' => wp_create_nonce( 'spotfix_check_status' ),
				'nonceCreateAccount' => wp_create_nonce( 'spotfix_create_account' ),
				'nonceConfigureAccount' => wp_create_nonce( 'spotfix_configure_account' )
			) );
		}
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			'Spotfix',
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
			'Frontend code',
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
		// Check if DISALLOW_UNFILTERED_HTML is enabled
		$disallow_unfiltered_html = defined( 'DISALLOW_UNFILTERED_HTML' ) && DISALLOW_UNFILTERED_HTML;

		if ( $disallow_unfiltered_html ) {
			$title = __( '️DISALLOW_UNFILTERED_HTML is active', 'spotfix-content-review' );
			$description = __( 'The DISALLOW_UNFILTERED_HTML constant is currently enabled, which prevents JavaScript code execution for all user roles. This means the Spotfix widget will not work on the public part of your site. The JavaScript code will not be loaded on the frontend.', 'spotfix-content-review' );
			?>
			<div class="notice notice-error spotfix-unfiltered-html-notice">
				<p class="spotfix-unfiltered-html-title"><?php echo esc_html( $title ); ?></p>
				<p class="spotfix-unfiltered-html-description"><?php echo esc_html( $description ); ?></p>
			</div>
			<?php
		}

		echo sprintf( '<h3>%s</h3>', esc_html__( 'Proofreading, spelling and grammar reviews by visitors', 'spotfix-content-review' ) );
		echo sprintf( '<p>%s</p>', esc_html__( 'Collect questions, suggestions, and fix content directly on website pages.', 'spotfix-content-review' ) );
	}

	/**
	 * Render code field.
	 */
	public function render_code_field() {
		$settings = get_option( 'spotfix_settings', array() );
		$code = isset( $settings['code'] ) ? $settings['code'] : '';
		$example_code = "(function () {\n      let apbctScript = document.createElement('script');\n      apbctScript.type = 'text/javascript';\n      apbctScript.async = \"true\";\n      apbctScript.src = 'https://spotfix.doboard.com/doboard-widget-bundle.min.js'; \n      let firstScriptNode = document.getElementsByTagName('script')[0];\n      firstScriptNode.parentNode.insertBefore(apbctScript, firstScriptNode);\n    })();";
		
		// Show auto-setup section if code is not configured
		if ( empty( $code ) ) :
			$admin_email = get_option( 'admin_email' );
		?>
		<div class="spotfix-auto-setup" style="margin-bottom: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
			<h4 style="margin-top: 0; margin-bottom: 10px;"><?php esc_html_e( 'Automatic Setup', 'spotfix-content-review' ); ?></h4>
			<p style="margin-bottom: 15px;">
				<?php 
				printf(
					esc_html__( 'Click the button below to automatically create a doBoard account using your admin email (%s) and configure the widget.', 'spotfix-content-review' ),
					'<strong>' . esc_html( $admin_email ) . '</strong>'
				);
				?>
			</p>

			<div id="spotfix-create-account-block">
				<button type="button" id="spotfix-create-account" class="button button-primary">
					<?php esc_html_e( 'Create Account', 'spotfix-content-review' ); ?>
				</button>
				<span id="spotfix-create-spinner" class="spinner" style="float: none;"></span>
			</div>

			<div id="spotfix-configure-account-block" style="margin-top:15px; display:none;">
				<button type="button" id="spotfix-configure-account" class="button button-secondary">
					<?php esc_html_e( 'Configure Account', 'spotfix-content-review' ); ?>
				</button>
				<span id="spotfix-configure-spinner" class="spinner" style="float: none;"></span>
			</div>

			<div id="spotfix-setup-message" style="margin-top: 10px;"></div>
		</div>
		<p class="description" style="margin-bottom: 10px;"><?php esc_html_e( 'Or paste your code manually:', 'spotfix-content-review' ); ?></p>
		<?php endif; ?>
		
		<textarea name="spotfix_settings[code]" rows="8" class="large-text code-textarea" id="spotfix-code"<?php echo empty( $code ) ? ' placeholder="' . esc_attr( $example_code ) . '"' : ''; ?>><?php echo esc_textarea( $code ); ?></textarea>
		<?php if ( empty( $code ) ) : ?>
			<div class="spotfix-instructions-section" style="margin-top: 20px; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'What should I do next?', 'spotfix-content-review' ); ?></h3>
				<ul style="list-style-type: disc; margin-left: 20px;">
					<li><?php
						echo wp_kses_post(
							sprintf(
								/* translators: %1$s: Link to doboard, %2$s: Link to signup */
								__( 'Go to %1$s → Account → ANY_PROJECT → Settings → Spotfix. Don\'t have a doBoard account yet? %2$s', 'spotfix-content-review' ),
								'<a href="https://doboard.com/?utm_source=spotfix-pllugin&utm_medium=settings&utm_campaign=spotfix&utm_content=instruction" target="_blank" rel="noopener noreferrer">doboard.com</a>',
								'<a href="https://doboard.com/signup?utm_source=spotfix-pllugin&utm_medium=settings&utm_campaign=spotfix&utm_content=instruction" target="_blank" rel="noopener noreferrer">Signup here</a>'
							)
						);
					?></li>
					<li><?php esc_html_e( 'Copy the code and paste it into the area above.', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'Click Save settings.', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'You should see the status "Spotfix is online." Congratulations!', 'spotfix-content-review' ); ?></li>
					<li><?php
						echo wp_kses_post(
							sprintf(
								/* translators: %s: Link to home page */
								__( 'Go to the %s', 'spotfix-content-review' ),
								'<a href="' . esc_url( home_url() ) . '" target="_blank" rel="noopener noreferrer">home page</a>'
							)
						);
					?></li>
					<li><?php esc_html_e( 'Select (mark) any text or image on the page.', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'Click the Review content button in the bottom-right corner.', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'Post your first spot!', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'Find the task created from the spot in doboard.com → Account → ANY_PROJECT.', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'Done!', 'spotfix-content-review' ); ?></li>
				</ul>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render status field.
	 */
	public function render_status_field() {
		$settings = get_option( 'spotfix_settings', array() );
		$status = isset( $settings['status'] ) ? $settings['status'] : 'offline';
		$error = isset( $settings['error'] ) ? $settings['error'] : '';
		$code = isset( $settings['code'] ) ? $settings['code'] : '';
		?>
		<div class="spotfix-status-container">
			<span class="spotfix-status-indicator status-<?php echo esc_attr( $status ); ?>">
				<span class="status-dot"></span>
				<strong><?php echo esc_html__( 'Spotfix is ', 'spotfix-content-review' ) . esc_html( $status ); ?></strong>
			</span>
			<?php if ( $status === 'offline' && ! empty( $error ) ) : ?>
				<p class="spotfix-error-message"><?php echo esc_html( $error ); ?></p>
			<?php endif; ?>
			<a href="#" class="spotfix-check-status-link" id="spotfix-check-status"><?php esc_html_e( 'Check Status', 'spotfix-content-review' ); ?></a>
		</div>
		<?php if ( ! empty( $code ) && $status === 'online' ) : ?>
			<div class="spotfix-instructions-section" style="margin-top: 20px; padding: 20px; background: #f0f6fc; border-left: 4px solid #2271b1;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'What should I do next?', 'spotfix-content-review' ); ?></h3>
				<ul style="list-style-type: disc; margin-left: 20px;">
					<li><?php
						echo wp_kses_post(
							sprintf(
								/* translators: %s: Link to home page */
								__( 'Go to the %s', 'spotfix-content-review' ),
								'<a href="' . esc_url( home_url() ) . '" target="_blank" rel="noopener noreferrer">home page</a>'
							)
						);
					?></li>
					<li><?php esc_html_e( 'Select (mark) any text or image on the page.', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'Click the Review content button in the bottom-right corner.', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'Post your first spot!', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'Find the task created from the spot in doboard.com → Account → ANY_PROJECT.', 'spotfix-content-review' ); ?></li>
					<li><?php esc_html_e( 'Done!', 'spotfix-content-review' ); ?></li>
				</ul>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render visibility field.
	 */
	public function render_visibility_field() {
		$settings = get_option( 'spotfix_settings', array() );
		$visibility = isset( $settings['visibility'] ) ? $settings['visibility'] : 'everyone';
		?>
		<p class="description"><?php esc_html_e( 'Choose scope of visibility for the widget.', 'spotfix-content-review' ); ?></p>
		<fieldset>
			<label>
				<input type="radio" name="spotfix_settings[visibility]" value="everyone" <?php checked( $visibility, 'everyone' ); ?> />
				<?php esc_html_e( 'Everyone including unauthorized visitors.', 'spotfix-content-review' ); ?>
			</label><br>
			<label>
				<input type="radio" name="spotfix_settings[visibility]" value="logged_in" <?php checked( $visibility, 'logged_in' ); ?> />
				<?php esc_html_e( 'Authorized in WordPress users.', 'spotfix-content-review' ); ?>
			</label><br>
			<label>
				<input type="radio" name="spotfix_settings[visibility]" value="admin" <?php checked( $visibility, 'admin' ); ?> />
				<?php esc_html_e( 'Users with the admin role.', 'spotfix-content-review' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'The widget is visible only on public pages of WordPress.', 'spotfix-content-review' ); ?></p>
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
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'spotfix_settings_group' );
				do_settings_sections( 'spotfix-settings' );
				submit_button( __( 'Save Settings', 'spotfix-content-review' ) );
				?>
			</form>

			<div class="spotfix-info-section">
				<h2><?php esc_html_e( 'Instructions', 'spotfix-content-review' ); ?></h2>
				<p><?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Link to doboard settings */
							__( 'Instructions to obtain the code %s', 'spotfix-content-review' ),
							'<a href="https://doboard.com/spotfix#doboard_settings" target="_blank" rel="noopener noreferrer">doboard.com/spotfix#doboard_settings</a>'
						)
					);
				?></p>
				<p><?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Link to doboard */
							__( 'To run the widget, you need a %s doBoard is the task management system that serves as the backend for Spotfix.', 'spotfix-content-review' ),
							'<a href="https://doboard.com" target="_blank" rel="noopener noreferrer">doBoard account</a>'
						)
					);
				?></p>
				<p><?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Link to support forum */
							__( 'Have questions? We are ready to support you at %s', 'spotfix-content-review' ),
							'<a href="https://wordpress.org/support/plugin/spotfix-content-review" target="_blank" rel="noopener noreferrer">wordpress.org/support/plugin/spotfix-content-review</a>'
						)
					);
				?></p>
				<p><?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Link to reviews */
							__( 'Like the plugin? Review us please %s', 'spotfix-content-review' ),
							'<a href="https://wordpress.org/support/plugin/spotfix-content-review/reviews" target="_blank" rel="noopener noreferrer">wordpress.org/support/plugin/spotfix-content-review/reviews</a>'
						)
					);
				?></p>
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
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'spotfix-content-review' ) ) );
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

	/**
	 * AJAX handler for creating account.
	 */
	public function ajax_create_account() {
		check_ajax_referer( 'spotfix_create_account', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error' => __( 'Unauthorized', 'spotfix-content-review' ) ) );
		}

		$result = Spotfix_API::create_account();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX handler for configuring account.
	 */
	public function ajax_configure_account() {
		check_ajax_referer( 'spotfix_configure_account', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error' => __( 'Unauthorized', 'spotfix-content-review' ) ) );
		}

		$result = Spotfix_API::configurate_account();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Add settings link to plugin actions.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=spotfix-settings' ),
			__( 'Settings', 'spotfix-content-review' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add Support and Review links to plugin row meta (after "Visit plugin site").
	 *
	 * @param array  $links Existing plugin meta links.
	 * @param string $file  Plugin file.
	 * @return array Modified plugin meta links.
	 */
	public function add_plugin_row_meta( $links, $file ) {
		if ( plugin_basename( SPOTFIX_PLUGIN_DIR . 'spotfix-content-review.php' ) === $file ) {
			// Add Support link
			$support_link = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				'https://wordpress.org/support/plugin/spotfix-content-review/',
				__( 'Support', 'spotfix-content-review' )
			);
			$links[] = $support_link;

			// Add Review link
			$review_link = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				'https://wordpress.org/support/plugin/spotfix-content-review/reviews/',
				__( 'Review', 'spotfix-content-review' )
			);
			$links[] = $review_link;
		}
		return $links;
	}
}
