<?php
/**
 * PHP status provider.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Status;

defined( 'ABSPATH' ) || exit;

/**
 * Collects PHP runtime information.
 *
 * @since 0.1.0
 */
class PHP_Provider implements Provider {

	/**
	 * Returns the provider key.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'php';
	}

	/**
	 * Collects PHP runtime information.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		return array(
			'version'             => PHP_VERSION,
			'sapi'                => PHP_SAPI,
			'memory_limit'        => ini_get( 'memory_limit' ),
			'max_execution_time'  => (int) ini_get( 'max_execution_time' ),
			'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
		);
	}
}
