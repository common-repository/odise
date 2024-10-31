<?php

namespace Odise;

class Assets_Manager {

	public $suffix = '.min';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ), 9 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ), 9 );

		if ( $this->is_script_debug() ) {
			$this->suffix = '';
		}
	}

	public static function is_script_debug() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}

	public function register_public_assets() {
		$this->register_public_scripts();
	}

	public function register_public_scripts() {
		wp_register_script( 'odise-if-then', ODISE_RULE_CLIENT_URL, array( 'jquery' ), ODISE_VERSION, true );
	}

	public function register_admin_assets() {
		$this->register_admin_scripts();
		$this->register_admin_styles();
	}

	public function register_admin_scripts() {
		wp_register_script( 'odise-admin', ODISE_ADMIN_ASSETS . 'js/odise-admin' . $this->suffix . '.js', array( 'jquery' ), ODISE_VERSION, false );
	}

	public function register_admin_styles() {
		wp_register_style( 'odise-admin', ODISE_ADMIN_ASSETS . 'css/odise-admin' . $this->suffix . '.css', array(), ODISE_VERSION, 'all' );
	}
}
