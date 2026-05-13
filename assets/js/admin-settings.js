(function($) {
	'use strict';

	$(document).ready(function() {
		// Test API Keys button handler
		$('#rwdp-test-keys-btn').on('click', function(e) {
			e.preventDefault();

			const $button = $(this);
			const $spinner = $('#rwdp-test-spinner');
			const frontendKey = $('#rwdp_google_maps_api_key').val().trim();
			const serverKey = $('#rwdp_google_maps_server_key').val().trim();

			// Disable button and show spinner
			$button.prop('disabled', true);
			$spinner.show();

			// Clear previous results
			$('#rwdp-frontend-status').hide();
			$('#rwdp-server-status').hide();

			$.ajax({
				url: rwdpSettings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'rwdp_test_api_keys',
					nonce: rwdpSettings.nonce,
					frontend_key: frontendKey,
					server_key: serverKey,
				},
				success: function(response) {
					if (response.success) {
						const results = response.data;

						// Display frontend key result
						displayValidationStatus(
							'#rwdp-frontend-status',
							results.frontend
						);

						// Display server key result
						displayValidationStatus(
							'#rwdp-server-status',
							results.server
						);
					} else {
						alert(response.data.message || 'An error occurred.');
					}
				},
				error: function() {
					alert('Network error. Please try again.');
				},
				complete: function() {
					// Re-enable button and hide spinner
					$button.prop('disabled', false);
					$spinner.hide();
				},
			});
		});

		/**
		 * Display validation status for an API key.
		 *
		 * @param {string} selector - jQuery selector for the status container.
		 * @param {object} result - Validation result object.
		 */
		function displayValidationStatus(selector, result) {
			const $status = $(selector);
			const $indicator = $status.find('.rwdp-status-indicator');
			const $message = $status.find('.rwdp-status-message');
			const statusClass = result.status_class || (result.valid ? 'valid' : 'invalid');

			// Remove previous classes
			$status.removeClass('valid invalid warning');

			if (statusClass === 'valid') {
				$indicator.html('✓').css({
					'color': '#28a745',
					'font-weight': 'bold',
					'margin-right': '8px',
				});
				$message.text(result.error_message).css('color', '#28a745');
				$status.addClass('valid');
			} else if (statusClass === 'warning') {
				$indicator.html('!').css({
					'color': '#a15c00',
					'font-weight': 'bold',
					'margin-right': '8px',
				});
				$message.html(
					'<strong>' + (result.error_code || 'Notice') + ':</strong> ' +
					result.error_message
				).css('color', '#8a5a00');
				$status.addClass('warning');
			} else {
				$indicator.html('✗').css({
					'color': '#dc3545',
					'font-weight': 'bold',
					'margin-right': '8px',
				});
				$message.html(
					'<strong>' + (result.error_code || 'Error') + ':</strong> ' +
					result.error_message
				).css('color', '#dc3545');
				$status.addClass('invalid');
			}

			$status.show();
		}
	});

})(jQuery);
