<?php
/**
 * Status provider contract.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Status;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the contract for status data providers.
 *
 * @since 0.1.0
 */
interface Provider {

	/**
	 * Returns the provider key used in the aggregated response.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_key(): string;

	/**
	 * Collects status data.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array;
}
