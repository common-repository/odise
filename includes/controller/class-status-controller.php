<?php

namespace Odise\Controller;

class Status_Controller {
	public function status( $request ) {

		if ( $request->get_header( 'X-Site-ID' ) != get_option( 'odise_site_id' ) ) { // phpcs:ignore
			return new \WP_Error( 'site_id_not_valid', esc_html__( 'Invalid Site ID', 'odise' ), array( 'status' => 403 ) );
		}

		try {
			$is_woo_commerce_active = \Odise\is_woocommerce_active();
		} catch ( \Throwable $throwable ) {
			$is_woo_commerce_active = false;
		}

		global $wp_version;

		// Basic information, versions.
		$info = array(
			'active'              => true,
			'woocommerce'         => $is_woo_commerce_active,

			'site_locale'         => get_locale(),
			'permalink_structure' => get_option( 'permalink_structure' ),

			'wp_version'          => $wp_version,
			'odise_version'       => ODISE_VERSION,
			'php_version'         => phpversion(),
		);

		// Theme information.
		try {
			$theme = wp_get_theme();
			if ( $theme && $theme->exists() ) {
				$info['theme_name']    = $theme->get( 'Name' );
				$info['theme_version'] = $theme->get( 'Version' );
			}
		} catch ( \Throwable $throwable ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Not handling the exception; do not include the info.
		}

		// WooCommerce information.
		try {
			if ( $is_woo_commerce_active ) {
				global $woocommerce;

				$info['currency']        = get_woocommerce_currency();
				$info['currency_symbol'] = get_woocommerce_currency_symbol();
				$info['wc_version']      = $woocommerce->version;
			}
		} catch ( \Throwable $throwable ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Not handling the exception; do not include the info.
		}

		return $info;
	}
}
