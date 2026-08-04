<?php
/**
 * Update WordPress Core action.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Updates WordPress Core via Core_Upgrader.
 *
 * @since 0.1.0
 */
class Update_Core_Action implements Action_Interface {

	/**
	 * Updates WordPress Core when an update is available.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $options Optional action options. Unused.
	 * @return array<string, mixed>
	 */
	public function execute( array $options = array() ): array {
		unset( $options );

		try {
			$this->load_dependencies();

			wp_version_check();

			$from   = $this->get_installed_version();
			$update = get_preferred_from_update_core();

			if ( ! is_object( $update ) || empty( $update->response ) || 'latest' === $update->response ) {
				$latest = isset( $update->current ) && is_string( $update->current ) && '' !== $update->current
					? $update->current
					: $from;

				return array(
					'core' => array(
						'current' => $from,
						'latest'  => $latest,
						'updated' => false,
						'message' => __( 'WordPress is already up to date.', 'wp-autoops-agent' ),
					),
				);
			}

			if ( 'upgrade' !== $update->response ) {
				return array(
					'core' => array(
						'current' => $from,
						'latest'  => $from,
						'updated' => false,
						'message' => __( 'WordPress is already up to date.', 'wp-autoops-agent' ),
					),
				);
			}

			$to = isset( $update->current ) ? (string) $update->current : '';

			if ( '' === $to || version_compare( $from, $to, '>=' ) ) {
				return array(
					'core' => array(
						'current' => $from,
						'latest'  => '' !== $to ? $to : $from,
						'updated' => false,
						'message' => __( 'WordPress is already up to date.', 'wp-autoops-agent' ),
					),
				);
			}

			$allow_relaxed_file_ownership = isset( $update->new_files ) && ! $update->new_files;

			$skin     = new \WP_Ajax_Upgrader_Skin();
			$upgrader = new \Core_Upgrader( $skin );
			$result   = $upgrader->upgrade(
				$update,
				array(
					'allow_relaxed_file_ownership' => $allow_relaxed_file_ownership,
				)
			);

			if ( is_wp_error( $result ) ) {
				if ( 'up_to_date' === $result->get_error_code() ) {
					$current = $this->get_installed_version();

					return array(
						'core' => array(
							'current' => $current,
							'latest'  => $current,
							'updated' => false,
							'message' => __( 'WordPress is already up to date.', 'wp-autoops-agent' ),
						),
					);
				}

				return $this->failure_response();
			}

			if ( ! is_string( $result ) || '' === $result ) {
				return $this->failure_response();
			}

			return array(
				'core' => array(
					'from'    => $from,
					'to'      => $result,
					'updated' => true,
				),
			);
		} catch ( \Throwable $exception ) {
			unset( $exception );

			return $this->failure_response();
		}
	}

	/**
	 * Returns the installed WordPress version.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	private function get_installed_version(): string {
		require ABSPATH . WPINC . '/version.php';

		global $wp_version;

		if ( is_string( $wp_version ) && '' !== $wp_version ) {
			return $wp_version;
		}

		return (string) get_bloginfo( 'version' );
	}

	/**
	 * Builds the standardized core update failure payload.
	 *
	 * @since 0.1.0
	 *
	 * @return array{error: array{code: string, message: string, status: int}}
	 */
	private function failure_response(): array {
		return array(
			'error' => array(
				'code'    => 'core_update_failed',
				'message' => __( 'Unable to update WordPress Core.', 'wp-autoops-agent' ),
				'status'  => 500,
			),
		);
	}

	/**
	 * Loads WordPress admin dependencies required for core upgrades.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! function_exists( 'wp_version_check' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}

		if ( ! function_exists( 'get_preferred_from_update_core' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}
}
