<?php
/**
 * WP-MCP Autoloader
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class for WP-MCP plugin.
 */
class WPMCP_Autoloader {

	/**
	 * Class map for non-standard or sub-directory classes.
	 *
	 * @var array<string, string>
	 */
	private static array $class_map = array(
		// Core
		'WPMCP_Core'                 => 'includes/class-wpmcp-core.php',
		'WPMCP_Activator'            => 'includes/class-wpmcp-activator.php',
		'WPMCP_Deactivator'          => 'includes/class-wpmcp-deactivator.php',
		'WPMCP_Settings'             => 'includes/class-wpmcp-settings.php',
		'WPMCP_Security'             => 'includes/class-wpmcp-security.php',

		// Admin
		'WPMCP_Admin'                => 'admin/class-wpmcp-admin.php',

		// Tools
		'WPMCP_Tool_Interface'       => 'includes/tools/class-wpmcp-tool-interface.php',
		'WPMCP_Base_Tool'            => 'includes/tools/class-wpmcp-base-tool.php',
		'WPMCP_Tool_Registry'        => 'includes/tools/class-wpmcp-tool-registry.php',
		'WPMCP_Content_Tools'        => 'includes/tools/class-wpmcp-content-tools.php',
		'WPMCP_Site_Tools'           => 'includes/tools/class-wpmcp-site-tools.php',
		'WPMCP_System_Tools'         => 'includes/tools/class-wpmcp-system-tools.php',
		'WPMCP_Block_Tools'          => 'includes/tools/class-wpmcp-block-tools.php',
		'WPMCP_WooCommerce_Tools'    => 'includes/tools/class-wpmcp-woocommerce-tools.php',

		// AI Engine
		'WPMCP_AI_Client'            => 'includes/ai/class-wpmcp-ai-client.php',
		'WPMCP_Agent_Orchestrator'   => 'includes/ai/class-wpmcp-agent-orchestrator.php',

		// MCP Protocol & REST
		'WPMCP_MCP_Server'           => 'includes/mcp/class-wpmcp-mcp-server.php',
		'WPMCP_REST_Controller'      => 'includes/mcp/class-wpmcp-rest-controller.php',
		'WPMCP_Auth'                 => 'includes/mcp/class-wpmcp-auth.php',

		// Safety & Rollback
		'WPMCP_Audit_Logger'         => 'includes/safety/class-wpmcp-audit-logger.php',
		'WPMCP_Rollback_Manager'     => 'includes/safety/class-wpmcp-rollback-manager.php',
	);

	/**
	 * Register the autoloader.
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload a class by name.
	 *
	 * @param string $class_name Class name to load.
	 */
	public static function autoload( string $class_name ): void {
		if ( strpos( $class_name, 'WPMCP_' ) !== 0 ) {
			return;
		}

		if ( isset( self::$class_map[ $class_name ] ) ) {
			$file = WPMCP_PATH . self::$class_map[ $class_name ];
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Fallback: standard WP naming convention in includes/
		$formatted = str_replace( '_', '-', strtolower( str_replace( 'WPMCP_', '', $class_name ) ) );
		$fallback_file = WPMCP_PATH . 'includes/class-wpmcp-' . $formatted . '.php';

		if ( file_exists( $fallback_file ) ) {
			require_once $fallback_file;
		}
	}
}
