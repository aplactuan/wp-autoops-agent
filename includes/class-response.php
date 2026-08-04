<?php
/**
 * Standardized REST API response helpers.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Builds consistent success and error responses for API endpoints.
 *
 * @since 1.0.0
 */
class Response {

	/**
	 * Creates a success response.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $data    Response data.
	 * @param string               $message Optional human-readable message.
	 * @param int                  $status  HTTP status code.
	 * @param array<string, mixed> $meta    Optional response metadata.
	 * @return \WP_REST_Response
	 */
	public static function success(
		mixed $data = null,
		string $message = '',
		int $status = 200,
		array $meta = array()
	): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => $message,
				'data'    => $data,
				'error'   => null,
				'meta'    => $meta,
			),
			$status
		);
	}

	/**
	 * Creates an error response.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $code    Machine-readable error code.
	 * @param string               $message Human-readable error message.
	 * @param int                  $status  HTTP status code.
	 * @param array<string, mixed> $details Optional error details.
	 * @param array<string, mixed> $meta    Optional response metadata.
	 * @return \WP_REST_Response
	 */
	public static function error(
		string $code,
		string $message,
		int $status = 400,
		array $details = array(),
		array $meta = array()
	): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => $message,
				'data'    => null,
				'error'   => array(
					'code'    => $code,
					'details' => $details,
				),
				'meta'    => $meta,
			),
			$status
		);
	}
}
