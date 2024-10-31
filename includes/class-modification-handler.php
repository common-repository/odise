<?php

namespace Odise;

class Modification_Handler {

	const USER_META_FIELDS = array(
		// Odise fields
		'phone',
		'address',
		'page_furl',
		'page_title',
		'session_id',
		'client_id',
		'popup_id',
		'rule_id',

		// From WooCommerce, to detect Guest Checkout by a Guest Subscriber (i.e automatic order association)
		'_order_count',
	);

	const POST_TYPES = array(
		'post',
		'page',
		'product',
		'shop_order',
	);

	public function __construct() {
		add_action( 'user_register', array( $this, 'set_user_last_modification_time' ) ); // User registration.
		add_action( 'profile_update', array( $this, 'set_user_last_modification_time' ) ); // General db updates in user info.
		add_action( 'personal_options_update', array( $this, 'set_user_last_modification_time' ) );  // User updates profile.
		add_action( 'edit_user_profile_update', array( $this, 'set_user_last_modification_time' ) ); // User updates another ussr's profile.

		$this->add_actions( self::POST_TYPES );
		$this->add_meta_actions( 'user' );

		// For WooCommerce rating and review meta fields on product
		add_action( 'wp_insert_comment', array( $this, 'handle_insert_comment' ), 20, 2 );

		// Associating orders with existing users (e.g on `wc_update_new_customer_past_orders` call).
		add_action( 'updated_post_meta', array( $this, 'set_post_meta_last_modification_time' ), 20, 4 );
	}

	public function add_actions( $post_types ) {
		foreach ( $post_types as $post_type ) {
			add_action( "save_post_{$post_type}", array( $this, 'set_last_modification_time' ), 10, 3 );
		}
	}

	public function add_meta_actions( $meta_type ) {
		add_action( "added_{$meta_type}_meta", array( $this, "set_{$meta_type}_meta_last_modification_time" ), 20, 4 );
		add_action( "updated_{$meta_type}_meta", array( $this, "set_{$meta_type}_meta_last_modification_time" ), 20, 4 );
		add_action( "deleted_{$meta_type}_meta", array( $this, "set_{$meta_type}_meta_last_modification_time" ), 20, 4 );
	}

	public function set_last_modification_time( $post_id, $post, $update ) {
		update_post_meta( $post_id, "_odise_{$post->post_type}_last_modification", time() );  // Timestamp, GMT.
	}

	public static function set_post_last_modification_time( $post_id, $post_type ) {
		update_post_meta( $post_id, "_odise_{$post_type}_last_modification", time() );  // Timestamp, GMT.
	}

	public function set_post_meta_last_modification_time( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( '_customer_user' === $meta_key && 'shop_order' === get_post_type( $post_id ) ) {
			self::set_post_last_modification_time( $post_id, 'shop_order' );
		}
	}

	public function set_user_last_modification_time( $user_id ) {
		update_user_meta( $user_id, '_odise_user_last_modification', time() );  // Timestamp, GMT.
	}

	public function set_user_meta_last_modification_time( $meta_id, $user_id, $meta_key, $meta_value ) {
		if ( $this->is_user_meta_interesting( $meta_key ) ) {
			$this->set_user_last_modification_time( $user_id );
		}
	}

	private function is_user_meta_interesting( $meta_key ) {
		// It is important that we don't return true for '_odise_user_last_modification'.
		return in_array( $meta_key, self::USER_META_FIELDS, true )
			|| starts_with( $meta_key, '_woocommerce_persistent_cart' );
	}

	public function handle_insert_comment( $id, $comment ) {
		// Updates the following meta on the product postmeta. We handle them here instead of on 'updated_post_meta'.
		// - _wc_average_rating
		// - _wc_rating_count
		// - _wc_review_count

		if ( 'review' === $comment->comment_type && 'product' === get_post_type( $comment->comment_post_ID ) ) {
			self::set_post_last_modification_time( $comment->comment_post_ID, 'product' );
		}
	}

	public static function handle_checkout( $order ) {
		// Updates the 'total_sales' meta on the product postmeta. The mentioned meta changes cannot be detected with
		// the normal 'updated_post_meta' as WooCommerce changes database directly for updating it.

		foreach ( $order->get_items() as $item ) {
			self::set_post_last_modification_time( $item->get_product_id(), 'product' );
		}

		// Sometimes there is a delay between checkout and recording the session_ids in WooCommerce_Checkout_Controller;
		// either a few seconds or if things crash and the user has to refresh the thankyou page to see it. If sync runs
		// during this time the backend will get no session_ids. We try to prevent these edge cases by setting the order
		// modification time one more time.

		self::set_post_last_modification_time( $order->get_id(), 'shop_order' );
	}
}
