<?php
/**
 * Contains the Session_Manager class.
 *
 * @package Odise
 */

namespace Odise;

/**
 * Class Session_Manager
 *
 * Retrieves analytics session details from cookies.
 *
 * @package Odise
 */
class Session_Manager {
	/**
	 * The parsed contents of the cookie
	 *
	 * @var object
	 */
	private $cookie;

	/**
	 * Session_Manager constructor
	 */
	public function __construct() {
		$this->get_analytics_cookie();
	}

	/**
	 * Get the permanent client id of the visitor
	 *
	 * @return string|null
	 */
	public function get_client_id() {
		return isset( $this->cookie->clientId ) ? $this->cookie->clientId : null;
	}

	/**
	 * Get the temporary session id of the visitor
	 *
	 * @return string|null
	 */
	public function get_session_id() {
		return isset( $this->cookie->sessionId ) ? $this->cookie->sessionId : null;
	}

	/**
	 * Put a reset session id request at the footer which will be picked up by the script.
	 *
	 * Does NOT change the session ID for the duration of the current request.
	 *
	 * @param $session_id
	 * @param string $reason
	 */
	public function reset_session_id( $session_id, $reason ) {
		$session_reset = array(
			'reason'    => $reason,
			'sessionId' => $session_id,
		);

		$echo_reset = function () use ( $session_reset ) {
			?>
			<script type="text/javascript">
				window._odiseSessionReset = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( $session_reset ) ); ?>' ) );
			</script>;
			<?php
		};

		add_action( 'wp_footer', $echo_reset, 5 );
	}

	/**
	 * Override the user properties of the visitor; e.g upgrade them to a Guest Customer
	 *
	 * @param string $type
	 * @param string $username
	 * @param string $first_name
	 * @param string $last_name
	 * @param string $registered
	 */
	public function override_user_props( $type, $username, $first_name, $last_name, $registered ) {
		// Not overriding `id` because the user does not exist on WordPress in Guest Checkouts.

		$user_override = array(
			'type'       => $type,
			'username'   => $username,
			'firstName'  => $first_name,
			'lastName'   => $last_name,
			'registered' => $registered,
			'noOfOrders' => 1,
		);

		$echo_override = function () use ( $user_override ) {
			?>
			<script type="text/javascript">
				window._odiseUserOverride = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( $user_override ) ); ?>' ) );
			</script>;
			<?php
		};

		add_action( 'wp_footer', $echo_override, 5 );
	}

	/**
	 * Gets the effective user if if set by the Front-end.
	 *
	 * @return int|null
	 */
	public function get_effective_user_id() {
		if ( isset( $this->cookie->overrideUser->id ) ) {
			return $this->cookie->overrideUser->id;
		}

		return null;
	}

	/**
	 * Gets the effective user type if set by the Front-end.
	 *
	 * @return string|null
	 */
	public function get_effective_user_type() {
		if ( isset( $this->cookie->overrideUser->type ) ) {
			return $this->cookie->overrideUser->type;
		}

		return null;
	}

	/**
	 * Parses the analytics cookie
	 *
	 * @return void
	 */
	private function get_analytics_cookie() {
		$this->cookie = json_decode( filter_input( INPUT_COOKIE, 'outstand' ) );
	}
}
