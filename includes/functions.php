<?php


namespace Odise;

function get_user_type() {
	global $wpdb;

	if ( ! is_user_logged_in() ) {
		return 'guest';
	}

	if ( is_multisite() ) {
		if ( ! is_user_member_of_blog( get_current_user_id(), get_current_blog_id() ) ) {
			return 'guest';
		}
	}

	if ( ! is_woocommerce_active() ) {
		return 'lead';
	}

	/**
	 * Check if user has order or not.
	 *
	 * Has builtin cache (user meta).
	 * @link https://docs.woocommerce.com/wc-apidocs/source-class-WC_Customer_Data_Store.html#349-377
	 */
	return wc_get_customer_order_count( get_current_user_id() ) > 0 ? 'customer' : 'lead';
}

function is_woocommerce_active() {
	return did_action( 'woocommerce_loaded' );
}

function starts_with( $haystack, $needle ) {
	return substr( $haystack, 0, strlen( $needle ) ) === $needle;
}

function error_log( $log ) {
	if ( is_array( $log ) || is_object( $log ) ) {
		\error_log( print_r( $log, true ) ); // phpcs:ignore
		return;
	}

	\error_log( $log ); // phpcs:ignore
}

/**
 * Returns a string to be used as the meta key prefix for Odise meta entries in places where the meta must only be
 * available for a specific site and must not be synced to other sites. As an example if tenant re-integrates with a new
 * site id, we don't want to use the old site's purchase session ids with the new site. Therefore append site_id to
 * order metas and other metas which should not survive on changing sites.
 *
 * @return string
 */
function meta_site_prefix() {
	$site_id = get_option( 'odise_site_id' );
	return "_odise_{$site_id}";
}

function is_integrated() {
	return intval( get_option( 'odise_site_id', false ) );
}

function is_integration_initiated() {
	return intval( get_transient( 'odise_integration_info', false ) ) || is_integrated();
}

function get_relative_permalink( $post ) {
	$permalink = get_permalink( $post );
	$url       = str_replace( home_url(), '', $permalink );

	return rtrim( $url, '/' );
}
