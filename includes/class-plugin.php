<?php
/**
 * Main plugin orchestrator.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates plugin bootstrap and shared services.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Hook loader.
	 *
	 * @since 1.0.0
	 *
	 * @var Loader
	 */
	protected Loader $loader;

	/**
	 * REST API controller.
	 *
	 * @since 1.0.0
	 *
	 * @var API
	 */
	protected API $api;

	/**
	 * Whether the plugin has been run.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected bool $booted = false;

	/**
	 * Returns the shared plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->loader = new Loader();
		$this->define_hooks();
	}

	/**
	 * Prevents cloning.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevents unserialization.
	 *
	 * @since 1.0.0
	 *
	 * @throws \Exception Always, to block unserialization.
	 * @return void
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Loads required class files.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once WP_AUTOOPS_AGENT_PATH . 'includes/class-response.php';
		require_once WP_AUTOOPS_AGENT_PATH . 'includes/class-auth.php';
		require_once WP_AUTOOPS_AGENT_PATH . 'includes/class-status-service.php';
		require_once WP_AUTOOPS_AGENT_PATH . 'includes/class-job-service.php';
		require_once WP_AUTOOPS_AGENT_PATH . 'includes/class-api.php';
	}

	/**
	 * Registers plugin hooks with the loader.
	 *
	 * Extension points are wired here as features are added.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function define_hooks(): void {
		$auth           = new Auth();
		$status_service = new Status_Service();
		$job_service    = new Job_Service();
		$this->api      = new API( $auth, $status_service, $job_service );

		$this->loader->add_action( 'init', $this, 'load_textdomain' );
		$this->loader->add_action( 'rest_api_init', $this->api, 'register_routes' );
	}

	/**
	 * Loads the plugin text domain for translations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-autoops-agent',
			false,
			dirname( plugin_basename( WP_AUTOOPS_AGENT_PATH . 'wp-autoops-agent.php' ) ) . '/languages'
		);
	}

	/**
	 * Returns the hook loader.
	 *
	 * @since 1.0.0
	 *
	 * @return Loader
	 */
	public function get_loader(): Loader {
		return $this->loader;
	}

	/**
	 * Registers all hooks and marks the plugin as booted.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run(): void {
		if ( $this->booted ) {
			return;
		}

		$this->loader->run();
		$this->booted = true;
	}
}
