<?php
/**
 * Theme status provider.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Status;

defined( 'ABSPATH' ) || exit;

/**
 * Collects active theme information.
 *
 * @since 0.1.0
 */
class Theme_Provider implements Provider {

	/**
	 * Returns the provider key.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'theme';
	}

	/**
	 * Collects active theme information.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		$theme           = wp_get_theme();
		$parent          = $theme->parent();
		$updates         = get_site_transient( 'update_themes' );
		$update_response = is_object( $updates ) && isset( $updates->response )
			? (array) $updates->response
			: array();

		return array(
			'name'             => $theme->get( 'Name' ),
			'version'          => $theme->get( 'Version' ),
			'stylesheet'       => $theme->get_stylesheet(),
			'template'         => $theme->get_template(),
			'parent'           => $parent ? $parent->get( 'Name' ) : null,
			'update_available' => isset( $update_response[ $theme->get_stylesheet() ] ),
		);
	}
}
