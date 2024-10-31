<?php

add_action( 'rest_api_init', 'odise_register_api_routes' );

function odise_register_api_routes() {
	$token_verifier_callback = array( new \Odise\Webhook\Verify_Token_Webhook(), 'verify_token' );
	$odise_base_route        = 'odise/v1';

	register_rest_route(
		$odise_base_route,
		'sync/users',
		array(
			'methods'             => 'GET',
			'callback'            => array( new Odise\Controller\User_Controller(), 'index' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'sync/pages',
		array(
			'methods'             => 'GET',
			'callback'            => array( new Odise\Controller\Page_Controller(), 'index' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'sync/posts',
		array(
			'methods'             => 'GET',
			'callback'            => array( new Odise\Controller\Post_Controller(), 'index' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'sync/products',
		array(
			'methods'             => 'GET',
			'callback'            => array( new Odise\Controller\Product_Controller(), 'index' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'sync/orders',
		array(
			'methods'             => 'GET',
			'callback'            => array( new Odise\Controller\Orders_Controller(), 'index' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'sync/status',
		array(
			'methods'             => 'POST',
			'callback'            => array( new Odise\Controller\Status_Controller(), 'status' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'coupons',
		array(
			'methods'             => 'POST',
			'callback'            => array( new Odise\Controller\Coupon_Controller(), 'retrieve' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'coupons/create',
		array(
			'methods'             => 'POST',
			'callback'            => array( new Odise\Controller\Coupon_Controller(), 'create' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'coupons/update',
		array(
			'methods'             => 'POST',
			'callback'            => array( new Odise\Controller\Coupon_Controller(), 'update' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'coupons/delete',
		array(
			'methods'             => 'POST',
			'callback'            => array( new Odise\Controller\Coupon_Controller(), 'delete' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'users',
		array(
			'methods'             => 'POST',
			'callback'            => array( new Odise\Controller\User_Controller(), 'create' ),
			'permission_callback' => $token_verifier_callback,
		)
	);

	register_rest_route(
		$odise_base_route,
		'integrate',
		array(
			'methods'  => 'GET',
			'callback' => array( new Odise\Integration_Init(), 'get_status' ),
		)
	);
}
