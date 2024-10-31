<?php

namespace Odise\Controller;

use Odise\Event_Transport;

class Redirect_Controller {
	const OVERRIDE_REFERRER_COOKIE_NAME = '_odise_override_referrer';

	const VALID_SCHEMES = array( 'http', 'https', 'ftp' );

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'odise_template_redirect' ) );
	}

	public static function add_rewrite_rules() {
		add_rewrite_rule(
			'^odredirect/([^/]*)/(.*)$',
			'index.php?pagename=odredirect&odr_type=$matches[1]&odr_url=$matches[2]',
			'top'
		);
	}

	public static function register_redirect_query_vars( $vars ) {
		array_push(
			$vars,
			'odr_url',
			'odr_type',
			'odr_oid',
			'odr_oty',
			'odr_sid',
			'odr_rid',
			'odr_hid',
			'odr_ref',
			'odr_uid'
		);
		return $vars;
	}

	public function odise_template_redirect() {
		$url  = rawurldecode( rawurldecode( get_query_var( 'odr_url' ) ) );
		$type = get_query_var( 'odr_type' );

		if ( $url && $type ) {
			if ( ! in_array( strtok( $url, ':' ), self::VALID_SCHEMES, true ) ) {
				return;
			}
			if ( strval( get_query_var( 'odr_sid' ) ) !== strval( get_option( 'odise_site_id' ) ) ) {
				return; // Using another site id, must not work on this site
			}

			if ( '/' === $url[0] ) {
				$url         = home_url( $url );
				$is_internal = true;
			} else {
				$own_host    = wp_parse_url( get_home_url(), PHP_URL_HOST );
				$target_host = wp_parse_url( $url, PHP_URL_HOST );

				// loose comparison, ignoring scheme and port.
				$is_internal = ( $own_host === $target_host );
			}

			if ( $is_internal ) {
				// Set only for internal URLs to make sure the cookie is instantly picked up and removed by front-end.
				// If we set it for URLs heading out of the site, it will remain there on next visit to the blog and
				// will send an invalid referrer on next page load.
				$this->set_override_referrer_cookie( $type );
			} else {
				// Delete the previous cookie, if any; to not break things in case of chained internal url redirects
				$this->remove_override_referrer_cookie();

				$this->send_redirect_event( $type, $url );
			}

			wp_redirect( $url ); // phpcs:ignore
			exit();
		}
	}

	private function get_redirect_data( $type, $url = null ) {
		$redirect_data = array(
			'type' => $type,
			'oid'  => get_query_var( 'odr_oid', null ),
			'oty'  => get_query_var( 'odr_oty', null ),
			'sid'  => get_query_var( 'odr_sid', null ),
			'rid'  => get_query_var( 'odr_rid', null ),
			'hid'  => get_query_var( 'odr_hid', null ),
			'ref'  => get_query_var( 'odr_ref', null ),
			'uid'  => get_query_var( 'odr_uid', null ),
		);

		if ( isset( $url ) ) {
			$redirect_data['url'] = $url;
		}

		return $redirect_data;
	}

	private function set_override_referrer_cookie( $type ) {
		$redirect_data = wp_json_encode( $this->get_redirect_data( $type ) );
		setcookie( self::OVERRIDE_REFERRER_COOKIE_NAME, $redirect_data, 0, '/' );
	}

	private function remove_override_referrer_cookie() {
		setcookie( self::OVERRIDE_REFERRER_COOKIE_NAME, '', time() - 3600, '/' );
	}

	private function send_redirect_event( $type, $url ) {
		$event_name = 'redirect:' . $type;
		$event_data = array(
			'redirect' => $this->get_redirect_data( $type, $url ),
		);

		$transport = new Event_Transport();
		$transport->send_event( $event_name, $event_data );
	}
}
