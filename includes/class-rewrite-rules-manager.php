<?php

namespace Odise;

use Odise\Controller\Redirect_Controller;
use Odise\Controller\Unsubscribe_Controller;

/**
 * Class Rewrite_Rules_Manager
 *
 * Ensures rewrite rules are activated automatically on plugin install, eliminating to do a manual Permalinks Save by
 * the user. Supports both single-site and multi-site scenarios.
 */
class Rewrite_Rules_Manager {
	public function __construct() {
		// Add rewrite rules on plugin activation.
		$main_file = dirname( plugin_dir_path( __FILE__ ), 1 ) . '/odise.php';
		register_activation_hook( $main_file, array( $this, 'plugin_activate' ) );

		// Add rewrite rules on network site creation.
		add_action( 'wp_insert_site', array( $this, 'add_rewrite_rules_for_site' ) );

		// Rewrite rules must always be present in memory, esp for manual Permalinks page saving.
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );

		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
	}

	public function plugin_activate( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			$sites = get_sites();
			foreach ( $sites as $site ) {
				$this->add_rewrite_rules_for_site( $site );
			}
		} else {
			$this->add_rewrite_rules();
			flush_rewrite_rules();
		}
	}

	public function add_rewrite_rules_for_site( $site ) {
		switch_to_blog( $site->blog_id );

		$this->add_rewrite_rules();
		flush_rewrite_rules();

		restore_current_blog();
	}

	public function add_rewrite_rules() {
		Redirect_Controller::add_rewrite_rules();
		Unsubscribe_Controller::add_rewrite_rules();
	}

	public function register_query_vars( $vars ) {
		$vars = Redirect_Controller::register_redirect_query_vars( $vars );
		$vars = Unsubscribe_Controller::register_unsubscribe_query_vars( $vars );
		return $vars;
	}
}
