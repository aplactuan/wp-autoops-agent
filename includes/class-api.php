<?php
/**
 * REST API registration scaffold.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates future REST route registration.
 *
 * Intentionally empty in the bootstrap phase — no endpoints are registered yet.
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
	public const NAMESPACE = 'wp-autoops-agent/v1';

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
	 * Reserved for future endpoint registration.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// No routes in the bootstrap phase.
	}
}
