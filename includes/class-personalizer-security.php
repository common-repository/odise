<?php

namespace Odise;

/**
 * Class Personalizer_Security
 *
 * Adjusts the XFO/CSP headers to make content personalization work in Odise UI.
 *
 * @package Odise
 */
class Personalizer_Security {
	public function __construct() {
		if ( 'local' === ODISE_APP_ENV || 'dev' === ODISE_APP_ENV ) {
			return;
		}

		add_filter( 'send_headers', array( $this, 'adjust_personalizer_security_headers' ), 90, 0 );
	}

	public function adjust_personalizer_security_headers() {
		if ( ! $this->should_override_headers() ) {
			return;
		}

		header_remove( 'X-Frame-Options' );
		header( "Content-Security-Policy: frame-ancestors 'self' *.odise.io" );
	}

	private function should_override_headers() {
		return filter_input( INPUT_GET, 'odise-mode' ) === 'personalize' &&
			filter_input( INPUT_GET, 'spid' ) > 0;
	}
}
