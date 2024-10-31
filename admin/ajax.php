<?php

add_action( 'wp_ajax_odise_integration_check', 'odise_integration_check' );

function odise_integration_check() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'odise_ajax' ) ) {
		wp_send_json_error();
	}

	$info = get_transient( 'odise_integration_info' );

	if ( $info ) {
		wp_send_json_success();
	}

	wp_send_json_error();
}

add_action( 'wp_ajax_odise_integrate', 'odise_integrate' );

function odise_integrate() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'odise_ajax' ) ) {
		wp_send_json_error();
	}

	$info = get_transient( 'odise_integration_info' );
	if ( $info && isset( $info['site_id'] ) ) {
		update_option( 'odise_site_id', $info['site_id'] );
		wp_send_json_success();
	}

	wp_send_json_error();
}


add_action( 'wp_ajax_odise_remove_integration', 'odise_remove_integration' );

function odise_remove_integration() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'odise_ajax' ) ) {
		wp_send_json_error();
	}

	delete_transient( 'odise_integration_info' );
	delete_option( 'odise_site_id' );
	wp_send_json_success();
}
