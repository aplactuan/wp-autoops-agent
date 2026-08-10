<?php
/**
 * Hook loader for registering actions and filters.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and fires WordPress actions and filters.
 *
 * Collects hooks during bootstrap and registers them in one pass.
 *
 * @since 1.0.0
 */
class Loader {

	/**
	 * Registered action hooks.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{hook: string, component: object|string, callback: string, priority: int, accepted_args: int}>
	 */
	protected array $actions = array();

	/**
	 * Registered filter hooks.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{hook: string, component: object|string, callback: string, priority: int, accepted_args: int}>
	 */
	protected array $filters = array();

	/**
	 * Adds an action to the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $hook          The WordPress action name.
	 * @param object|string $component     Object or class that owns the callback.
	 * @param string        $callback      Method name on the component.
	 * @param int           $priority      Hook priority. Default 10.
	 * @param int           $accepted_args Number of accepted arguments. Default 1.
	 * @return void
	 */
	public function add_action(
		string $hook,
		$component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Adds a filter to the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $hook          The WordPress filter name.
	 * @param object|string $component     Object or class that owns the callback.
	 * @param string        $callback      Method name on the component.
	 * @param int           $priority      Hook priority. Default 10.
	 * @param int           $accepted_args Number of accepted arguments. Default 1.
	 * @return void
	 */
	public function add_filter(
		string $hook,
		$component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Stores a hook in the given collection.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array{hook: string, component: object|string, callback: string, priority: int, accepted_args: int}> $hooks         Existing hook collection.
	 * @param string                                                                                                         $hook          The WordPress hook name.
	 * @param object|string                                                                                                  $component     Object or class that owns the callback.
	 * @param string                                                                                                         $callback      Method name on the component.
	 * @param int                                                                                                            $priority      Hook priority.
	 * @param int                                                                                                            $accepted_args Number of accepted arguments.
	 * @return array<int, array{hook: string, component: object|string, callback: string, priority: int, accepted_args: int}>
	 */
	protected function add(
		array $hooks,
		string $hook,
		$component,
		string $callback,
		int $priority,
		int $accepted_args
	): array {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Registers all collected actions and filters with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
