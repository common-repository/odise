<?php

namespace Odise\Controller;

use function Odise\starts_with;

class User_Controller {

	const META_FIELDS = array(
		'phone',
		'address',
		'page_furl',
		'page_title',
		'session_id',
		'client_id',
		'popup_id',
		'rule_id',
	);

	public function index( $request ) {
		if ( $request->get_header( 'X-Site-ID' ) != get_option( 'odise_site_id' ) ) { // phpcs:ignore
			return new \WP_Error( 'site_id_not_valid', esc_html__( 'Invalid Site ID', 'odise' ), array( 'status' => 403 ) );
		}

		$params = $request->get_params();

		if ( isset( $params['type'] ) ) {

			switch ( $params['type'] ) {
				case 'new':
					return $this->get_new_users( $params );
				case 'modified':
					return $this->get_modified_users( $params );
				default:
					break;
			}
		}

		if ( isset( $params['ids'] ) ) {
			return $this->get_users_by_id( $params['ids'] );
		}
	}

	public function get_new_users( $args ) {

		$last_id = isset( $args['last_id'] ) ? $args['last_id'] : 0;

		$users = $this->get_users( $last_id );
		$users = $this->attach_metadata( $users );

		return $users;
	}

	public function get_modified_users( $args ) {

		global $wpdb;

		$first_synced_id = isset( $args['first_synced_id'] ) ? $args['first_synced_id'] : 0;
		$last_id         = isset( $args['last_id'] ) ? $args['last_id'] : 0;
		$limit           = 1000;
		// Multi-site queries work on single-site too, but we try to run lighter queries on single-site.
		if ( is_multisite() ) {
			$blog_prefix = $wpdb->get_blog_prefix( get_current_blog_id() );

			$query =
				"SELECT m1.user_id AS id, m1.meta_value AS ts
				FROM {$wpdb->usermeta} m1, {$wpdb->usermeta} m2
				WHERE m1.user_id = m2.user_id
				AND m1.meta_key = '_odise_user_last_modification'
				AND m2.meta_key = '{$blog_prefix}capabilities'
				AND m1.user_id BETWEEN %d AND %d
				ORDER BY id
				LIMIT %d";
		} else {
			$query =
				"SELECT user_id AS id, meta_value AS ts
				FROM $wpdb->usermeta
				WHERE meta_key = '_odise_user_last_modification' AND user_id BETWEEN %d AND %d
				ORDER BY id
				LIMIT %d";
		}

		$sql   = $wpdb->prepare( $query, $last_id, $first_synced_id, $limit ); // phpcs:ignore
		$users = $wpdb->get_results( $sql ); // phpcs:ignore

		return $users;
	}

	public function get_users_by_id( $ids ) {

		$ids      = explode( ',', $ids );
		$user_ids = array_slice( $ids, 0, 100, true );

		$fields = array(
			'id' => 'id',
			'display_name',
			'user_email',
			'user_registered',
			'user_status',
			'user_login',
		);

		$args = array(
			'include' => $user_ids,
			'fields'  => $fields,
		);

		$users = get_users( $args );
		$users = $this->attach_metadata( $users );

		return $users;
	}

	protected function get_users( $last_id = 0, $limit = 100 ) {

		global $wpdb;

		// Multi-site queries work on single-site too, but we try to run lighter queries on single-site.
		if ( is_multisite() ) {
			$blog_prefix = $wpdb->get_blog_prefix( get_current_blog_id() );

			$query =
				"SELECT user_id, user_id AS id, display_name, user_email, user_registered, user_status, user_login
				FROM {$wpdb->users}, {$wpdb->usermeta}
				WHERE {$wpdb->users}.id = {$wpdb->usermeta}.user_id
				AND meta_key = '{$blog_prefix}capabilities'
				AND id > %d
				ORDER BY {$wpdb->usermeta}.user_id
				LIMIT %d";
		} else {
			$query = "
				SELECT id, display_name, user_email, user_registered, user_status, user_login
				FROM $wpdb->users
				WHERE id > %d
				ORDER BY id
				LIMIT %d";
		}

		$sql = $wpdb->prepare( $query, $last_id, $limit ); // phpcs:ignore

		return $wpdb->get_results( $sql ); // phpcs:ignore
	}

