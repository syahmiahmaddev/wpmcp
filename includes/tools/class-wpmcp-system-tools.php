<?php
/**
 * WP-MCP System, Theme & Plugin Management Tools
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and implements tools for plugins, themes, and system diagnostics.
 */
class WPMCP_System_Tools {

	/**
	 * Register all system tools with the registry.
	 *
	 * @param WPMCP_Tool_Registry $registry Registry instance.
	 */
	public static function register( WPMCP_Tool_Registry $registry ): void {
		$registry->register_tool( new WPMCP_Tool_List_Plugins() );
		$registry->register_tool( new WPMCP_Tool_Toggle_Plugin_State() );
		$registry->register_tool( new WPMCP_Tool_List_Themes() );
		$registry->register_tool( new WPMCP_Tool_Switch_Theme() );
		$registry->register_tool( new WPMCP_Tool_Get_Site_Health() );
	}
}

/**
 * Tool: List installed plugins.
 */
class WPMCP_Tool_List_Plugins extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_list_plugins';
	}

	public function get_description(): string {
		return 'List all installed WordPress plugins with their activation status, version, author, and description.';
	}

	public function get_required_capability(): string {
		return 'activate_plugins';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'status' => array(
					'type'        => 'string',
					'enum'        => array( 'all', 'active', 'inactive' ),
					'default'     => 'all',
					'description' => 'Filter by plugin status ("all", "active", "inactive").',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$status_filter = sanitize_key( $params['status'] ?? 'all' );
		$all_plugins   = get_plugins();
		$active_plugin_files = get_option( 'active_plugins', array() );
		$results       = array();

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$is_active = in_array( $plugin_file, $active_plugin_files, true ) || is_plugin_active_for_network( $plugin_file );

			if ( 'active' === $status_filter && ! $is_active ) {
				continue;
			}
			if ( 'inactive' === $status_filter && $is_active ) {
				continue;
			}

			$results[] = array(
				'file'        => $plugin_file,
				'name'        => $plugin_data['Name'],
				'version'     => $plugin_data['Version'],
				'author'      => wp_strip_all_tags( $plugin_data['Author'] ),
				'is_active'   => $is_active,
				'description' => wp_strip_all_tags( $plugin_data['Description'] ),
			);
		}

		return $this->success(
			array(
				'count'   => count( $results ),
				'plugins' => $results,
			),
			sprintf( __( 'Found %d plugins.', 'wpmcp' ), count( $results ) )
		);
	}
}

/**
 * Tool: Activate or Deactivate a plugin.
 */
class WPMCP_Tool_Toggle_Plugin_State extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_toggle_plugin_state';
	}

	public function get_description(): string {
		return 'Activate or deactivate a WordPress plugin by its plugin file path (e.g. "woocommerce/woocommerce.php").';
	}

	public function get_required_capability(): string {
		return 'activate_plugins';
	}

	public function get_risk_level(): string {
		return 'destructive';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'plugin_file', 'action' ),
			'properties' => array(
				'plugin_file' => array(
					'type'        => 'string',
					'description' => 'The plugin file identifier (e.g., "akismet/akismet.php" or "wpmcp/wpmcp.php").',
				),
				'action'      => array(
					'type'        => 'string',
					'enum'        => array( 'activate', 'deactivate' ),
					'description' => 'Action to perform ("activate" or "deactivate").',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = sanitize_text_field( $params['plugin_file'] ?? '' );
		$action      = sanitize_key( $params['action'] ?? '' );

		// Prevent deactivating WP-MCP itself via prompt without safety
		if ( 'deactivate' === $action && strpos( $plugin_file, 'wpmcp' ) !== false ) {
			return $this->error( __( 'Cannot deactivate WP-MCP from within prompt.', 'wpmcp' ) );
		}

		if ( 'activate' === $action ) {
			$result = activate_plugin( $plugin_file );
			if ( is_wp_error( $result ) ) {
				return $this->error( $result->get_error_message() );
			}
			return $this->success( array( 'plugin' => $plugin_file, 'status' => 'active' ), sprintf( __( 'Activated plugin %s.', 'wpmcp' ), $plugin_file ) );
		}

		if ( 'deactivate' === $action ) {
			deactivate_plugins( $plugin_file );
			return $this->success( array( 'plugin' => $plugin_file, 'status' => 'inactive' ), sprintf( __( 'Deactivated plugin %s.', 'wpmcp' ), $plugin_file ) );
		}

		return $this->error( __( 'Invalid action.', 'wpmcp' ) );
	}
}

