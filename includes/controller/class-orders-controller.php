<?php

namespace Odise\Controller;

use WC_Order;

/**
 * Orders_Controller
 */
class Orders_Controller extends Post_Type_Sync {

	public function __construct() {
		$this->post_type = 'shop_order';
		$this->fields    = array( 'ID', 'post_date_gmt' );

		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$this->post_status = array_keys( wc_get_order_statuses() );
		}
	}

	protected function prepare( $orders ) {
		$meta_prefix = \Odise\meta_site_prefix();

		return array_map(
			function ( $post ) use ( $meta_prefix ) {
				$order = new WC_Order( $post->ID );

				$products = array_map(
					function( $item ) {
						return array(
							'id'  => $item['product_id'],
							'qty' => $item['qty'],
						);
					},
					$order->get_items()
				);

				return array(
					'id'           => $post->ID,
					'user_id'      => $order->get_user_id(),
					'products'     => array_values( $products ),
					'published_at' => $post->post_date_gmt,
					'session_id'   => $order->get_meta( "{$meta_prefix}_session_id" ),
					'client_id'    => $order->get_meta( "{$meta_prefix}_client_id" ),
					'coupon_ids'   => $order->get_coupon_codes(),
					'order_total'  => $order->get_total(),
					'modified_at'  => $this->get_post_type_modified_at( $post->ID ),
				);
			},
			$orders
		);
	}
}
