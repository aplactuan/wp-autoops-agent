<?php
/**
 * Backup status provider.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Status;

defined( 'ABSPATH' ) || exit;

/**
 * Collects UpdraftPlus backup progress and last-result information.
 *
 * @since 0.1.0
 */
class Backup_Provider implements Provider {

	/**
	 * UpdraftPlus main plugin basename.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private const UPDRAFTPLUS_BASENAME = 'updraftplus/updraftplus.php';

	/**
	 * Cron hooks that mean a backup-now request is still waiting to start.
	 *
	 * @since 0.1.0
	 *
	 * @var array<int, string>
	 */
	private const QUEUED_BACKUP_HOOKS = array(
		'updraft_backupnow_backup_all',
		'updraft_backupnow_backup',
		'updraft_backupnow_backup_database',
	);

	/**
	 * Returns the provider key.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'backup';
	}

	/**
	 * Collects backup status information.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		if ( ! $this->is_updraftplus_available() ) {
			return array(
				'provider'    => 'updraftplus',
				'available'   => false,
				'in_progress' => false,
				'last'        => null,
			);
		}

		return array(
			'provider'    => 'updraftplus',
			'available'   => true,
			'in_progress' => $this->is_backup_in_progress(),
			'last'        => $this->get_last_backup(),
		);
	}

	/**
	 * Determines whether UpdraftPlus is available.
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

		return class_exists( 'UpdraftPlus', false )
			|| class_exists( 'UpdraftPlus_Options', false )
			|| has_action( 'updraft_backupnow_backup_all' );
	}

	/**
	 * Determines whether a backup is queued or currently running.
	 *
	 * Matches UpdraftPlus's own notion of an active job: a waiting backup-now
	 * cron, an oneshot nonce with live jobdata, or a resume cron whose jobdata
	 * is not finished. Orphaned cron entries and stale unfinished jobdata are
	 * ignored so a completed backup does not stay "in progress".
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function is_backup_in_progress(): bool {
		if ( $this->has_queued_backupnow_cron() ) {
			return true;
		}

		$oneshot = get_site_option( 'updraft_oneshotnonce', false );

		if ( is_string( $oneshot ) && '' !== $oneshot && $this->job_is_active( $oneshot ) ) {
			return true;
		}

		foreach ( $this->get_resume_job_ids() as $job_id ) {
			if ( $this->job_is_active( $job_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether a backup-now request is still queued in cron.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function has_queued_backupnow_cron(): bool {
		$cron = get_option( 'cron' );

		if ( ! is_array( $cron ) ) {
			return false;
		}

		foreach ( $cron as $hooks ) {
			if ( ! is_array( $hooks ) ) {
				continue;
			}

			foreach ( self::QUEUED_BACKUP_HOOKS as $hook ) {
				if ( isset( $hooks[ $hook ] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Collects job IDs referenced by scheduled updraft_backup_resume events.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	private function get_resume_job_ids(): array {
		$cron = get_option( 'cron' );
		$ids  = array();

		if ( ! is_array( $cron ) ) {
			return $ids;
		}

		foreach ( $cron as $hooks ) {
			if ( ! is_array( $hooks ) || empty( $hooks['updraft_backup_resume'] ) || ! is_array( $hooks['updraft_backup_resume'] ) ) {
				continue;
			}

			foreach ( $hooks['updraft_backup_resume'] as $event ) {
				if ( empty( $event['args'][1] ) || ! is_string( $event['args'][1] ) ) {
					continue;
				}

				$ids[] = $event['args'][1];
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Determines whether a specific UpdraftPlus job is still active.
	 *
	 * @since 0.1.0
	 *
	 * @param string $job_id Job nonce.
	 * @return bool
	 */
	private function job_is_active( string $job_id ): bool {
		$jobdata = get_site_option( 'updraft_jobdata_' . $job_id, array() );

		if ( ! is_array( $jobdata ) || empty( $jobdata['backup_time'] ) ) {
			return false;
		}

		$jobstatus = isset( $jobdata['jobstatus'] ) ? (string) $jobdata['jobstatus'] : '';

		return 'finished' !== $jobstatus;
	}

	/**
	 * Returns the last completed UpdraftPlus backup summary.
	 *
	 * @since 0.1.0
	 *
	 * @return array{success: bool, backup_time: int}|null
	 */
	private function get_last_backup(): ?array {
		$last = class_exists( 'UpdraftPlus_Options', false )
			? \UpdraftPlus_Options::get_updraft_option( 'updraft_last_backup', false )
			: get_option( 'updraft_last_backup', false );

		if ( ! is_array( $last ) || empty( $last['backup_time'] ) ) {
			return null;
		}

		return array(
			'success'     => ! empty( $last['success'] ),
			'backup_time' => (int) $last['backup_time'],
		);
	}
}
