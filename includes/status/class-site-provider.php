<?php
/**
 * Site status provider.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Status;

defined( 'ABSPATH' ) || exit;

/**
 * Collects general site information.
 *
 * @since 0.1.0
 */
class Site_Provider implements Provider {

	/**
	 * Returns the provider key.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'site';
	}

	/**
	 * Collects general site information.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		return array(
			'name'         => get_bloginfo( 'name' ),
			'url'          => home_url( '/' ),
			'wp_url'       => site_url( '/' ),
			'locale'       => get_locale(),
			'timezone'     => wp_timezone_string(),
			'is_multisite' => is_multisite(),
		);
	}
}
