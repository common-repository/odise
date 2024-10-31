<?php

/**
 * Plugin Name:       Odise
 * Plugin URI:        odise.io
 * Description:       Odise is a marketing automation assistant to help you understand your users’ behavior, personalize their experience across their journey and help you acquire, nurture, convert and retain them.
 * Version:           1.0.20
 * Author:            Artbees
 * Author URI:        artbees.net
 * License:           GPL-3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       odise
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once 'vendor/autoload.php';

register_activation_hook( __FILE__, 'odise_activation' );
new Odise\Rewrite_Rules_Manager();

function odise_activation() {
	set_transient( 'odise_activation_redirect', true, MINUTE_IN_SECONDS );
}

add_action( 'plugins_loaded', 'odise_load' );

function odise_load() {
	new Odise\Admin\Init();
	new Odise\Assets_Manager();

	if( ! Odise\is_integrated() ) {
		return;
	}

	new Odise\Front\Init();
	new Odise\Page_Webhook();
	new Odise\Controller\User_Order_Fixup();
	new Odise\Controller\Woocommerce_Checkout_Controller();
	new Odise\Controller\Redirect_Controller();
	new Odise\Controller\Unsubscribe_Controller();
	new Odise\Coupon_Handler();
	new Odise\Modification_Handler();
	new Odise\Personalizer_Security();
}
