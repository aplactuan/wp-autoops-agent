<?php
/**
 * Update active theme action.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Updates the currently active theme via Theme_Upgrader.
 *
 * @since 0.1.0
 */
class Update_Theme_Action implements Action_Interface {

	/**
	 * Updates the active theme when an update is available.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $options Optional action options. Unused.
	 * @return array<string, mixed>
	 */
	public function execute( array $options = array() ): array {
		unset( $options );

		$this->load_dependencies();

		wp_update_themes();

		$theme       = wp_get_theme();
		$stylesheet  = $theme->get_stylesheet();
		$name        = (string) $theme->get( 'Name' );
		$from        = (string) $theme->get( 'Version' );
		$update_data = get_site_transient( 'update_themes' );

		if ( ! is_object( $update_data ) || empty( $update_data->response[ $stylesheet ] ) ) {
			return array(
				'theme' => array(
					'stylesheet' => $stylesheet,
					'updated'    => false,
					'message'    => __( 'Theme is already up to date.', 'wp-autoops-agent' ),
				),
			);
		}

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Theme_Upgrader( $skin );
		$result   = $upgrader->upgrade( $stylesheet );

		wp_clean_themes_cache( true );

		$updated_theme = wp_get_theme( $stylesheet );
		$to            = (string) $updated_theme->get( 'Version' );

		if ( true === $result ) {
			return array(
				'theme' => array(
					'stylesheet' => $stylesheet,
					'name'       => $name,
					'from'       => $from,
					'to'         => $to,
					'updated'    => true,
				),
			);
		}

		return array(
			'error' => array(
				'code'    => 'theme_update_failed',
				'message' => __( 'Unable to update active theme.', 'wp-autoops-agent' ),
				'status'  => 500,
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
}
