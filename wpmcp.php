<?php
/**
 * Plugin Name:       WP-MCP: AI Prompt Copilot & MCP Server
 * Plugin URI:        https://github.com/syahmiahmad/wpmcp
 * Description:       Empowers WordPress administrators to prompt and manage their site using AI, and exposes a secure Model Context Protocol (MCP) server for external clients (Claude Desktop, Cursor).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Syahmi Ahmad
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpmcp
 * Domain Path:       /languages
 *
 * @package           WPMCP
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'WPMCP_VERSION', '1.0.0' );
define( 'WPMCP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPMCP_URL', plugin_dir_url( __FILE__ ) );
define( 'WPMCP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load Autoloader.
require_once WPMCP_PATH . 'includes/class-wpmcp-autoloader.php';
WPMCP_Autoloader::register();

/**
 * The code that runs during plugin activation.
 */
function activate_wpmcp(): void {
	WPMCP_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wpmcp(): void {
	WPMCP_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpmcp' );
register_deactivation_hook( __FILE__, 'deactivate_wpmcp' );

/**
 * Begins execution of the plugin.
 */
function run_wpmcp(): void {
	$plugin = WPMCP_Core::get_instance();
	$plugin->run();
}

add_action( 'plugins_loaded', 'run_wpmcp' );
