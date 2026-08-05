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
	 * Cron hooks that indicate a backup is queued or running.
	 *
	 * @since 0.1.0
	 *
	 * @var array<int, string>
	 */
	private const ACTIVE_BACKUP_HOOKS = array(
		'updraft_backupnow_backup_all',
		'updraft_backupnow_backup',
		'updraft_backupnow_backup_database',
		'updraft_backup_resume',
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
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function is_backup_in_progress(): bool {
		if ( false !== get_site_option( 'updraft_oneshotnonce', false ) ) {
			return true;
		}

		if ( $this->has_active_backup_cron() ) {
			return true;
		}

		return $this->has_active_jobdata();
	}

	/**
	 * Checks whether any UpdraftPlus backup-related cron events are scheduled.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function has_active_backup_cron(): bool {
		$cron = get_option( 'cron' );

		if ( ! is_array( $cron ) ) {
			return false;
		}

		foreach ( $cron as $hooks ) {
			if ( ! is_array( $hooks ) ) {
				continue;
			}

			foreach ( self::ACTIVE_BACKUP_HOOKS as $hook ) {
				if ( isset( $hooks[ $hook ] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks whether unfinished UpdraftPlus jobdata still exists.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function has_active_jobdata(): bool {
		global $wpdb;

		$table      = is_multisite() ? $wpdb->sitemeta : $wpdb->options;
		$key_column = is_multisite() ? 'meta_key' : 'option_name';
		$val_column = is_multisite() ? 'meta_value' : 'option_value';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table/column names are fixed wpdb properties; values are prepared.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$key_column} AS job_key, {$val_column} AS job_value FROM {$table} WHERE {$key_column} LIKE %s LIMIT 20",
				'updraft_jobdata_%'
			),
			ARRAY_A
		);

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return false;
		}

		foreach ( $rows as $row ) {
			if ( empty( $row['job_value'] ) || ! is_string( $row['job_value'] ) ) {
				continue;
			}

			$jobdata = maybe_unserialize( $row['job_value'] );

			if ( ! is_array( $jobdata ) ) {
				continue;
			}

			$jobstatus = isset( $jobdata['jobstatus'] ) ? (string) $jobdata['jobstatus'] : '';

			if ( 'finished' !== $jobstatus ) {
				return true;
			}
		}

		return false;
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
