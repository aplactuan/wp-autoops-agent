<?php
/**
 * UpdraftPlus backup action.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Starts a scoped UpdraftPlus backup (database + plugins + themes).
 *
 * Backups are queued asynchronously so the REST request remains responsive.
 *
 * @since 0.1.0
 */
class Backup_Action implements Action_Interface {

	/**
	 * UpdraftPlus main plugin basename.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private const UPDRAFTPLUS_BASENAME = 'updraftplus/updraftplus.php';

	/**
	 * File entities included in every backup.
	 *
	 * Database is always included via updraft_backupnow_backup_all.
	 *
	 * @since 0.1.0
	 *
	 * @var array<int, string>
	 */
	private const FILE_ENTITIES = array( 'plugins', 'themes' );

	/**
	 * Queues a database + plugins + themes UpdraftPlus backup.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $options Optional action options.
	 * @return array<string, mixed>
	 */
	public function execute( array $options = array() ): array {
		try {
			if ( ! $this->is_updraftplus_available() ) {
				return array(
					'error' => array(
						'code'    => 'updraftplus_unavailable',
						'message' => __( 'UpdraftPlus is required to run backups.', 'wp-autoops-agent' ),
						'status'  => 400,
					),
				);
			}

			$backup_options = array(
				'nocloud'                    => ! empty( $options['nocloud'] ) ? 1 : 0,
				'restrict_files_to_override' => self::FILE_ENTITIES,
			);

			if ( ! empty( $options['label'] ) && is_string( $options['label'] ) ) {
				$backup_options['label'] = sanitize_text_field( $options['label'] );
			}

			$event_args = array( $backup_options );

			if ( wp_next_scheduled( 'updraft_backupnow_backup_all', $event_args ) ) {
				return array(
					'backup' => array(
						'provider' => 'updraftplus',
						'started'  => true,
						'queued'   => true,
						'scope'    => array( 'database', 'plugins', 'themes' ),
						'message'  => __( 'An UpdraftPlus backup is already queued.', 'wp-autoops-agent' ),
					),
				);
			}

			$scheduled = wp_schedule_single_event(
				time() + 1,
				'updraft_backupnow_backup_all',
				$event_args
			);

			if ( false === $scheduled ) {
				return $this->failure_response();
			}

			if ( function_exists( 'spawn_cron' ) ) {
				spawn_cron();
			}

			return array(
				'backup' => array(
					'provider' => 'updraftplus',
					'started'  => true,
					'queued'   => true,
					'scope'    => array( 'database', 'plugins', 'themes' ),
					'nocloud'  => (bool) $backup_options['nocloud'],
					'message'  => __( 'UpdraftPlus backup has been queued.', 'wp-autoops-agent' ),
				),
			);
		} catch ( \Throwable $exception ) {
			unset( $exception );

			return $this->failure_response();
		}
	}

	/**
	 * Determines whether UpdraftPlus is available for backups.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function is_updraftplus_available(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( self::UPDRAFTPLUS_BASENAME ) ) {
			return false;
		}

		return has_action( 'updraft_backupnow_backup_all' )
			|| class_exists( 'UpdraftPlus', false );
	}

	/**
	 * Builds the standardized backup failure payload.
	 *
	 * @since 0.1.0
	 *
	 * @return array{error: array{code: string, message: string, status: int}}
	 */
	private function failure_response(): array {
		return array(
			'error' => array(
				'code'    => 'backup_failed',
				'message' => __( 'Unable to start an UpdraftPlus backup.', 'wp-autoops-agent' ),
				'status'  => 500,
			),
		);
	}
}
