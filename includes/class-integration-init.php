<?php

namespace Odise;

class Integration_Init {

	public function get_status( $request ) {

		$site_id = sanitize_text_field( $request->get_param( 'site_id' ) );
		$email   = sanitize_text_field( $request->get_param( 'email' ) );

		if ( empty( $site_id ) || empty( $email ) ) {
			return false;
		}

		if ( \Odise\is_integrated() ) {
			$current_site_id = get_option( 'odise_site_id', false );

			if ( intval( $site_id ) !== intval( $current_site_id ) ) {
				return array(
					'status' => 'wrong_site_id',
				);
			}

			return array(
				'status' => 'set',
			);
		}

		delete_transient( 'odise_integration_info' );
		$this->set_initial_info( $site_id, $email );

		if ( \Odise\is_integration_initiated() ) {
			return array(
				'status' => 'exists',
			);
		}

		return array(
			'status' => 'new',
		);
	}

	public function set_initial_info( $site_id, $email ) {
		$info = array(
			'site_id' => $site_id,
			'email'   => $email,
		);

		set_transient( 'odise_integration_info', $info, 10 * MINUTE_IN_SECONDS );
	}
}
