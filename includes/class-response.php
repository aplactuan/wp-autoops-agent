<?php
/**
 * Standardized API response helpers.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Builds consistent success and error payloads for future API responses.
 *
 * @since 1.0.0
 */
class Response {

	/**
	 * Creates a success response payload.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $data    Response data.
	 * @param string               $message Optional human-readable message.
	 * @param array<string, mixed> $meta    Optional metadata.
	 * @return array{success: true, message: string, data: mixed, meta: array<string, mixed>}
	 */
	public static function success( mixed $data = null, string $message = '', array $meta = array() ): array {
		return array(
			'success' => true,
			'message' => $message,
			'data'    => $data,
			'meta'    => $meta,
		);
	}

	/**
	 * Creates an error response payload.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $message Error message.
	 * @param string               $code    Machine-readable error code.
	 * @param array<string, mixed> $meta    Optional metadata.
	 * @return array{success: false, message: string, code: string, data: null, meta: array<string, mixed>}
	 */
	public static function error( string $message, string $code = 'error', array $meta = array() ): array {
		return array(
			'success' => false,
			'message' => $message,
			'code'    => $code,
			'data'    => null,
			'meta'    => $meta,
		);
	}
}
