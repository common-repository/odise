<?php

namespace Odise\Admin;

class Init {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'activation_redirect' ) );
		add_action( 'admin_init', array( $this, 'check_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_options_page() {
		add_options_page(
			esc_html__( 'Odise Settings', 'odise' ),
			esc_html__( 'Odise', 'odise' ),
			'manage_options',
			'odise-admin',
			array( $this, 'render_option_page' )
		);
	}

	public function render_option_page() {

		if ( \Odise\is_integrated() ) {
			include_once 'views/integrated.php';
			return;
		}

		if ( \Odise\is_integration_initiated() ) {
			include_once 'views/agreement.php';
			return;
		}

		include_once 'views/welcome.php';
	}

	public function enqueue_assets( $hook ) {
		if ( 'settings_page_odise-admin' === $hook ) {
			wp_enqueue_script( 'odise-admin' );
			wp_enqueue_style( 'odise-admin' );

			$params = array(
				'nonce'       => wp_create_nonce('odise_ajax'),
				'waitMessage' => sprintf(
					'%s <a href="http://odise.io" target="_blank">%s</a>.',
					esc_html__( 'Please complete your registration in', 'odise' ),
					esc_html__( 'Odise', 'odise' )
				),
			);

			if ( \Odise\is_integration_initiated() ) {
				$params['siteID'] = true;
			}

			wp_localize_script( 'odise-admin', 'odise', $params );
		}
	}

	public function activation_redirect() {
		if ( ! get_transient( 'odise_activation_redirect' ) ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			return;
		}

		delete_transient( 'odise_activation_redirect' );

		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=odise-admin' ) );

		exit;
	}

	public function check_settings() {
		if ( ! isset( $_GET['odise_nonce'], $_GET['odise-remove-integration'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['odise_nonce'], 'odise_remove_integration' ) ) {
			return;
		}

		if ( sanitize_text_field( $_GET['odise-remove-integration'] ) ) {
			delete_option( 'odise_site_id' );
		}

	}
}
