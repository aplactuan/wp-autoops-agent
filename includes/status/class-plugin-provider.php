<?php
/**
 * Plugin status provider.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Status;

defined( 'ABSPATH' ) || exit;

/**
 * Collects active plugin information.
 *
 * @since 0.1.0
 */
class Plugin_Provider implements Provider {

	/**
	 * Returns the provider key.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'plugins';
	}

	/**
	 * Collects active plugin information.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins         = get_plugins();
		$active_plugins  = (array) get_option( 'active_plugins', array() );
		$network_plugins = array();
		$updates         = get_site_transient( 'update_plugins' );
		$update_response = is_object( $updates ) && isset( $updates->response )
			? (array) $updates->response
			: array();

		if ( is_multisite() ) {
			$network_plugins = array_keys(
				(array) get_site_option( 'active_sitewide_plugins', array() )
			);
		}

		$active_plugin_files = array_unique( array_merge( $active_plugins, $network_plugins ) );
		$data                = array();

		sort( $active_plugin_files );

		foreach ( $active_plugin_files as $plugin_file ) {
			if ( ! isset( $plugins[ $plugin_file ] ) ) {
				continue;
			}

			$data[] = array(
				'name'             => $plugins[ $plugin_file ]['Name'],
				'version'          => $plugins[ $plugin_file ]['Version'],
				'plugin_file'      => $plugin_file,
				'network_active'   => in_array( $plugin_file, $network_plugins, true ),
				'update_available' => isset( $update_response[ $plugin_file ] ),
			);
		}

		return array(
			'active_count' => count( $data ),
			'active'       => $data,
		);
	}
}
