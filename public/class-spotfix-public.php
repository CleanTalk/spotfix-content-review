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

		// Check if DISALLOW_UNFILTERED_HTML is enabled - if so, don't load JS
		if ( defined( 'DISALLOW_UNFILTERED_HTML' ) && DISALLOW_UNFILTERED_HTML ) {
			return;
		}

		$settings = get_option( 'spotfix_settings', array() );
		$code = isset( $settings['code'] ) ? $settings['code'] : '';
		$visibility = isset( $settings['visibility'] ) ? $settings['visibility'] : 'everyone';

		// Check if code is configured
		if ( empty( $code ) ) {
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

		// Output the Spotfix code
		// Note: Code is output as-is since it's user-provided JavaScript from admin settings
		?>
		<!-- Spotfix Widget -->
		<script type="text/javascript">
		<?php echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</script>
		<?php
	}
}

