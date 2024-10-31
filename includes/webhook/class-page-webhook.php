<?php

namespace Odise;

class Page_Webhook {
	public function __construct() {
		add_action( 'save_post_page', array( $this, 'notify_page_changes' ), 10, 3 );
	}

	public function notify_page_changes( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->remote_call();
	}

	public function remote_call() {
		$url = ODISE_API_URL . '/v1/webhook/pages';

		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(),
				'body'        => array( 'site_id' => get_option( 'odise_site_id' ) ),
				'cookies'     => array(),
			)
		);
	}
}
