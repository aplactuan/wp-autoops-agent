<?php
/**
 * Authentication and request authorization helpers.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Validates credentials for future remote agent requests.
 *
 * No authentication logic is implemented in the bootstrap phase.
 *
 * @since 1.0.0
 */
class Auth {

	/**
	 * Determines whether the current request is authorized.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always false until authentication is implemented.
	 */
	public function is_authorized(): bool {
		return false;
	}

	/**
	 * Permission callback placeholder for REST routes.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always false until authentication is implemented.
	 */
	public function permissions_check(): bool {
		return $this->is_authorized();
	}
}
