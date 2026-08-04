<?php
/**
 * WordPress status provider.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Status;

defined( 'ABSPATH' ) || exit;

/**
 * Collects WordPress runtime information.
 *
 * @since 0.1.0
 */
class WordPress_Provider implements Provider {

	/**
	 * Returns the provider key.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'wordpress';
	}

	/**
	 * Collects WordPress runtime information.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		return array(
			'version'          => get_bloginfo( 'version' ),
			'environment_type' => wp_get_environment_type(),
			'debug_enabled'    => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'cron_disabled'    => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		);
	}
}
