<?php
/**
 * WP-MCP Deactivator Class
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fired during plugin deactivation.
 */
class WPMCP_Deactivator {

	/**
	 * Clean up temporary states and rewrite rules on deactivation.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
