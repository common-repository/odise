<?php
/**
 * Contains the Event_Transport service class
 *
 * @package Odise
 */

namespace Odise;

/**
 * Class Event_Transport
 *
 * Contains functions for reporting events to collector in the backend.
 *
 * @package Odise
 */
class Event_Transport {
	/**
	 * The site id
	 *
	 * @var mixed $site_id
	 */
	public $site_id;

	/**
	 * The session manager.
	 *
	 * @var Session_Manager $session
	 */
	private $session;

	/**
	 * The user type; users from other blogs will be considered guest. Plus it picks up user type overrides.
	 *
	 * @var string $user_type
	 */
	public $user_type;

	/**
	 * The real user type; users from other blogs will be considered guest. It does not pick up user type overrides.
	 *
	 * @var string $user_type_real
	 */
	public $user_type_real;

	/**
	 * The WordPress user id; users from other blogs will be null.
	 *
	 * @var mixed $user_id
	 */
	public $user_id;

	/**
	 * Event_Transport constructor.
	 *
	 * @param int|null    $override_user_id
	 * @param string|null $override_user_type
	 */
	public function __construct( $override_user_id = null, $override_user_type = null ) {
		$this->site_id = get_option( 'odise_site_id' );
		$this->session = new Session_Manager();

		if ( isset( $override_user_id, $override_user_type ) ) {
			$this->user_id        = $override_user_id;
			$this->user_type      = $override_user_type;
			$this->user_type_real = $this->user_type;
		} else {
			$eff_user_id   = $this->session->get_effective_user_id();
			$eff_user_type = $this->session->get_effective_user_type();

			// Use this function to exclude Aliens from current blog's users.
			$this->user_type      = get_user_type();
			$this->user_type_real = $this->user_type;

			$user_id       = get_current_user_id();
			$this->user_id = ( $user_id && 'guest' !== $this->user_type ) ? $user_id : null;

			if ( $eff_user_id && $eff_user_type && 'guest' === $this->user_type && 'guest' !== $eff_user_type ) {
				$this->user_id   = $eff_user_id;
				$this->user_type = $eff_user_type;
			}
		}
	}

	/**
	 * Sends an event.
	 *
	 * @param string $event_name The event name, negotiated with the backend.
	 * @param array  $event_data Additional event data.
	 */
	public function send_event( $event_name, $event_data = array() ) {
		if ( ! $this->site_id ) {
			return;
		}

		$url = ODISE_API_URL . '/v1/outstand';

		$payload = array(
			'event'   => array(
				'id'      => wp_generate_uuid4(),
				'type'    => $event_name,
				'version' => 1,
			),
			'session' => array(
				'clientId'  => $this->session->get_client_id(),
				'sessionId' => $this->session->get_session_id(),
			),
			'site'    => array(
				'id' => $this->site_id,
			),
			'user'    => array(
				'id'            => $this->user_id,
				'effectiveType' => $this->user_type,
				'realType'      => $this->user_type_real,
			),
			'plugin'  => array(
				'platform' => 'wordpress',
				'version'  => ODISE_VERSION,
			),
			'wpData'  => $event_data,
		);

		wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => true,
				'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'        => wp_json_encode( $payload ),
				'cookies'     => array(),
			)
		);
	}
}
