(function($) {
	'use strict';

	$(document).ready(function() {
		// Check status link handler
		$('#spotfix-check-status').on('click', function(e) {
			e.preventDefault();
			
			var $link = $(this);
			var $statusIndicator = $('.spotfix-status-indicator');
			var $errorMessage = $('.spotfix-error-message');
			
			if ($link.hasClass('checking')) {
				return false;
			}
			
			$link.addClass('checking').text('Checking...');
			
			$.ajax({
				url: spotfixAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'spotfix_check_status',
					nonce: spotfixAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						var status = response.data.status;
						var error = response.data.error || '';
						
						// Update status indicator
						$statusIndicator
							.removeClass('status-online status-offline')
							.addClass('status-' + status);
						
						$statusIndicator.find('strong').text('Spotfix is ' + status);
						
						// Update error message
						if (status === 'offline' && error) {
							var newError = $('<p class="spotfix-error-message"></p>').text(error);
							$statusIndicator.after(newError);
						} else {
							$errorMessage.remove();
						}
					} else {
						alert('Error checking status: ' + (response.data.message || 'Unknown error'));
					}
				},
				error: function() {
					alert('Failed to check status. Please try again.');
				},
				complete: function() {
					$link.removeClass('checking').text('Check Status');
				}
			});
		});

		// Create account button handler
		$('#spotfix-create-account').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var $spinner = $('#spotfix-create-spinner');
			var $message = $('#spotfix-setup-message');
			
			if ($button.prop('disabled')) {
				return false;
			}
			
			$button.prop('disabled', true);
			$spinner.addClass('is-active');
			$message.html('').hide();
			
			$.ajax({
				url: spotfixAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'spotfix_create_account',
					nonce: spotfixAdmin.nonceCreateAccount
				},
				success: function(response) {
					console.log(response);
					if (response.success) {
						$message
							.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>')
							.show();
						if (response.data.session_id) {
							$('#spotfix-configure-account-block').css('display', 'block');
						}
					} else {
						$message
							.html('<div class="notice notice-error inline"><p>' + (response.data.error || 'Unknown error') + '</p></div>')
							.show();
						$button.prop('disabled', false);
					}
				},
				error: function() {
					$message
						.html('<div class="notice notice-error inline"><p>Failed to connect. Please try again.</p></div>')
						.show();
					$button.prop('disabled', false);
				},
				complete: function() {
					$spinner.removeClass('is-active');
				}
			});
		});
		
		// Configure Account button handler
		$('#spotfix-configure-account').on('click', function(e) {
			e.preventDefault();
			var $button = $(this);
			var $spinner = $('#spotfix-configure-spinner');
			var $message = $('#spotfix-setup-message');

			if ($button.prop('disabled')) {
				return false;
			}

			$button.prop('disabled', true);
			$spinner.addClass('is-active');
			$message.html('').hide();

			$.ajax({
				url: spotfixAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'spotfix_configure_account',
					nonce: spotfixAdmin.nonceConfigureAccount
				},
				success: function(response) {
					console.log(response);
					if (response.success) {
						if(response.data.account_add.error_no == 1401 && response.data.account_add.error_message) {
							$message
								.html(
									'<div class="notice notice-error inline"><p>' +
									(response.data.account_add.error_message || 'Waiting for email confirmation') +
									'</p></div>'
								).show();
							$button.prop('disabled', false);
						} else {
							$message
								.html('<div class="notice notice-success inline"><p>' + (response.data.message || 'Account configured successfully!') + '</p></div>')
								.show();
						}
					} else {
						$message
							.html('<div class="notice notice-error inline"><p>' + (response.data.error || 'Unknown error') + '</p></div>')
							.show();
						$button.prop('disabled', false);
					}
				},
				error: function() {
					$message
						.html('<div class="notice notice-error inline"><p>Failed to connect. Please try again.</p></div>')
						.show();
					$button.prop('disabled', false);
				},
				complete: function() {
					$spinner.removeClass('is-active');
				}
			});
		});
	});
})(jQuery);

