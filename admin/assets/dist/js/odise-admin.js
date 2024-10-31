'use strict';

(function ($) {
	'use strict';

	$(function () {
		if ('undefined' === typeof odise.siteID) {
			var integrationCheckInterval = setInterval(function () {
				$.post(ajaxurl, {
					action: 'odise_integration_check',
					nonce: odise.nonce
				}, function (res) {
					if (res.success) {
						clearInterval(integrationCheckInterval);
						document.location.reload();
						return;
					}
				});
			}, 2000);

			setTimeout(function () {
				$('.wrap h3').html(odise.waitMessage);
			}, 11000);
		}

		$('.wrap').on('click', '.odise-confirm', function (e) {
			e.preventDefault();
			$.post(ajaxurl, {
				action: 'odise_integrate',
				nonce: odise.nonce
			}, function (res) {
				if (res.success) {
					document.location.reload();
					return;
				}
			});
		});

		$('.wrap').on('click', '.odise-remove-integration', function (e) {
			e.preventDefault();
			$.post(ajaxurl, {
				action: 'odise_remove_integration',
				nonce: odise.nonce
			}, function (res) {
				if (res.success) {
					document.location.reload();
					return;
				}
			});
		});
	});
})(jQuery);