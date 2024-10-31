<?php

namespace Odise\Controller;

class Product_Controller extends Post_Type_Sync {

	public function __construct() {
		$this->post_type = 'product';
		$this->fields    = array( 'ID', 'post_content', 'post_excerpt', 'post_title', 'post_date_gmt' );
	}

	protected function prepare( $products ) {
		return array_map(
			function ( $post ) {
				$cats = get_the_terms( $post->ID, 'product_cat' );
				$tags = get_the_terms( $post->ID, 'product_tag' );

				$product = wc_get_product( $post->ID );
				return array(
					'id'             => $post->ID,
					'content'        => $post->post_content,
					'excerpt'        => $post->post_excerpt,
					'url'            => \Odise\get_relative_permalink( $post->ID ),
					'title'          => $post->post_title,
					'images'         => array( wp_get_attachment_url( get_post_thumbnail_id( $post->ID ), 'thumbnail' ) ),
					'price'          => (float) $product->get_price(),
					'sale_price'     => (float) $product->get_sale_price(),
					'regular_price'  => (float) $product->get_regular_price(),
					'sku'            => $product->get_sku(),
					'total_sales'    => $product->get_total_sales(),

					'review_count'   => $product->get_review_count(),
					'average_rating' => (float) $product->get_average_rating(),
					'is_featured'    => $product->get_featured(),

					'published_at'   => $post->post_date_gmt,
					'categories'     => wp_list_pluck( (array) $cats, 'term_taxonomy_id' ),
					'tags'           => wp_list_pluck( (array) $tags, 'term_taxonomy_id' ),
					'modified_at'    => $this->get_post_type_modified_at( $post->ID ),
				);
			},
			$products
		);
	}
}
