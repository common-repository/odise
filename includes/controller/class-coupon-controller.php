<?php
/**
 * Contains the Coupon_Controller class.
 *
 * @package Odise\Controller
 */

namespace Odise\Controller;

use Odise\Coupon_Handler;

/**
 * Class Coupon_Controller
 *
 * Manages WooCommerce Coupon CRUD actions.
 *
 * @package Odise\Controller
 */
class Coupon_Controller {
	/**
	 * Return errors if the request is not valid, i.e site not integrated or WooCommerce not active.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|null
	 */
	private function check_request_error( $request ) {
		if ( $request->get_header( 'X-Site-ID' ) != get_option( 'odise_site_id' ) ) { // phpcs:ignore
			return new \WP_Error( 'site_id_not_valid', esc_html__( 'Invalid Site ID', 'odise' ), array( 'status' => 403 ) );
		}

		if ( ! \Odise\is_woocommerce_active() ) {
			return new \WP_Error( 'woocommerce_not_active', esc_html__( 'WooCommerce is not active', 'odise' ), array( 'status' => 404 ) );
		}

		return null;
	}

	/**
	 * Makes a response based on a coupon post.
	 *
	 * @param $coupon
	 * @param $status
	 * @return \WP_REST_Response
	 */
	private function coupon_response( $coupon, $status ) {
		$entity_prefix = \Odise\meta_site_prefix() . '_entity';

		$coupon_data = array(
			'id'            => $coupon->ID,
			'code'          => $coupon->post_title,
			'discount_type' => get_post_meta( $coupon->ID, 'discount_type', true ),
			'coupon_amount' => get_post_meta( $coupon->ID, 'coupon_amount', true ),
			'product_ids'   => get_post_meta( $coupon->ID, 'product_ids', true ),
			'entity_type'   => get_post_meta( $coupon->ID, "{$entity_prefix}_type", true ),
			'entity_id'     => get_post_meta( $coupon->ID, "{$entity_prefix}_id", true ),
		);

		$data = array( 'coupon' => $coupon_data );

		$response = new \WP_REST_Response( $data );

		$response->set_status( $status );

		return $response;
	}

	/**
	 * Retrieves a coupon post from the request after validating it.
	 *
	 * @param $request
	 * @return \WP_Error|\WP_Post|null
	 */
	private function retrieve_coupon( $request ) {
		$request_error = $this->check_request_error( $request );

		if ( $request_error ) {
			return $request_error;
		}

		$coupon = get_post( $request->get_param( 'id' ) );

		if ( ! $coupon || 'shop_coupon' !== $coupon->post_type || 'publish' !== $coupon->post_status ) {
			return new \WP_Error( 'invalid', esc_html__( 'Invalid coupon', 'odise' ), array( 'status' => 404 ) );
		}

		return $coupon;
	}

	/**
	 * REST API route.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response|null
	 */
	public function retrieve( $request ) {
		$coupon = $this->retrieve_coupon( $request );
		if ( ! ( $coupon instanceof \WP_Post ) ) {
			return $coupon;
		}

		return $this->coupon_response( $coupon, 200 );
	}

	/**
	 * REST API route.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response|null
	 */
	public function create( $request ) {
		$request_error = $this->check_request_error( $request );

		if ( $request_error ) {
			return $request_error;
		}

		$coupon = $this->add_coupon( $request );

		return $this->coupon_response( $coupon, 201 );
	}

	/**
	 * Creates a coupon post.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Post
	 */
	protected function add_coupon( $request ) {
		$coupon_code   = strtoupper( substr( md5( wp_rand() ), 0, 12 ) );
		$discount_type = $this->valid_discount_type( $request->get_param( 'discount_type' ) );
		$coupon_amount = $request->get_param( 'coupon_amount' );
		$product_ids   = $request->get_param( 'product_ids' );
		$user_limit    = 1;

		$odise_prefix  = \Odise\meta_site_prefix();
		$entity_prefix = "{$odise_prefix}_entity";
		$entity_type   = $request->get_param( 'entity_type' );
		$entity_id     = $request->get_param( 'entity_id' );

		$coupon = array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1000000000000,
			'post_type'    => 'shop_coupon',
		);

		$new_coupon_id = wp_insert_post( $coupon );

		// Up-to-date as of WooCommerce 3.8.
		$this->update_coupon_meta(
			$new_coupon_id,
			array(
				'discount_type'          => $discount_type,
				'coupon_amount'          => $coupon_amount,
				'free_shipping'          => 'no',
				'date_expires'           => '',

				'individual_use'         => 'no',
				'exclude_sale_items'     => 'no',

				'product_ids'            => $product_ids,
				'exclude_product_ids'    => '',

				'usage_limit'            => '',
				'limit_usage_to_x_items' => '',
				'usage_limit_per_user'   => $user_limit,

				"{$odise_prefix}_coupon" => 1,
				"{$entity_prefix}_type"  => $entity_type,
				"{$entity_prefix}_id"    => $entity_id,
			)
		);

		return get_post( $new_coupon_id );
	}

	/**
	 * Returns a fallback default for discount type if the supplied value is wrong.
	 *
	 * @param string $discount_type
	 * @return string
	 */
	private function valid_discount_type( $discount_type ) {
		$valid_discount_types = array( 'percent', 'fixed_cart', 'fixed_product' );

		if ( ! in_array( $discount_type, $valid_discount_types, true ) ) {
			$discount_type = 'percent';
		}

		return $discount_type;
	}

	/**
	 * Update meta values for a coupon post.
	 *
	 * @param $coupon_id
	 * @param array $meta
	 */
	private function update_coupon_meta( $coupon_id, $meta ) {
		foreach ( $meta as $key => $value ) {
			update_post_meta( $coupon_id, $key, $value );
		}
	}

	/**
	 * REST API route.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response|null
	 */
	public function update( $request ) {
		$coupon = $this->retrieve_coupon( $request );
		if ( ! ( $coupon instanceof \WP_Post ) ) {
			return $coupon;
		}

		$coupon = $this->update_coupon( $request, $coupon );

		return $this->coupon_response( $coupon, 200 );
	}

	/**
	 * Updates a coupon post.
	 *
	 * @param \WP_REST_Request $request
	 * @param $coupon
	 * @return \WP_Post
	 */
	protected function update_coupon( $request, $coupon ) {
		$discount_type = $this->valid_discount_type( $request->get_param( 'discount_type' ) );
		$coupon_amount = $request->get_param( 'coupon_amount' );
		$product_ids   = $request->get_param( 'product_ids' );

		$this->update_coupon_meta(
			$coupon->ID,
			array(
				'discount_type' => $discount_type,
				'coupon_amount' => $coupon_amount,
				'product_ids'   => $product_ids,
			)
		);

		return $coupon;
	}

	/**
	 * REST API route.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response|null
	 */
	public function delete( $request ) {
		$coupon = $this->retrieve_coupon( $request );
		if ( ! ( $coupon instanceof \WP_Post ) ) {
			return $coupon;
		}

		$this->delete_coupon( $coupon );

		return $this->coupon_response( $coupon, 200 );
	}

	/**
	 * Trashes or deletes a coupon post.
	 *
	 * @param $coupon
	 */
	protected function delete_coupon( $coupon ) {
		wp_delete_post( $coupon->ID );
	}
}
