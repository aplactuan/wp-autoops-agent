<?php
/**
 * Update themes action.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Updates all installed themes that have an available update via Theme_Upgrader.
 *
 * Continues processing remaining themes when a single update fails.
 *
 * @since 0.1.0
 */
class Update_Theme_Action implements Action_Interface {

	/**
	 * Updates every theme with an available update.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $options Optional action options. Unused.
	 * @return array{
	 *     result: array{
	 *         updated: int,
	 *         failed: int,
	 *         skipped: int,
	 *         themes: array<int, array<string, mixed>>
	 *     }
	 * }
	 */
	public function execute( array $options = array() ): array {
		unset( $options );

		$this->load_dependencies();

		wp_update_themes();

		$update_data     = get_site_transient( 'update_themes' );
		$update_response = is_object( $update_data ) && isset( $update_data->response )
			? (array) $update_data->response
			: array();

		$results = array();
		$updated = 0;
		$failed  = 0;
		$skipped = 0;

		foreach ( array_keys( $update_response ) as $stylesheet ) {
			$result = $this->update_theme( (string) $stylesheet );

			$results[] = $result;

			if ( ! empty( $result['skipped'] ) ) {
				++$skipped;
			} elseif ( ! empty( $result['success'] ) ) {
				++$updated;
			} else {
				++$failed;
			}
		}

		wp_clean_themes_cache( true );

		return array(
			'result' => array(
				'updated' => $updated,
				'failed'  => $failed,
				'skipped' => $skipped,
				'themes'  => $results,
			),
		);
	}

	/**
	 * Loads WordPress admin dependencies required for theme upgrades.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! function_exists( 'wp_update_themes' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	/**
	 * Attempts to update a single theme.
	 *
	 * @since 0.1.0
	 *
	 * @param string $stylesheet Theme stylesheet directory name.
	 * @return array<string, mixed>
	 */
	private function update_theme( string $stylesheet ): array {
		$theme = wp_get_theme( $stylesheet );

		if ( ! $theme->exists() ) {
			return array(
				'stylesheet' => $stylesheet,
				'success'    => false,
				'skipped'    => true,
				'message'    => __( 'Theme is not installed.', 'wp-autoops-agent' ),
			);
		}

		$name             = (string) $theme->get( 'Name' );
		$previous_version = (string) $theme->get( 'Version' );

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Theme_Upgrader( $skin );

		/*
		 * Keep the update transient intact until the whole batch finishes so
		 * later themes still have package URLs available.
		 */
		$result = $upgrader->upgrade(
			$stylesheet,
			array(
				'clear_update_cache' => false,
			)
		);

		$updated_theme   = wp_get_theme( $stylesheet );
		$current_version = (string) $updated_theme->get( 'Version' );

		if ( true === $result ) {
			return array(
				'stylesheet'       => $stylesheet,
				'name'             => $name,
				'success'          => true,
				'skipped'          => false,
				'previous_version' => $previous_version,
				'current_version'  => $current_version,
				'message'          => __( 'Theme updated successfully.', 'wp-autoops-agent' ),
			);
		}

		$error_message = __( 'Theme update failed.', 'wp-autoops-agent' );

		if ( is_wp_error( $result ) ) {
			$error_message = $result->get_error_message();
		} elseif ( $skin->get_errors()->has_errors() ) {
			$error_message = $skin->get_error_messages();
		}

		return array(
			'stylesheet'       => $stylesheet,
			'name'             => $name,
			'success'          => false,
			'skipped'          => false,
			'previous_version' => $previous_version,
			'current_version'  => $current_version,
			'message'          => $error_message,
		);
	}
}
