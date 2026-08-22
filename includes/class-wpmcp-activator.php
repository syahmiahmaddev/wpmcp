<?php
/**
 * WP-MCP Activator Class
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fired during plugin activation.
 */
class WPMCP_Activator {

	/**
	 * Activate plugin and initialize database tables.
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_default_options();
		flush_rewrite_rules();
	}

	/**
	 * Create or update custom database tables.
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// 1. Audit Logs Table
		$table_audit = $wpdb->prefix . 'wpmcp_audit_logs';
		$sql_audit   = "CREATE TABLE $table_audit (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			tool_name varchar(100) NOT NULL,
			risk_level varchar(20) NOT NULL DEFAULT 'read',
			prompt text NULL,
			arguments longtext NULL,
			result longtext NULL,
			snapshot_before longtext NULL,
			status varchar(20) NOT NULL DEFAULT 'success',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY tool_name (tool_name),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql_audit );

		// 2. API Tokens Table for Remote MCP Client Auth
		$table_tokens = $wpdb->prefix . 'wpmcp_api_tokens';
		$sql_tokens   = "CREATE TABLE $table_tokens (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			token_hash varchar(64) NOT NULL,
			name varchar(100) NOT NULL,
			permissions varchar(50) NOT NULL DEFAULT 'read_write',
			last_used_at datetime NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY token_hash (token_hash),
			KEY user_id (user_id)
		) $charset_collate;";

		dbDelta( $sql_tokens );
	}

	/**
	 * Set default options if not already configured.
	 */
	private static function set_default_options(): void {
		$defaults = array(
			'ai_provider'              => 'openai',
			'openai_api_key'           => '',
			'openai_model'             => 'gpt-4o',
			'anthropic_api_key'        => '',
			'anthropic_model'          => 'claude-3-7-sonnet-20250219',
			'gemini_api_key'           => '',
			'gemini_model'             => 'gemini-2.5-pro',
			'openrouter_api_key'       => '',
			'openrouter_model'         => 'anthropic/claude-3.7-sonnet',
			'ollama_endpoint'          => 'http://localhost:11434/v1',
			'ollama_model'             => 'llama3.3',
			'custom_endpoint'          => '',
			'custom_api_key'           => '',
			'custom_model'             => '',
			'mcp_enabled'              => '1',
			'auto_confirm_destructive' => '0',
			'max_agent_turns'          => 8,
			'system_prompt_custom'     => '',
		);

		$existing = get_option( 'wpmcp_settings', array() );
		$merged   = wp_parse_args( $existing, $defaults );
		update_option( 'wpmcp_settings', $merged );
	}
}
