<?php
/**
 * REST API controller.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Owns REST route registration and request handlers.
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
	 * Action service.
	 *
	 * @since 0.1.0
	 *
	 * @var Action_Service
	 */
	protected Action_Service $action_service;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Auth           $auth            Authentication helper.
	 * @param Status_Service $status_service  Status service.
	 * @param Action_Service $action_service  Action service.
	 */
	public function __construct(
		Auth $auth,
		Status_Service $status_service,
		Action_Service $action_service
	) {
		$this->auth           = $auth;
		$this->status_service = $status_service;
		$this->action_service = $action_service;
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

		register_rest_route(
			self::NAMESPACE,
			'/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this->auth, 'permissions_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/actions',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'post_actions' ),
				'permission_callback' => array( $this->auth, 'permissions_check' ),
				'args'                => array(
					'actions' => array(
						'required' => true,
						'type'     => 'array',
					),
				),
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

	/**
	 * Handles the status endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_REST_Request $request Current REST request.
	 * @return \WP_REST_Response
	 */
	public function get_status( \WP_REST_Request $request ): \WP_REST_Response {
		unset( $request );

		return Response::success(
			$this->status_service->get_status(),
			array(
				'agent_version' => WP_AUTOOPS_AGENT_VERSION,
				'generated_at'  => gmdate( 'Y-m-d\TH:i:s\Z' ),
			)
		);
	}

	/**
	 * Handles the actions endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_REST_Request $request Current REST request.
	 * @return \WP_REST_Response
	 */
	public function post_actions( \WP_REST_Request $request ): \WP_REST_Response {
		$actions = $request->get_param( 'actions' );

		if ( ! is_array( $actions ) ) {
			return Response::error(
				'invalid_actions',
				__( 'The actions parameter must be an array.', 'wp-autoops-agent' ),
				400
			);
		}

		return $this->action_service->execute( $actions );
	}
}
