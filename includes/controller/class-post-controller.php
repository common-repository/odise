<?php

namespace Odise\Controller;

class Post_Controller extends Post_Type_Sync {

	public function __construct() {
		$this->post_type = 'post';
		$this->fields    = array( 'ID', 'post_content', 'post_title', 'post_date_gmt' );
	}

	protected function prepare( $posts ) {
		return array_map(
			function ( $post ) {
				$cats = get_the_terms( $post->ID, 'category' );
				$tags = get_the_terms( $post->ID, 'post_tag' );

				return array(
					'id'           => $post->ID,
					'content'      => $post->post_content,
					'url'          => \Odise\get_relative_permalink( $post->ID ),
					'title'        => $post->post_title,
					'images'       => array( wp_get_attachment_url( get_post_thumbnail_id( $post->ID ), 'thumbnail' ) ),
					'published_at' => $post->post_date_gmt,
					'categories'   => wp_list_pluck( (array) $cats, 'term_taxonomy_id' ),
					'tags'         => wp_list_pluck( (array) $tags, 'term_taxonomy_id' ),
					'modified_at'  => $this->get_post_type_modified_at( $post->ID ),
				);
			},
			$posts
		);
	}
}
