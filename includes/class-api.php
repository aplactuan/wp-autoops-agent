<?php
/**
 * REST API controller.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Owns REST route registration, permission callbacks, and handlers.
 *
 * @since 1.0.0
 */
class API {

	/**
	 * REST API namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public const NAMESPACE = 'wp-autoops/v1';

	/**
	 * Authentication helper.
	 *
	 * @since 1.0.0
	 *
	 * @var Auth
	 */
	protected Auth $auth;

	/**
	 * Status service.
	 *
	 * @since 1.0.0
	 *
	 * @var Status_Service
	 */
	protected Status_Service $status_service;

	/**
	 * Job service.
	 *
	 * @since 1.0.0
	 *
	 * @var Job_Service
	 */
	protected Job_Service $job_service;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Auth           $auth            Authentication helper.
	 * @param Status_Service $status_service  Status service.
	 * @param Job_Service    $job_service     Job service.
	 */
	public function __construct(
		Auth $auth,
		Status_Service $status_service,
		Job_Service $job_service
	) {
		$this->auth           = $auth;
		$this->status_service = $status_service;
		$this->job_service    = $job_service;
	}

	/**
	 * Registers REST API routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/ping',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_ping' ),
				'permission_callback' => array( $this->auth, 'permissions_check' ),
			)
		);
	}

	/**
	 * Handles the ping endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Current REST request.
	 * @return \WP_REST_Response
	 */
	public function get_ping( \WP_REST_Request $request ): \WP_REST_Response {
		unset( $request );

		return Response::success(
			array(
				'plugin_version'    => WP_AUTOOPS_AGENT_VERSION,
				'wordpress_version' => get_bloginfo( 'version' ),
				'php_version'       => PHP_VERSION,
				'timestamp'         => current_time( 'c', true ),
			)
		);
	}
}
