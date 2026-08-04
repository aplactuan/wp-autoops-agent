<?php
/**
 * Site status reporting service.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

use WP_AutoOps_Agent\Status\Provider;

defined( 'ABSPATH' ) || exit;

/**
 * Aggregates status provider data for remote monitoring.
 *
 * @since 0.1.0
 */
class Status_Service {

	/**
	 * Status providers.
	 *
	 * @since 0.1.0
	 *
	 * @var array<int, Provider>
	 */
	private array $providers;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Provider ...$providers Status data providers.
	 */
	public function __construct( Provider ...$providers ) {
		$this->providers = $providers;
	}

	/**
	 * Returns the current site status snapshot.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_status(): array {
		$status = array();

		foreach ( $this->providers as $provider ) {
			$status[ $provider->get_key() ] = $provider->get_data();
		}

		return $status;
	}
}
