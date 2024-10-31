<?php

// Load text domain.
add_action( 'init', 'odise_i18n' );

function odise_i18n() {
	load_plugin_textdomain( 'odise', false, ODISE_PATH . 'languages' );
}

// Fix hidden links in emails.
add_filter( 'wp_new_user_notification_email', 'odise_modify_new_user_notification_email', 99, 3 );

function odise_modify_new_user_notification_email( $email, $user, $blogname ) {
	$email['message'] = preg_replace( '/<(' . preg_quote( network_site_url(), '/' ) . '[^>]*)>/', '\1', $email['message'] );
	return $email;
}

// Add custom data attribute to if-then script.
add_filter( 'script_loader_tag', 'odise_script_custom_attrs', 10, 3 );

function odise_script_custom_attrs( $tag, $handle, $source ) {
	if ( 'odise-if-then' === $handle && false !== strpos( $source, ODISE_RULE_CLIENT_URL ) ) {
		$tag = str_replace( "<script src='" . $source . "'></script>", "<script src='" . $source . "' data-cfasync='false' data-no-optimize='1' data-minify='0'></script>", $tag ); //phpcs:ignore
	}
	return $tag;
}

// Exclude odise scripts from Autoptimize.
add_filter( 'autoptimize_filter_js_exclude', 'odise_compatibility_autoptimize' );

function odise_compatibility_autoptimize( $excluded_js_files ) {
	$odise_files = 'odise-public.js, if-then.min.js, _odise';
	return $excluded_js_files . ', ' . $odise_files;
}

// Exclude odise scripts from LiteSpeed Cache.
add_filter( 'litespeed_cache_optimize_js_excludes', 'odise_compatibility_litespeed_cache' );

function odise_compatibility_litespeed_cache( $files ) {
	return $files . "\nodise-public.js\nif-then.min.js\n";
}

// Exclude odise inline scripts from WP Rocket.
add_filter( 'rocket_excluded_inline_js_content', 'odise_compatibility_wp_rocket_inline_scripts' );

function odise_compatibility_wp_rocket_inline_scripts( array $excluded_files ) {
	$excluded_files[] = '_odise';
	return $excluded_files;
}

// Exclude odise external scripts from WP Rocket.
add_filter( 'rocket_minify_excluded_external_js', 'odise_compatibility_wp_rocket' );

function odise_compatibility_wp_rocket( array $excluded_files ) {
	$excluded_files[] = 'odise.io';
	return $excluded_files;
}
