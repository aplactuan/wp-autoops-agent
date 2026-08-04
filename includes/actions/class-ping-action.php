<?php
/**
 * Ping action.
 *
 * @package WP_AutoOps_Agent
 */

namespace WP_AutoOps_Agent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies that the action framework is operational.
 *
 * @since 0.1.0
 */
class Ping_Action implements Action_Interface {

	/**
	 * Executes the ping action.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $options Optional action options.
	 * @return array{result: array{message: string}}
	 */
	public function execute( array $options = array() ): array {
		unset( $options );

		return array(
			'result' => array(
				'message' => 'Action framework operational.',
			),
		);
	}
}
