<?php
/**
 * Remote job execution service.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Accepts and processes maintenance jobs from WP AutoOps.
 *
 * Intentionally empty in the bootstrap phase.
 *
 * @since 1.0.0
 */
class Job_Service {

	/**
	 * Returns available job definitions.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>> Empty list until jobs are implemented.
	 */
	public function get_jobs(): array {
		return array();
	}

	/**
	 * Dispatches a job by identifier.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $job_id Job identifier.
	 * @param array<string, mixed> $args   Optional job arguments.
	 * @return \WP_REST_Response
	 */
	public function run_job( string $job_id, array $args = array() ): \WP_REST_Response {
		unset( $job_id, $args );

		return Response::error(
			'not_implemented',
			__( 'Job execution is not implemented yet.', 'wp-autoops-agent' ),
			501
		);
	}
}
