<h2><?php esc_html_e( 'You have unsubscribed from future emails.', 'odise' ); ?></h2>

<p>
	<strong><?php esc_html_e( 'Note:', 'odise' ); ?></strong>
	<?php
	esc_html_e(
		'You may still receive non-marketing emails from us, including, but not limited to, emails related to your user account and shop orders.',
		'odise'
	);
	?>
</p>

<p>
	<?php
	esc_html_e(
		'Sorry to see you go! Feel free to drop us a line if you\'d like to join our email list again to receive the latest and greatest articles and deals.',
		'odise'
	);
	?>
</p>

<p>
	<?php
	printf(
		'<a href="%1$s">%2$s</a>',
		esc_url( get_home_url( null, '/' ) ),
		esc_html__( 'Click here to return to home page.', 'odise' )
	);
	?>
</p>
