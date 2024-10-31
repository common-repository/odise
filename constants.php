<?php

define( 'ODISE_VERSION', '1.0.20' );
define( 'ODISE_SLUG', 'odise' );

define( 'ODISE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ODISE_URL', plugin_dir_url( __FILE__ ) );

define( 'ODISE_ADMIN_PATH', ODISE_PATH . 'admin/' );
define( 'ODISE_ADMIN_URL', ODISE_URL . 'admin/' );

define( 'ODISE_PUBLIC_ASSETS', ODISE_URL . 'public/assets/dist/' );
define( 'ODISE_ADMIN_ASSETS', ODISE_ADMIN_URL . 'assets/dist/' );

if ( ! defined( 'ODISE_API_URL' ) ) {
	define( 'ODISE_API_URL', 'https://api.odise.io' );
}

if ( ! defined( 'ODISE_RULE_CLIENT_URL' ) ) {
	define( 'ODISE_RULE_CLIENT_URL', '//executor.odise.io/if-then.min.js' );
}

if ( ! defined( 'ODISE_TOKEN_VERIFY_DEBUG' ) ) {
	define( 'ODISE_TOKEN_VERIFY_DEBUG', 'no' );
}

if ( ! defined( 'ODISE_APP_ENV' ) ) {
	define( 'ODISE_APP_ENV', 'prod' );
}
