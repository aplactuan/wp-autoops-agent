<?php
/**
 * Plugin Name:       WP AutoOps Agent
 * Plugin URI:        https://github.com/aplactuan/wp-autoops-agent
 * Description:       Remote monitoring and maintenance agent for WP AutoOps.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Adrian Lactuan
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-autoops-agent
 * Domain Path:       /languages
 *
 * @package WP_AutoOps_Agent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Current plugin version.
 */
define( 'WP_AUTOOPS_AGENT_VERSION', '1.0.0' );

/**
 * Absolute path to the plugin directory, with trailing slash.
 */
define( 'WP_AUTOOPS_AGENT_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Absolute URL to the plugin directory, with trailing slash.
 */
define( 'WP_AUTOOPS_AGENT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Minimum required PHP version.
 */
define( 'WP_AUTOOPS_AGENT_MIN_PHP', '8.1' );

/**
 * Minimum required WordPress version.
 */
define( 'WP_AUTOOPS_AGENT_MIN_WP', '6.5' );

require_once WP_AUTOOPS_AGENT_PATH . 'includes/class-loader.php';
require_once WP_AUTOOPS_AGENT_PATH . 'includes/class-plugin.php';

/**
 * Runs on plugin activation.
 *
 * Validates environment requirements. Aborts activation if unmet.
 *
 * @since 1.0.0
 *
 * @return void
 */
function wp_autoops_agent_activate(): void {
	$errors = array();

	if ( version_compare( PHP_VERSION, WP_AUTOOPS_AGENT_MIN_PHP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Required PHP version, 2: Current PHP version. */
			__( 'WP AutoOps Agent requires PHP %1$s or higher. You are running PHP %2$s.', 'wp-autoops-agent' ),
			WP_AUTOOPS_AGENT_MIN_PHP,
			PHP_VERSION
		);
	}

	global $wp_version;

	if ( version_compare( $wp_version, WP_AUTOOPS_AGENT_MIN_WP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Required WordPress version, 2: Current WordPress version. */
			__( 'WP AutoOps Agent requires WordPress %1$s or higher. You are running WordPress %2$s.', 'wp-autoops-agent' ),
			WP_AUTOOPS_AGENT_MIN_WP,
			$wp_version
		);
	}

	if ( ! empty( $errors ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html( implode( ' ', $errors ) ),
			esc_html__( 'Plugin Activation Error', 'wp-autoops-agent' ),
			array( 'back_link' => true )
		);
	}
}

/**
 * Runs on plugin deactivation.
 *
 * @since 1.0.0
 *
 * @return void
 */
function wp_autoops_agent_deactivate(): void {
	// Reserved for cleanup of scheduled events and transient state.
}

register_activation_hook( __FILE__, 'wp_autoops_agent_activate' );
register_deactivation_hook( __FILE__, 'wp_autoops_agent_deactivate' );

/**
 * Returns the main plugin instance.
 *
 * @since 1.0.0
 *
 * @return WP_AutoOps_Agent\Plugin
 */
function wp_autoops_agent(): WP_AutoOps_Agent\Plugin {
	return WP_AutoOps_Agent\Plugin::instance();
}

/**
 * Bootstraps the plugin after all plugins are loaded.
 *
 * @since 1.0.0
 *
 * @return void
 */
function wp_autoops_agent_init(): void {
	wp_autoops_agent()->run();
}

add_action( 'plugins_loaded', 'wp_autoops_agent_init' );
