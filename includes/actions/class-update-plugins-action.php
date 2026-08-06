<?php
/**
 * Update plugins action.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Updates enabled WordPress plugins with available updates via Plugin_Upgrader.
 *
 * Continues processing remaining plugins when a single update fails.
 *
 * Mimics WordPress background (cron) updates so Plugin_Upgrader does not
 * deactivate active plugins. Remote API requests have no browser UI to
 * reactivate them afterward; calling activate_plugin() mid-batch is unsafe
 * because a fatal during sandbox scrape aborts the request and leaves later
 * plugins deactivated.
 *
 * @since 0.1.0
 */
class Update_Plugins_Action implements Action_Interface {

	/**
	 * Updates all enabled plugins that currently have an available update.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $options Action options.
	 * @return array{
	 *     result: array{
	 *         updated: int,
	 *         failed: int,
	 *         skipped: int,
	 *         plugins: array<int, array<string, mixed>>
	 *     }
	 * }
	 */
	public function execute( array $options = array() ): array {
		$this->load_dependencies();

		$installed_plugins = get_plugins();
		$update_data       = get_site_transient( 'update_plugins' );
		$update_response   = is_object( $update_data ) && isset( $update_data->response )
			? (array) $update_data->response
			: array();
		$active_plugins    = (array) get_option( 'active_plugins', array() );
		$network_plugins   = is_multisite()
			? array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) )
			: array();
		$enabled_plugins   = array_unique( array_merge( $active_plugins, $network_plugins ) );
		$requested         = array_values(
			array_intersect(
				array_keys( $update_response ),
				$enabled_plugins
			)
		);

		$results = array();
		$updated = 0;
		$failed  = 0;
		$skipped = 0;

		foreach ( $requested as $basename ) {
			$result = $this->update_plugin( $basename, $installed_plugins, $update_response );

			$results[] = $result;

			if ( ! empty( $result['skipped'] ) ) {
				++$skipped;
			} elseif ( ! empty( $result['success'] ) ) {
				++$updated;
				$installed_plugins = get_plugins();
			} else {
				++$failed;
			}
		}

		wp_clean_plugins_cache( true );

		return array(
			'result' => array(
				'updated' => $updated,
				'failed'  => $failed,
				'skipped' => $skipped,
				'plugins' => $results,
			),
		);
	}

	/**
	 * Loads WordPress admin dependencies required for upgrades.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	/**
	 * Attempts to update a single plugin.
	 *
	 * @since 0.1.0
	 *
	 * @param string                    $basename          Plugin basename.
	 * @param array<string, array>      $installed_plugins Installed plugin headers.
	 * @param array<string, object|array> $update_response Available update packages.
	 * @return array<string, mixed>
	 */
	private function update_plugin( string $basename, array $installed_plugins, array $update_response ): array {
		if ( ! isset( $installed_plugins[ $basename ] ) ) {
			return array(
				'basename' => $basename,
				'success'  => false,
				'skipped'  => true,
				'message'  => __( 'Plugin is not installed.', 'wp-autoops-agent' ),
			);
		}

		$previous_version = (string) $installed_plugins[ $basename ]['Version'];

		if ( ! isset( $update_response[ $basename ] ) ) {
			return array(
				'basename'         => $basename,
				'success'          => false,
				'skipped'          => true,
				'previous_version' => $previous_version,
				'current_version'  => $previous_version,
				'message'          => __( 'No update is available for this plugin.', 'wp-autoops-agent' ),
			);
		}

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );

		/*
		 * Plugin_Upgrader::deactivate_plugin_before_upgrade() skips deactivation
		 * when wp_doing_cron() is true (background updates). Remote jobs must
		 * use the same path — there is no admin UI to reactivate afterward.
		 * This also enables maintenance mode around active-plugin file swaps.
		 */
		add_filter( 'wp_doing_cron', '__return_true' );

		try {
			/*
			 * Keep the update transient intact until the whole batch finishes so
			 * later plugins still have package URLs available.
			 */
			$result = $upgrader->upgrade(
				$basename,
				array(
					'clear_update_cache' => false,
				)
			);
		} finally {
			remove_filter( 'wp_doing_cron', '__return_true' );
		}

		$fresh_plugins   = get_plugins();
		$current_version = isset( $fresh_plugins[ $basename ]['Version'] )
			? (string) $fresh_plugins[ $basename ]['Version']
			: $previous_version;

		if ( true === $result ) {
			return array(
				'basename'         => $basename,
				'success'          => true,
				'skipped'          => false,
				'previous_version' => $previous_version,
				'current_version'  => $current_version,
				'message'          => __( 'Plugin updated successfully.', 'wp-autoops-agent' ),
			);
		}

		$error_message = __( 'Plugin update failed.', 'wp-autoops-agent' );

		if ( is_wp_error( $result ) ) {
			$error_message = $result->get_error_message();
		} elseif ( $skin->get_errors()->has_errors() ) {
			$error_message = $skin->get_error_messages();
		}

		return array(
			'basename'         => $basename,
			'success'          => false,
			'skipped'          => false,
			'previous_version' => $previous_version,
			'current_version'  => $current_version,
			'message'          => $error_message,
		);
	}
}
