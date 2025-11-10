(function($) {
	'use strict';

	$(document).ready(function() {
		// Check status button handler
		$('#spotfix-check-status').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var $statusIndicator = $('.spotfix-status-indicator');
			var $errorMessage = $('.spotfix-error-message');
			
			$button.prop('disabled', true).text('Checking...');
			
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
							if ($errorMessage.length) {
								$errorMessage.text(error);
							} else {
								$statusIndicator.after('<p class="spotfix-error-message">' + error + '</p>');
							}
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
					$button.prop('disabled', false).text('Check Status');
				}
			});
		});
	});
})(jQuery);

