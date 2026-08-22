<?php
/**
 * WP-MCP Base Tool Abstract Class
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class providing common utilities for all WP-MCP tools.
 */
abstract class WPMCP_Base_Tool implements WPMCP_Tool_Interface {

	/**
	 * Default required capability.
	 *
	 * @return string
	 */
	public function get_required_capability(): string {
		return 'manage_options';
	}

	/**
	 * Default risk level.
	 *
	 * @return string
	 */
	public function get_risk_level(): string {
		return 'read';
	}

	/**
	 * Format a successful tool execution response.
	 *
	 * @param mixed  $data    Result data.
	 * @param string $message Optional success message.
	 * @return array<string, mixed>
	 */
	protected function success( mixed $data = null, string $message = '' ): array {
		$response = array(
			'success' => true,
		);

		if ( null !== $data ) {
			$response['data'] = $data;
		}

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		return $response;
	}

	/**
	 * Format an error tool execution response.
	 *
	 * @param string $message Error message.
	 * @param mixed  $data    Optional extra error details.
	 * @param string $code    Optional error code.
	 * @return array<string, mixed>
	 */
	protected function error( string $message, mixed $data = null, string $code = 'tool_error' ): array {
		$response = array(
			'success' => false,
			'error'   => $message,
			'code'    => $code,
		);

		if ( null !== $data ) {
			$response['data'] = $data;
		}

		return $response;
	}

	/**
	 * Validate parameters against simple required keys.
	 *
	 * @param array<string, mixed> $params Provided parameters.
	 * @param array<string>        $required_keys List of required keys.
	 * @return true|WP_Error
	 */
	protected function validate_required( array $params, array $required_keys ): true|WP_Error {
		foreach ( $required_keys as $key ) {
			if ( ! isset( $params[ $key ] ) || '' === $params[ $key ] ) {
				return new WP_Error(
					'missing_param',
					sprintf( __( 'Missing required parameter: %s', 'wpmcp' ), $key )
				);
			}
		}
		return true;
	}
}
