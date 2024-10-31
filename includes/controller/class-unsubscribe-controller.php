<?php

namespace Odise\Controller;

class Unsubscribe_Controller {
	private $source = 'link';

	public function __construct() {
		add_action( 'parse_request', array( $this, 'set_up_unsubscribe_page' ) );
	}

	public static function add_rewrite_rules() {
		add_rewrite_rule(
			'^od/unsubscribe/([^/]*)/?$',
			'index.php?pagename=odunsubscribe&odu_source=$matches[1]',
			'top'
		);
	}

	public static function register_unsubscribe_query_vars( $vars ) {
		array_push(
			$vars,
			'odu_source'
		);
		return $vars;
	}

	public function set_up_unsubscribe_page( $wp ) {
		if ( empty( $wp->query_vars['pagename'] ) ) {
			return;
		}

		if ( 'odunsubscribe' !== $wp->query_vars['pagename'] ) {
			return;
		}

		if ( ! empty( $wp->query_vars['odu_source'] ) ) {
			$this->source = $wp->query_vars['odu_source'];
		}

		add_filter( 'the_posts', array( &$this, 'odise_unsubscribe_post' ) );

		add_filter( 'page_link', array( &$this, 'odise_unsubscribe_pagelink' ), 1, 2 );
	}

	public function odise_unsubscribe_post( $posts ) {
		global $wp_query;

		ob_start();
		require dirname( plugin_dir_path( __FILE__ ), 2 ) . '/public/views/odunsubscribe.php';
		$content = ob_get_clean();

		// Create a virtual post
		$post = (object) array(
			'ID'               => -1,
			'post_author'      => 1,
			'post_date'        => current_time( 'mysql' ),
			'post_date_gmt'    => current_time( 'mysql', 1 ),
			'post_title'       => __( 'Unsubscribe Successful', 'odise' ),
			'post_content'     => $content,
			'post_excerpt'     => __( 'Unsubscribe Successful', 'odise' ),
			'comment_status'   => 'closed',
			'ping_status'      => 'closed',
			'post_parent'      => 0,
			'menu_item_parent' => 0,
			'post_password'    => '',
			'post_name'        => 'unsubscribe',
			'to_ping'          => '',
			'pinged'           => '',
			'modified'         => current_time( 'mysql' ),
			'modified_gmt'     => current_time( 'mysql', 1 ),
			'guid'             => get_bloginfo( 'wpurl' ) . "/od/unsubscribe/{$this->source}/",
			'url'              => get_bloginfo( 'wpurl' ) . "/od/unsubscribe/{$this->source}/",
			'menu_order'       => 0,
			'post_type'        => 'page',
			'post_status'      => 'publish',
			'post_mime_type'   => '',
			'comment_count'    => 0,
			'description'      => '',
			'filter'           => 'raw',
			'ancestors'        => array(),
		);

		if ( is_admin() ) {
			$post = new \WP_Post( $post );
		}
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['post'] = $post;
		$posts           = array( $post );

		// Fake WP_Query fields
		$wp_query->is_page     = true;
		$wp_query->is_singular = true;
		$wp_query->is_home     = false;
		$wp_query->is_archive  = false;
		$wp_query->is_category = false;

		unset( $wp_query->query['error'] );
		$wp_query->query_vars['error'] = '';
		$wp_query->is_404              = false;

		$wp_query->current_post    = $post->ID;
		$wp_query->found_posts     = 1;
		$wp_query->post_count      = 1;
		$wp_query->max_num_pages   = 1;
		$wp_query->comment_count   = 0;
		$wp_query->current_comment = null;
		$wp_query->is_singular     = 1;

		$wp_query->post              = $post;
		$wp_query->posts             = array( $post );
		$wp_query->queried_object    = $post;
		$wp_query->queried_object_id = $post->ID;

		return $posts;
	}

	public function odise_unsubscribe_pagelink( $link, $post_id ) {
		if ( -1 === $post_id ) {
			return get_bloginfo( 'wpurl' ) . "/od/unsubscribe/{$this->source}/";
		}

		return $link;
	}
}
