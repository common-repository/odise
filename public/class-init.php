<?php

namespace Odise\Front;
use Odise;

class Init {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'add_odise_if_then' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_inline_scripts' ) );
	}

	public function add_odise_if_then() {
		wp_enqueue_script( 'odise-if-then' );
	}

	public function add_inline_scripts() {
		$site_id = get_option( 'odise_site_id' );

		global $wp;
		$user_type    = Odise\get_user_type();
		$current_user = ( $user_type === 'guest' ) ? null : wp_get_current_user();
		ob_start();
		?>

		window.siteId = '<?php echo $site_id; ?>';
		window._odise = {
			odiseApi: '<?php echo ODISE_API_URL; ?>',
			siteId:  '<?php echo $site_id; ?>',
			site: {
				id: '<?php echo $site_id; ?>',
				url:  '<?php echo rtrim(site_url(), '/'); ?>',
			},
			user: {
				type: '<?php echo $user_type; ?>',
				id: '<?php echo $current_user ? $current_user->ID : ''; ?>',
				username: '<?php echo $current_user ? $current_user->user_login : ''; ?>',
				firstName:  '<?php echo $current_user ? $current_user->first_name : ''; ?>',
				lastName:  '<?php echo $current_user ? $current_user->last_name : ''; ?>',
				registered:  '<?php echo $current_user ? $current_user->user_registered : ''; ?>',
				noOfOrders: <?php echo ( $current_user ? ( new Odise\Controller\User_Order_Controller() )->get_user_order_count( $current_user->ID ) : 0 ); ?>,
			},
			page: {
				wpUrl: '<?php echo '/' . $wp->request; ?>',
				postType: '<?php echo get_post_type(); ?>',
				postId: '<?php echo get_the_ID(); ?>',
				isSingle: <?php echo (is_single() || is_page()) ? 'true' : 'false'; ?>,
			},
			env: '<?php echo ODISE_APP_ENV; ?>',
			currentTime: '<?php echo date("Y-m-d H:i:s") ?>',
		};

		<?php
		$footer_scripts = ob_get_clean();

		wp_add_inline_script( 'odise-if-then', $footer_scripts, 'before' );

		$init_odiseift = 'if(typeof odiseift!=="undefined")odiseift.default(window._odise.siteId);else console.error("Odise Error: Undefined odiseift.");';
		wp_add_inline_script( 'odise-if-then', $init_odiseift, 'after' );

	}

}
