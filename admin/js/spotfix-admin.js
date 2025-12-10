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
	});
})(jQuery);

