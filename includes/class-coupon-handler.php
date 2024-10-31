<?php
/**
 * Contains the Coupon_Handler class.
 */

namespace Odise;

/**
 * Class Coupon_Handler
 *
 * Handles coupon modifications and notifies backend. Also manages some UI tweaks.
 *
 * @package Odise
 */
class Coupon_Handler {
	/**
	 * Coupon_Handler constructor.
	 */
	public function __construct() {
		add_action( 'edit_post_shop_coupon', array( $this, 'inform_coupon_update' ), 10, 2 );
		add_action( 'before_delete_post', array( $this, 'inform_coupon_delete' ), 10, 1 );

		add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'custom_coupon_column' ), 5, 2 );
	}

	/**
	 * Hook. Notifies backend of the change.
	 *
	 * @param $post_id
	 * @param $post
	 */
	public function inform_coupon_update( $post_id, $post ) {
		$prefix = meta_site_prefix();

		$is_odise_coupon = (bool) get_post_meta( $post_id, "{$prefix}_coupon", true );
		if ( ! $is_odise_coupon ) {
			return;
		}

		try {
			$url      = ODISE_API_URL . '/v1/webhook/coupon-updated';
			$site_id  = get_option( 'odise_site_id' );
			$e_prefix = "{$prefix}_entity";
			$data     = array(
				'site_id'     => $site_id,
				'coupon_id'   => $post_id,
				'entity_type' => get_post_meta( $post_id, "{$e_prefix}_type", true ),
				'entity_id'   => get_post_meta( $post_id, "{$e_prefix}_id", true ),
			);

			wp_remote_post(
				$url,
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking'    => true,
					'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
					'body'        => wp_json_encode( $data ),
					'cookies'     => array(),
				)
			);
		} catch ( \Throwable $ex ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Try not to crash
		}
	}

	/**
	 * Hook. Notifies backend of the change.
	 *
	 * @param $post_id
	 */
	public function inform_coupon_delete( $post_id ) {
		$post = get_post( $post_id );

		// Either not a coupon, or not published; in both cases we already don't have the coupon in the backend.
		if ( 'shop_coupon' !== $post->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		$this->inform_coupon_update( $post_id, $post );
	}

	/**
	 * Hook. Adds Odise icon beside Odise coupons in coupons list admin page.
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function custom_coupon_column( $column, $post_id ) {
		$prefix = meta_site_prefix();

		$is_odise_coupon = (bool) get_post_meta( $post_id, "{$prefix}_coupon", true );
		if ( ! $is_odise_coupon ) {
			return;
		}

		if ( 'coupon_code' === $column ) {
			echo '<img src="' . esc_url( ODISE_ADMIN_URL . 'assets/img/favicon.png' ) . '"' .
				'style="width: 1.2em; height: 1.2em; margin: 0; vertical-align: top;" ' .
				'alt="Managed by Odise.io" title="Managed by Odise.io">&nbsp;';
		}
	}
}
