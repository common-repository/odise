<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<h3><?php echo esc_html_e( 'Integration info', 'odise' ); ?></h3>
	<p>
		<strong><?php esc_html_e( 'Site URL:', 'odise' ); ?></strong>
		<?php echo esc_html( get_site_url() ); ?>
	</p>
	<p>
		<strong><?php esc_html_e( 'Site ID:', 'odise' ); ?></strong>
		<?php echo esc_html( get_option( 'odise_site_id', '' ) ); ?>
	</p>
	<p>
		<strong><a href="https://odise.io/terms-of-use/"><?php esc_html_e( 'Terms and conditions', 'odise' ); ?></a></strong>
	</p>
	<p>
	<button class="button button-secondary odise-remove-integration"><?php esc_html_e( 'Remove integration', 'odise' ); ?></button>
	</p>
</div>