	protected function attach_metadata( $users ) {
		if ( ! is_array( $users ) ) {
			return $users;
		}

		$orders = new User_Order_Controller();

		return array_map(
			function ( $user ) use ( $orders ) {
				$user->first_name  = get_user_meta( $user->id, 'first_name', true );
				$user->last_name   = get_user_meta( $user->id, 'last_name', true );
				$user->is_customer = $orders->user_has_ordered_by_user( $user->id );
				$user->order_count = $orders->get_user_order_count( $user->id );
				$user->has_cart    = $this->has_persistent_cart( $user );
				$user->modified_at = $this->get_user_modified_at( $user->id );
				$this->attach_odise_metadata( $user );
				return $user;
			},
			$users
		);
	}

	protected function get_user_modified_at( $user_id ) {
		$modification = get_user_meta( $user_id, '_odise_user_last_modification', true );

		if ( empty( $modification ) ) {
			$modification = time();
			update_user_meta( $user_id, '_odise_user_last_modification', $modification );
		}

		return $modification;
	}

	protected function has_persistent_cart( $user ) {
		$wc_meta_key = '_woocommerce_persistent_cart';
		$user_meta   = get_user_meta( $user->id );

		foreach ( $user_meta as $meta_key => $meta_value ) {
			// Check all meta keys starting with '_woocommerce_persistent_cart'.
			if ( starts_with( $meta_key, $wc_meta_key ) ) {
				// Found a persistent cart; check if empty.
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
				$cart = unserialize( $meta_value[0] );

				// If a cart exists, $cart['cart'] will be set and contain an item otherwise will be empty.
				// If found a cart, return it; otherwise continue checking the rest of the metas.
				if ( ! empty( $cart['cart'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	protected function attach_odise_metadata( $user ) {
		$meta_prefix = \Odise\meta_site_prefix();

		foreach ( self::META_FIELDS as $field_name ) {
			$field_value = get_user_meta( $user->id, "{$meta_prefix}_{$field_name}", true );
			if ( $field_value ) {
				$user->$field_name = $field_value;
			}
		}
	}

	public function create( $request ) {

		if ( $request->get_header( 'X-Site-ID' ) != get_option( 'odise_site_id' ) ) { // phpcs:ignore
			return new \WP_Error( 'site_id_not_valid', esc_html__( 'Invalid Site ID', 'odise' ), array( 'status' => 403 ) );
		}

		$parameters = $request->get_params();

		$userdata = array(
			'user_login' => $parameters['email'],
			'user_email' => $parameters['email'],
			'user_meta'  => array(
				'first_name' => isset( $parameters['first_name'] ) ? $parameters['first_name'] : '',
				'last_name'  => isset( $parameters['last_name'] ) ? $parameters['last_name'] : '',
			),
		);

		$existing_user_id = email_exists( $userdata['user_email'] );

		// If user exists and we are on multisite, we may need to add existing user to a new blog.
		if ( is_multisite() && $existing_user_id ) {
			$blog_id = get_current_blog_id();
			// Checking for being member of blog to prevent overriding user role.
			if ( $existing_user_id && ! is_user_member_of_blog( $existing_user_id, $blog_id ) ) {
				$results = add_user_to_blog( $blog_id, $existing_user_id, 'subscriber' );

				if ( is_wp_error( $results ) ) {
					return array(
						'success' => false,
						'code'    => $results->get_error_code(),
						'message' => $results->get_error_message(), // It includes HTML tags.
					);
				}
			}

			return array(
				'success' => true,
				'message' => __( 'Registration successful.' ),
				'data'    => array( 'user_id' => $existing_user_id ),
			);
		}

		// If we are not on multisite or user is not exists, try to register a new user.
		$user_id = register_new_user( $userdata['user_login'], $userdata['user_email'] );

		if ( is_wp_error( $user_id ) ) {
			return array(
				'success' => false,
				'code'    => $user_id->get_error_code(),
				'message' => $user_id->get_error_message(), // It includes HTML tags.
			);
		}

		update_user_meta( $user_id, 'first_name', $userdata['user_meta']['first_name'] );
		update_user_meta( $user_id, 'last_name', $userdata['user_meta']['last_name'] );
		$this->add_metadata( $user_id, $parameters );

		return array(
			'success' => true,
			'message' => __( 'Registration successful.', 'odise' ),
			'data'    => array( 'user_id' => $user_id ),
		);
	}

	protected function add_metadata( $user_id, $metadata ) {
		$meta_prefix = \Odise\meta_site_prefix();

		foreach ( self::META_FIELDS as $field_name ) {
			if ( ! empty( $metadata[ $field_name ] ) ) {
				// 3rd argument is set to true. Means no duplicate is allowed for a meta key per user.
				add_user_meta( $user_id, "{$meta_prefix}_{$field_name}", $metadata[ $field_name ], true );
			}
		}
	}
}
