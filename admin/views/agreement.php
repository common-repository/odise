<div class="wrap">
	<h2 class="odise-center"><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<main class="odise-welcome">
		<h3 class="odise-center"><?php esc_html_e( '..:: Terms and condition ::..', 'odise' ); ?></h3>
		<p>
			<?php
			printf(
				'%s <a href="https://odise.io" target="_blank">%s</a> %s',
				esc_html__( 'The following account from', 'odise' ),
				esc_html( 'odise.io', 'odise' ),
				esc_html( 'Wants to connect to this site.', 'odise' )
			);
			?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Email:', 'odise' ); ?></strong>
			<?php
			$integration_info = get_transient( 'odise_integration_info', array() );
			echo isset( $integration_info['email'] ) ? esc_html( $integration_info['email'] ) : '';
			?>
		</p>
		<p>
			<?php
			printf(
				'%s <a href="https://odise.io/privacy-policy/" target="_blank">%s</a> %s <a href="https://odise.io/terms-of-use/" target="_blank">%s</a>.',
				esc_html__( 'By clicking this button, you agree to Odise\'s', 'odise' ),
				esc_html__( 'Privacy Policy', 'odise' ),
				esc_html__( '&', 'odise' ),
				esc_html__( 'Terms of use', 'odise' )
			 );
			 ?>
		</p>
		<button class="button button-primary odise-confirm"><?php esc_html_e( 'I agree', 'odise' ); ?></button>
	</main>
</div>
