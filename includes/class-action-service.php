<?php
/**
 * Action registry and execution service.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent;

use WP_AutoOps_Agent\Actions\Action_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Validates, maps, and executes requested actions.
 *
 * @since 0.1.0
 */
class Action_Service {

	/**
	 * Registered actions keyed by type.
	 *
	 * @since 0.1.0
	 *
	 * @var array<string, Action_Interface>
	 */
	private array $actions;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, Action_Interface> $actions Action type map.
	 */
	public function __construct( array $actions = array() ) {
		$this->actions = array();

		foreach ( $actions as $type => $action ) {
			$this->register( (string) $type, $action );
		}
	}

	/**
	 * Registers an action handler.
	 *
	 * @since 0.1.0
	 *
	 * @param string           $type   Action type identifier.
	 * @param Action_Interface $action Action implementation.
	 * @return void
	 */
	public function register( string $type, Action_Interface $action ): void {
		$this->actions[ $type ] = $action;
	}

	/**
	 * Determines whether an action type is registered.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Action type identifier.
	 * @return bool
	 */
	public function has( string $type ): bool {
		return isset( $this->actions[ $type ] );
	}

	/**
	 * Validates and executes the requested actions.
	 *
	 * Unknown action types abort the entire batch without executing anything.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, mixed> $requested Requested action payloads.
	 * @return \WP_REST_Response
	 */
	public function execute( array $requested ): \WP_REST_Response {
		$normalized = array();

		foreach ( $requested as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$type = isset( $item['type'] ) && is_string( $item['type'] ) ? $item['type'] : '';

			if ( '' === $type || ! $this->has( $type ) ) {
				return Response::error(
					'unknown_action',
					__( 'Unsupported action type.', 'wp-autoops-agent' ),
					400
				);
			}

			$options = $item;
			unset( $options['type'] );

			if ( isset( $options['options'] ) && is_array( $options['options'] ) ) {
				$nested_options = $options['options'];
				unset( $options['options'] );
				$options = array_merge( $nested_options, $options );
			}

			$normalized[] = array(
				'type'    => $type,
				'options' => $options,
			);
		}

		$results = array();

		foreach ( $normalized as $action_request ) {
			$result = $this->actions[ $action_request['type'] ]->execute( $action_request['options'] );

			if ( $this->is_action_error( $result ) ) {
				return Response::error(
					(string) $result['error']['code'],
					(string) $result['error']['message'],
					isset( $result['error']['status'] ) ? (int) $result['error']['status'] : 500
				);
			}

			unset( $result['error'] );

			$results[] = array_merge(
				array(
					'type'    => $action_request['type'],
					'success' => true,
				),
				$result
			);
		}

		return Response::success(
			array(
				'actions' => $results,
			)
		);
	}

	/**
	 * Determines whether an action result represents a hard request failure.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $result Action execution result.
	 * @return bool
	 */
	private function is_action_error( array $result ): bool {
		return isset( $result['error'] )
			&& is_array( $result['error'] )
			&& isset( $result['error']['code'], $result['error']['message'] )
			&& is_string( $result['error']['code'] )
			&& is_string( $result['error']['message'] );
	}
}
