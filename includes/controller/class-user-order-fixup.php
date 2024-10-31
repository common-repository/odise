<?php
/**
 * Contains the User_Order_Fixup class.
 *
 * @package Odise\Controller
 */

namespace Odise\Controller;

/**
 * Class User_Order_Fixup
 *
 * Associate past orders to new users. Part of OD-507.
 *
 * @see \Odise\Controller\Woocommerce_Checkout_Controller::associate_past_orders
 *
 * @package Odise\Controller
 */
class User_Order_Fixup {
	/**
	 * User_Order_Fixup constructor.
	 */
	public function __construct() {
		add_action( 'user_register', array( $this, 'associate_past_orders' ), 20, 1 );
	}

	/**
	 * Link past orders to this customer if he has done any orders as a guest before
	 *
	 * @param $user_id
	 */
	public function associate_past_orders( $user_id ) {
		if ( ! function_exists( 'wc_update_new_customer_past_orders' ) ) {
			return;
		}

		wc_update_new_customer_past_orders( $user_id );
	}
}
