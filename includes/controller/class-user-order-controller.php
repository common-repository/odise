<?php

namespace Odise\Controller;

class User_Order_Controller {
	/**
	 * refer -> wc_customer_bought_product
	 *
	 * @param mixed $email
	 * @return bool
	 */
	public function user_has_ordered_by_email( $email ) {
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return false;
		}

		return $this->user_has_ordered_by_user( $user->ID );
	}

	public function get_user_order_count( $user_id ) {
		if ( ! \Odise\is_woocommerce_active() ) {
			return 0;
		}

		if ( ! $user_id ) {
			return 0;
		}

		return wc_get_customer_order_count( $user_id );
	}

	public function user_has_ordered_by_user( $user_id ) {
		$result = $this->get_user_order_count( $user_id );

		return $result > 0;
	}
}
