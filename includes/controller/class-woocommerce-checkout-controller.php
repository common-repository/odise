<?php
/**
 * Handles WooCommerce checkout action for both guest and logged-in users
 *
 * @package Odise
 */
// check repeated thank you after refresh.

namespace Odise\Controller;

use Odise\Event_Transport;
use Odise\Modification_Handler;
use Odise\Session_Manager;

/**
 * Class Woocommerce_Checkout_Controller
 *
 * Handles WooCommerce checkout action. Records session information and sends a checkout event to backend.
 * For logged-in users, try to associate their past guest checkouts with their new accounts.
 *
 * @package Odise
 */
class Woocommerce_Checkout_Controller {
	/**
	 * The key for order metadata specifying whether the Checkout was handled or not to prevent the action being handled
	 * and network requests multiple times. It is necessary as `woocommerce_thankyou` action may run more than once: e.g
	 * twice on initial checkout because of our old code, and then again user is able to refresh the thankyou page.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/7787
	 */
	const CHECKOUT_HANDLED_META = '_odise_checkout_handled';

	/**
	 * @var \WC_Order $order
	 */
	private $order;

	/**
	 * @var Session_Manager $session
	 */
	private $session;

	/**
	 * @var \WP_User|false $user
	 */
	private $user;

	/**
	 * Handle_Wooommerce_Checkout constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_thankyou', array( $this, 'handle_checkout' ), 10, 1 );
	}

	/**
	 * Checks if checkout is not already handled then handles it
	 *
	 * @param mixed $order_id Order ID from Woocommerce.
	 */
	public function handle_checkout( $order_id ) {
		$this->order   = new \WC_Order( $order_id );
		$this->session = new Session_Manager();

		$this->find_existing_user()
			->record_order_meta_data()
			->reset_session_id()
			->override_user_type();

		if ( ! $this->is_checkout_handled() ) {
			Modification_Handler::handle_checkout( $this->order );
			$this->send_checkout_event()
				->associate_past_orders()
				->mark_checkout_handled();
		}
	}

	/**
	 * Finds and stores the existing user of the person that did a checkout.
	 *
	 * If the user is logged in do not use the billing email, doing so results in the logged-in user being considered a
	 * guest if they enter a different email address than their WordPress email on WooCommerce Checkout page.
	 *
	 * @return $this
	 */
	private function find_existing_user() {
		if ( 'guest' === \Odise\get_user_type() ) {
			$order_email = $this->order->get_billing_email();
			$this->user  = get_user_by( 'email', $order_email );

			// WooCommerce won't make Aliens a user of current site if they do a guest checkout; so neither do we.
			if (
				false !== $this->user &&
				is_multisite() &&
				! is_user_member_of_blog( $this->user->ID, get_current_blog_id() )
			) {
				$this->user = false;
			}
		} else {
			$this->user = wp_get_current_user();
		}

		return $this;
	}

	/**
	 * Record session and client information for the order
	 *
	 * @return $this
	 */
	public function record_order_meta_data() {
		$meta_prefix = \Odise\meta_site_prefix();

		if ( $this->order->get_meta( "{$meta_prefix}_session_id" ) ) {
			return $this;
		}

		$this->order->add_meta_data( "{$meta_prefix}_session_id", $this->session->get_session_id(), true );
		$this->order->add_meta_data( "{$meta_prefix}_client_id", $this->session->get_client_id(), true );
		$this->order->save();

		return $this;
	}

	/**
	 * Resets the session ID after purchase. The session ID will not change in this request.
	 *
	 * @return $this
	 */
	private function reset_session_id() {
		// We just want to reset the exact session the order was made in; So we must not use `$session->get_session_id`
		// because that will reset the session each time the user visits the thankyou page.
		$meta_prefix = \Odise\meta_site_prefix();
		$session_id  = $this->order->get_meta( "{$meta_prefix}_session_id" );

		$this->session->reset_session_id( $session_id, 'checkout' );

		return $this;
	}

	/**
	 * Determine if this is a Guest Checkout.
	 *
	 * Use this function because our definition of 'guest' is different from a WordPress logged in user WRT users that
	 * are a member of another blog on multi-site, and an existing member that logs out and buys things with the same
	 * email.
	 *
	 * @return bool
	 */
	private function is_guest_checkout() {
		return ( false === $this->user );
	}

	/**
	 * Overrides the user type. This override will be picked up by FE.
	 *
	 * @return $this
	 */
	private function override_user_type() {
		// In this specific function do not use is_guest_checkout due to 593/594: When people* are logged out we'd like
		// to temporarily upgrade their current session anyway. (*People above includes own leads/customers and aliens.)
		if ( 'guest' !== \Odise\get_user_type() ) {
			return $this;
		}

		$this->session->override_user_props(
			'customer',
			$this->order->get_billing_email(),
			$this->order->get_billing_first_name(),
			$this->order->get_billing_last_name(),
			$this->order->get_date_created()->format( 'Y-m-d H:i:s' )
		);

		return $this;
	}

	/**
	 * Reports a checkout event to backend.
	 *
	 * @return $this
	 */
	private function send_checkout_event() {
		// Select only main order data.
		$order_data = array_intersect_key( $this->order->get_data(), array_flip( $this->order->get_data_keys() ) );

		// Plus the id.
		$order_data['id'] = $this->order->get_id();

		// Prepare and send the event.
		$event_name = $this->is_guest_checkout() ? 'wpGuestCheckout' : 'wpUserCheckout';
		$event_data = array(
			'order' => $order_data,
		);

		// If user is not logged in but her account already exists, it means she is now a customer and WooCommerce will
		// -or- already made her a member of the current blog in multi-site.  However Event_Transport still thinks of
		// her as a Guest, so we force the user type.
		$event_user_id   = $this->is_guest_checkout() ? null : $this->user->ID;
		$event_user_type = $this->is_guest_checkout() ? null : 'customer';

		$transport = new Event_Transport( $event_user_id, $event_user_type );
		$transport->send_event( $event_name, $event_data );

		return $this;
	}

	/**
	 * Link past orders to this customer if he has done any orders as a guest before
	 *
	 * Part of OD-507. This covers two scenarios:
	 * - User is logged out and they use the same email as their user account to make a purchase.
	 * - User is logged in but have placed orders using their email when they were logged out
	 *   (and we failed to associate those orders at that time).
	 *
	 * @see \Odise\Controller\User_Order_Fixup
	 *
	 * @return $this
	 */
	private function associate_past_orders() {
		if ( ! $this->is_guest_checkout() ) {
			wc_update_new_customer_past_orders( $this->user->ID );
		}

		return $this;
	}

	/**
	 * Checks if the checkout is handled.
	 *
	 * @return bool
	 */
	private function is_checkout_handled() {
		return (bool) $this->order->get_meta( self::CHECKOUT_HANDLED_META );
	}

	/**
	 * Sets metadata on the order specifying the checkout was handled.
	 *
	 * @return $this
	 */
	private function mark_checkout_handled() {
		$this->order->add_meta_data( self::CHECKOUT_HANDLED_META, '1', true );
		$this->order->save();

		return $this;
	}
}