/**
 * Tool: List installed themes.
 */
class WPMCP_Tool_List_Themes extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_list_themes';
	}

	public function get_description(): string {
		return 'List all installed themes, active theme indicator, and block theme support.';
	}

	public function get_required_capability(): string {
		return 'switch_themes';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => (object) array(),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$themes        = wp_get_themes();
		$current_theme = wp_get_theme();
		$results       = array();

		foreach ( $themes as $slug => $theme ) {
			$results[] = array(
				'slug'        => $slug,
				'name'        => $theme->get( 'Name' ),
				'version'     => $theme->get( 'Version' ),
				'author'      => $theme->get( 'Author' ),
				'is_active'   => ( $theme->get_stylesheet() === $current_theme->get_stylesheet() ),
				'is_block'    => $theme->is_block_theme(),
				'description' => wp_strip_all_tags( $theme->get( 'Description' ) ),
			);
		}

		return $this->success(
			array(
				'count'  => count( $results ),
				'themes' => $results,
			),
			sprintf( __( 'Found %d themes.', 'wpmcp' ), count( $results ) )
		);
	}
}

/**
 * Tool: Switch active theme.
 */
class WPMCP_Tool_Switch_Theme extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_switch_theme';
	}

	public function get_description(): string {
		return 'Switch the active WordPress theme by providing the theme stylesheet slug.';
	}

	public function get_required_capability(): string {
		return 'switch_themes';
	}

	public function get_risk_level(): string {
		return 'destructive';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'theme_slug' ),
			'properties' => array(
				'theme_slug' => array(
					'type'        => 'string',
					'description' => 'The slug of the installed theme to activate (e.g. "twentytwentyfour").',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$slug  = sanitize_text_field( $params['theme_slug'] ?? '' );
		$theme = wp_get_theme( $slug );

		if ( ! $theme->exists() ) {
			return $this->error( sprintf( __( 'Theme "%s" does not exist.', 'wpmcp' ), $slug ) );
		}

		switch_theme( $slug );

		return $this->success(
			array(
				'theme_slug' => $slug,
				'theme_name' => $theme->get( 'Name' ),
			),
			sprintf( __( 'Switched theme to "%s".', 'wpmcp' ), $theme->get( 'Name' ) )
		);
	}
}

/**
 * Tool: Get Site Health and Server Diagnostics.
 */
class WPMCP_Tool_Get_Site_Health extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_get_site_health';
	}

	public function get_description(): string {
		return 'Retrieve WordPress site health diagnostic details including PHP version, memory limits, database size, debug mode, and object cache status.';
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => (object) array(),
		);
	}

	public function execute( array $params, int $user_id ): array {
		global $wpdb;

		$upload_dir = wp_upload_dir();

		$diagnostics = array(
			'php_version'          => phpversion(),
			'php_memory_limit'     => ini_get( 'memory_limit' ),
			'php_max_execution'    => ini_get( 'max_execution_time' ),
			'php_post_max_size'    => ini_get( 'post_max_size' ),
			'php_upload_max_files' => ini_get( 'upload_max_filesize' ),
			'wp_debug'             => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'wp_debug_log'         => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
			'wp_memory_limit'      => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : '40M',
			'wp_max_memory_limit'  => defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : '256M',
			'mysql_version'        => $wpdb->db_version(),
			'https_active'         => is_ssl(),
			'uploads_writable'     => wp_is_writable( $upload_dir['basedir'] ),
			'rest_url'             => get_rest_url(),
			'active_plugins_count' => count( (array) get_option( 'active_plugins', array() ) ),
		);

		return $this->success( $diagnostics, __( 'Site health diagnostics retrieved.', 'wpmcp' ) );
	}
}
