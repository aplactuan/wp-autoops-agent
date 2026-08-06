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
 * Reactivates plugins that core deactivates before non-cron upgrades.
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

		/*
		 * Plugin_Upgrader::upgrade() silently deactivates active plugins when not
		 * running via cron (see deactivate_plugin_before_upgrade). Capture prior
		 * activation state so we can restore it after the upgrade completes —
		 * wp-admin JS does this for the Plugins screen; remote API callers do not.
		 */
		$was_active         = is_plugin_active( $basename );
		$was_network_active = is_multisite() && is_plugin_active_for_network( $basename );

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );

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

		$fresh_plugins   = get_plugins();
		$current_version = isset( $fresh_plugins[ $basename ]['Version'] )
			? (string) $fresh_plugins[ $basename ]['Version']
			: $previous_version;

		$success = ( true === $result );

		if ( $success ) {
			$message = __( 'Plugin updated successfully.', 'wp-autoops-agent' );
		} elseif ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();
		} elseif ( $skin->get_errors()->has_errors() ) {
			$message = $skin->get_error_messages();
		} else {
			$message = __( 'Plugin update failed.', 'wp-autoops-agent' );
		}

		$response = array(
			'basename'         => $basename,
			'success'          => $success,
			'skipped'          => false,
			'previous_version' => $previous_version,
			'current_version'  => $current_version,
			'message'          => $message,
		);

		/*
		 * Always attempt reactivation after a prior-active plugin was swapped —
		 * core deactivates before the upgrade even when the install later fails.
		 */
		if ( $was_active || $was_network_active ) {
			$reactivation = $this->reactivate_plugin( $basename, $was_network_active );

			$response['reactivated'] = $reactivation['success'];

			if ( ! $reactivation['success'] && null !== $reactivation['message'] ) {
				$response['reactivation_message'] = $reactivation['message'];
			}
		}

		return $response;
	}

	/**
	 * Reactivates a plugin that WordPress deactivated before the upgrade.
	 *
	 * @since 0.1.0
	 *
	 * @param string $basename           Plugin basename.
	 * @param bool   $was_network_active Whether the plugin was network-activated.
	 * @return array{success: bool, message: string|null}
	 */
	private function reactivate_plugin( string $basename, bool $was_network_active ): array {
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $basename ) ) {
			return array(
				'success' => false,
				'message' => __( 'Plugin file is missing after update; could not reactivate.', 'wp-autoops-agent' ),
			);
		}

		if ( $was_network_active ) {
			if ( is_plugin_active_for_network( $basename ) ) {
				return array(
					'success' => true,
					'message' => null,
				);
			}
		} elseif ( is_plugin_active( $basename ) ) {
			return array(
				'success' => true,
				'message' => null,
			);
		}

		$activated = activate_plugin( $basename, '', $was_network_active, true );

		if ( is_wp_error( $activated ) ) {
			return array(
				'success' => false,
				'message' => $activated->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => null,
		);
	}
}
