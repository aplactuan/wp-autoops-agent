<?php
/**
 * Authentication and request authorization helpers.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Authorizes REST requests using WordPress authentication.
 *
 * @since 1.0.0
 */
class Auth {

	/**
	 * Determines whether the current user may access agent endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the user is authenticated and authorized.
	 */
	public function is_authorized(): bool {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}

	/**
	 * Checks permissions for protected REST routes.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Current REST request.
	 * @return true|\WP_Error True when authorized; otherwise, an error.
	 */
	public function permissions_check( \WP_REST_Request $request ) {
		unset( $request );

		if ( $this->is_authorized() ) {
			return true;
		}

		return new \WP_Error(
			'wp_autoops_unauthorized',
			__( 'Authentication with an administrator account is required.', 'wp-autoops-agent' ),
			array( 'status' => is_user_logged_in() ? 403 : 401 )
		);
	}
}
