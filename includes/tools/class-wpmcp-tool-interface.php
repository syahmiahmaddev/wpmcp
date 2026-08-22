<?php
/**
 * WP-MCP Tool Interface
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interface that all WP-MCP tools must implement.
 */
interface WPMCP_Tool_Interface {

	/**
	 * Unique tool identifier (e.g., 'wpmcp_get_posts').
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Human-readable description of what the tool does.
	 *
	 * @return string
	 */
	public function get_description(): string;

	/**
	 * Tool parameters schema formatted in standard JSON Schema (OpenAI / MCP compliant).
	 *
	 * @return array<string, mixed>
	 */
	public function get_parameters_schema(): array;

	/**
	 * Required WordPress user capability (e.g. 'manage_options', 'edit_posts').
	 *
	 * @return string
	 */
	public function get_required_capability(): string;

	/**
	 * Risk level of the tool ('read', 'write', 'destructive').
	 *
	 * @return string
	 */
	public function get_risk_level(): string;

	/**
	 * Execute the tool with given parameters.
	 *
	 * @param array<string, mixed> $params  Input parameters.
	 * @param int                  $user_id User ID executing the tool.
	 * @return array<string, mixed> Result array containing success status and payload.
	 */
	public function execute( array $params, int $user_id ): array;
}
