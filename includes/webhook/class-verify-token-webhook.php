<?php

namespace Odise\Webhook;

class Verify_Token_Webhook {

	public function verify_token() {
		if ( ODISE_TOKEN_VERIFY_DEBUG === 'debug' ) {
			return true;
		}

		$token = '';

		if ( isset( $_SERVER['HTTP_X_ODISE_TOKEN'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_ODISE_TOKEN'] ) );
		}

		if ( ! $token ) {
			return new \WP_Error( 'invalid_token', esc_html__( 'Token not valid', 'odise' ), array( 'status' => 400 ) );
		}

		if ( get_transient( 'odise_token' ) === $token ) {
			return true;
		}

		$response = $this->remote_call( $token );
		if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
			return new \WP_Error( 'invalid_token', esc_html__( 'Token not valid', 'odise' ), array( 'status' => 400 ) );
		}

		if ( isset( $response['ttls'] ) ) {
			$ttls = min( 5 * MINUTE_IN_SECONDS, sanitize_text_field( $response['ttls'] ) );
			set_transient( 'odise_token', $token, $ttls );
		}

		return true;
	}

	private function remote_call( $token ) {
		$url = ODISE_API_URL . '/v1/webhook/verify-token';

		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(),
				'body'        => array( 'token' => $token ),
				'cookies'     => array(),
			)
		);

		return $response;
	}
}
