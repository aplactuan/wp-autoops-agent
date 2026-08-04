<?php
/**
 * Action contract.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the contract for executable agent actions.
 *
 * @since 0.1.0
 */
interface Action_Interface {

	/**
	 * Executes the action.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $options Optional action options.
	 * @return array<string, mixed> Action result payload.
	 */
	public function execute( array $options = array() ): array;
}
