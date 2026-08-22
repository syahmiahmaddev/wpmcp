<?php
/**
 * WP-MCP Help & Remote MCP Setup View
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

$sse_url      = rest_url( 'wpmcp/v1/sse' );
$messages_url = rest_url( 'wpmcp/v1/messages' );
$site_name    = get_bloginfo( 'name' );
$bridge_path  = WPMCP_PATH . 'bin/mcp-bridge.js';
?>

<div class="wrap wpmcp-admin-wrap">
	<h1><?php esc_html_e( 'Remote MCP Configuration & Documentation', 'wpmcp' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Connect Google Antigravity, Claude Desktop, Cursor, Windsurf, or any MCP-enabled LLM agent to your WordPress site.', 'wpmcp' ); ?></p>

	<div class="wpmcp-settings-grid">
		<div class="wpmcp-settings-main">
			<!-- Antigravity & IDEs -->
			<div class="wpmcp-card">
				<h2><?php esc_html_e( '1. Connect with Google Antigravity (AGY)', 'wpmcp' ); ?></h2>
				<p><?php esc_html_e( 'Add WP-MCP to your Antigravity configuration or mcpServers list:', 'wpmcp' ); ?></p>
				<pre class="wpmcp-code-block"><code>{
  "mcpServers": {
    "wordpress-<?php echo esc_attr( sanitize_title( $site_name ) ); ?>": {
      "command": "node",
      "args": [
        "<?php echo esc_attr( $bridge_path ); ?>",
        "--url", "<?php echo esc_url( $messages_url ); ?>",
        "--token", "YOUR_WPMCP_API_TOKEN"
      ]
    }
  }
}</code></pre>
			</div>

			<!-- Claude Desktop -->
			<div class="wpmcp-card">
				<h2><?php esc_html_e( '2. Connect with Claude Desktop', 'wpmcp' ); ?></h2>
				<p><?php esc_html_e( 'Add to your ', 'wpmcp' ); ?> <code>claude_desktop_config.json</code>:</p>
				<pre class="wpmcp-code-block"><code>{
  "mcpServers": {
    "wordpress-<?php echo esc_attr( sanitize_title( $site_name ) ); ?>": {
      "url": "<?php echo esc_url( $sse_url ); ?>",
      "headers": {
        "Authorization": "Bearer YOUR_WPMCP_API_TOKEN"
      }
    }
  }
}</code></pre>
				<p><em><?php esc_html_e( 'Note: You can generate an MCP API Token in WP-MCP > Settings, or use a WordPress Application Password.', 'wpmcp' ); ?></em></p>
			</div>

			<!-- Cursor, Windsurf, Cline, Continue -->
			<div class="wpmcp-card">
				<h2><?php esc_html_e( '3. Connect with Cursor, Windsurf, Cline & Continue', 'wpmcp' ); ?></h2>
				<p><?php esc_html_e( 'In Cursor/Windsurf/Cline MCP settings, use command mode with the built-in universal bridge:', 'wpmcp' ); ?></p>
				<pre class="wpmcp-code-block"><code>node <?php echo esc_html( $bridge_path ); ?> --url <?php echo esc_url( home_url() ); ?> --token YOUR_WPMCP_API_TOKEN</code></pre>
			</div>

			<!-- Registered Tools List -->
			<div class="wpmcp-card">
				<h2><?php esc_html_e( '4. Registered WordPress Tools', 'wpmcp' ); ?></h2>
				<p><?php esc_html_e( 'The following tools are available to all connected AI agents and in-admin prompt copilot:', 'wpmcp' ); ?></p>

				<div class="wpmcp-tool-list">
					<?php
					$core  = WPMCP_Core::get_instance();
					$tools = $core->tool_registry->get_all_tools();
					foreach ( $tools as $tool ) :
						$risk_class = match ( $tool->get_risk_level() ) {
							'destructive' => 'wpmcp-badge-danger',
							'write'       => 'wpmcp-badge-warning',
							default       => 'wpmcp-badge-info',
						};
						?>
						<div class="wpmcp-tool-item">
							<div class="wpmcp-tool-header">
								<code><?php echo esc_html( $tool->get_name() ); ?></code>
								<span class="wpmcp-badge <?php echo esc_attr( $risk_class ); ?>"><?php echo esc_html( ucfirst( $tool->get_risk_level() ) ); ?></span>
								<span class="wpmcp-badge wpmcp-badge-secondary"><?php echo esc_html( $tool->get_required_capability() ); ?></span>
							</div>
							<p class="wpmcp-tool-desc"><?php echo esc_html( $tool->get_description() ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="wpmcp-settings-sidebar">
			<div class="wpmcp-card">
				<h3><?php esc_html_e( 'Server Endpoints', 'wpmcp' ); ?></h3>
				<p><strong>SSE URL:</strong><br><small><code><?php echo esc_html( $sse_url ); ?></code></small></p>
				<p><strong>Messages URL:</strong><br><small><code><?php echo esc_html( $messages_url ); ?></code></small></p>
				<p><strong>Bridge Script:</strong><br><small><code><?php echo esc_html( $bridge_path ); ?></code></small></p>
				<p><strong>Protocol Version:</strong><br><code>2024-11-05</code></p>
			</div>

			<div class="wpmcp-card">
				<h3><?php esc_html_e( 'Keyboard Shortcut', 'wpmcp' ); ?></h3>
				<p><?php esc_html_e( 'Press ', 'wpmcp' ); ?> <kbd>Cmd + K</kbd> / <kbd>Ctrl + K</kbd> <?php esc_html_e( 'anywhere in the WordPress admin to summon the AI copilot instantly.', 'wpmcp' ); ?></p>
			</div>
		</div>
	</div>
</div>
