<?php
/**
 * WP-MCP Settings View
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

if ( isset( $_POST['wpmcp_save_settings'] ) && check_admin_referer( 'wpmcp_save_settings_nonce' ) ) {
	$posted = array(
		'ai_provider'              => sanitize_text_field( $_POST['ai_provider'] ?? 'openai' ),
		'openai_api_key'           => sanitize_text_field( $_POST['openai_api_key'] ?? '' ),
		'openai_model'             => sanitize_text_field( $_POST['openai_model'] ?? 'gpt-4o' ),
		'anthropic_api_key'        => sanitize_text_field( $_POST['anthropic_api_key'] ?? '' ),
		'anthropic_model'          => sanitize_text_field( $_POST['anthropic_model'] ?? 'claude-3-7-sonnet-20250219' ),
		'gemini_api_key'           => sanitize_text_field( $_POST['gemini_api_key'] ?? '' ),
		'gemini_model'             => sanitize_text_field( $_POST['gemini_model'] ?? 'gemini-2.5-pro' ),
		'openrouter_api_key'       => sanitize_text_field( $_POST['openrouter_api_key'] ?? '' ),
		'openrouter_model'         => sanitize_text_field( $_POST['openrouter_model'] ?? 'anthropic/claude-3.7-sonnet' ),
		'ollama_endpoint'          => esc_url_raw( $_POST['ollama_endpoint'] ?? 'http://localhost:11434/v1' ),
		'ollama_model'             => sanitize_text_field( $_POST['ollama_model'] ?? 'llama3.3' ),
		'mcp_enabled'              => ! empty( $_POST['mcp_enabled'] ) ? '1' : '0',
		'auto_confirm_destructive' => ! empty( $_POST['auto_confirm_destructive'] ) ? '1' : '0',
		'max_agent_turns'          => (int) ( $_POST['max_agent_turns'] ?? 8 ),
		'system_prompt_custom'     => sanitize_textarea_field( $_POST['system_prompt_custom'] ?? '' ),
	);

	$this->settings->update( $posted );
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'wpmcp' ) . '</p></div>';
}

$s = $this->settings->get_all();
?>

<div class="wrap wpmcp-admin-wrap">
	<h1><?php esc_html_e( 'WP-MCP Settings & Providers', 'wpmcp' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Configure your preferred AI provider (OpenAI, Anthropic Claude, Google Gemini, OpenRouter, or local Ollama) and manage remote MCP access.', 'wpmcp' ); ?></p>

	<form method="post" action="">
		<?php wp_nonce_field( 'wpmcp_save_settings_nonce' ); ?>

		<div class="wpmcp-settings-grid">
			<!-- Left Column: AI Providers -->
			<div class="wpmcp-settings-main">
				<div class="wpmcp-card">
					<h2><?php esc_html_e( 'Active AI Provider', 'wpmcp' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="ai_provider"><?php esc_html_e( 'Select Provider', 'wpmcp' ); ?></label></th>
							<td>
								<select name="ai_provider" id="ai_provider" class="regular-text">
									<option value="openai" <?php selected( $s['ai_provider'], 'openai' ); ?>>OpenAI (GPT-4o, GPT-4o-mini)</option>
									<option value="anthropic" <?php selected( $s['ai_provider'], 'anthropic' ); ?>>Anthropic (Claude 3.7 Sonnet / Claude 3.5)</option>
									<option value="gemini" <?php selected( $s['ai_provider'], 'gemini' ); ?>>Google Gemini (Gemini 2.5 Pro / Flash)</option>
									<option value="openrouter" <?php selected( $s['ai_provider'], 'openrouter' ); ?>>OpenRouter (Multi-model aggregation)</option>
									<option value="ollama" <?php selected( $s['ai_provider'], 'ollama' ); ?>>Ollama / Local LLM (Self-hosted)</option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<!-- OpenAI Settings -->
				<div class="wpmcp-card provider-section" id="section-openai">
					<h2><?php esc_html_e( 'OpenAI Configuration', 'wpmcp' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="openai_api_key"><?php esc_html_e( 'API Key', 'wpmcp' ); ?></label></th>
							<td>
								<input type="password" name="openai_api_key" id="openai_api_key" class="regular-text" value="<?php echo esc_attr( $s['openai_api_key'] ); ?>" placeholder="sk-...">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="openai_model"><?php esc_html_e( 'Model', 'wpmcp' ); ?></label></th>
							<td>
								<input type="text" name="openai_model" id="openai_model" class="regular-text" value="<?php echo esc_attr( $s['openai_model'] ); ?>" placeholder="gpt-4o">
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<button type="button" class="button wpmcp-test-btn" data-provider="openai"><?php esc_html_e( 'Test OpenAI Connection', 'wpmcp' ); ?></button>
								<span class="wpmcp-test-status"></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- Anthropic Settings -->
				<div class="wpmcp-card provider-section" id="section-anthropic">
					<h2><?php esc_html_e( 'Anthropic Claude Configuration', 'wpmcp' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="anthropic_api_key"><?php esc_html_e( 'API Key', 'wpmcp' ); ?></label></th>
							<td>
								<input type="password" name="anthropic_api_key" id="anthropic_api_key" class="regular-text" value="<?php echo esc_attr( $s['anthropic_api_key'] ); ?>" placeholder="sk-ant-...">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="anthropic_model"><?php esc_html_e( 'Model', 'wpmcp' ); ?></label></th>
							<td>
								<input type="text" name="anthropic_model" id="anthropic_model" class="regular-text" value="<?php echo esc_attr( $s['anthropic_model'] ); ?>" placeholder="claude-3-7-sonnet-20250219">
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<button type="button" class="button wpmcp-test-btn" data-provider="anthropic"><?php esc_html_e( 'Test Anthropic Connection', 'wpmcp' ); ?></button>
								<span class="wpmcp-test-status"></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- Google Gemini Settings -->
				<div class="wpmcp-card provider-section" id="section-gemini">
					<h2><?php esc_html_e( 'Google Gemini Configuration', 'wpmcp' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="gemini_api_key"><?php esc_html_e( 'API Key', 'wpmcp' ); ?></label></th>
							<td>
								<input type="password" name="gemini_api_key" id="gemini_api_key" class="regular-text" value="<?php echo esc_attr( $s['gemini_api_key'] ); ?>" placeholder="AIzaSy...">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_model"><?php esc_html_e( 'Model', 'wpmcp' ); ?></label></th>
							<td>
								<input type="text" name="gemini_model" id="gemini_model" class="regular-text" value="<?php echo esc_attr( $s['gemini_model'] ); ?>" placeholder="gemini-2.5-pro">
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<button type="button" class="button wpmcp-test-btn" data-provider="gemini"><?php esc_html_e( 'Test Gemini Connection', 'wpmcp' ); ?></button>
								<span class="wpmcp-test-status"></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- OpenRouter Settings -->
				<div class="wpmcp-card provider-section" id="section-openrouter">
					<h2><?php esc_html_e( 'OpenRouter Configuration', 'wpmcp' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="openrouter_api_key"><?php esc_html_e( 'API Key', 'wpmcp' ); ?></label></th>
							<td>
								<input type="password" name="openrouter_api_key" id="openrouter_api_key" class="regular-text" value="<?php echo esc_attr( $s['openrouter_api_key'] ); ?>" placeholder="sk-or-...">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="openrouter_model"><?php esc_html_e( 'Model Identifier', 'wpmcp' ); ?></label></th>
							<td>
								<input type="text" name="openrouter_model" id="openrouter_model" class="regular-text" value="<?php echo esc_attr( $s['openrouter_model'] ); ?>" placeholder="anthropic/claude-3.7-sonnet">
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<button type="button" class="button wpmcp-test-btn" data-provider="openrouter"><?php esc_html_e( 'Test OpenRouter Connection', 'wpmcp' ); ?></button>
								<span class="wpmcp-test-status"></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- Ollama Local LLM -->
				<div class="wpmcp-card provider-section" id="section-ollama">
					<h2><?php esc_html_e( 'Ollama / Local Endpoint', 'wpmcp' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="ollama_endpoint"><?php esc_html_e( 'Endpoint URL', 'wpmcp' ); ?></label></th>
							<td>
								<input type="url" name="ollama_endpoint" id="ollama_endpoint" class="regular-text" value="<?php echo esc_attr( $s['ollama_endpoint'] ); ?>" placeholder="http://localhost:11434/v1">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ollama_model"><?php esc_html_e( 'Model', 'wpmcp' ); ?></label></th>
							<td>
								<input type="text" name="ollama_model" id="ollama_model" class="regular-text" value="<?php echo esc_attr( $s['ollama_model'] ); ?>" placeholder="llama3.3">
							</td>
						</tr>
					</table>
				</div>

				<!-- Custom Prompt Guidance -->
				<div class="wpmcp-card">
					<h2><?php esc_html_e( 'Custom Agent Instructions', 'wpmcp' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Optional instructions appended to the AI Copilot system prompt (e.g. brand voice, style guide, rules).', 'wpmcp' ); ?></p>
					<textarea name="system_prompt_custom" rows="4" class="large-text" placeholder="e.g. Always write blog posts with a friendly tone and include an FAQ section at the end..."><?php echo esc_textarea( $s['system_prompt_custom'] ); ?></textarea>
				</div>
			</div>

			<!-- Right Column: Safety & MCP Options -->
			<div class="wpmcp-settings-sidebar">
				<div class="wpmcp-card">
					<h2><?php esc_html_e( 'Safety & Guardrails', 'wpmcp' ); ?></h2>
					<p>
						<label>
							<input type="checkbox" name="auto_confirm_destructive" value="1" <?php checked( $s['auto_confirm_destructive'], '1' ); ?>>
							<strong><?php esc_html_e( 'Auto-execute destructive tools', 'wpmcp' ); ?></strong>
						</label>
						<br>
						<span class="description"><?php esc_html_e( 'If unchecked, actions like deleting posts or switching themes will require 1-click confirmation.', 'wpmcp' ); ?></span>
					</p>
					<p>
						<label for="max_agent_turns"><strong><?php esc_html_e( 'Max Agent Turns:', 'wpmcp' ); ?></strong></label><br>
						<input type="number" name="max_agent_turns" id="max_agent_turns" min="1" max="20" value="<?php echo esc_attr( $s['max_agent_turns'] ); ?>" style="width:80px;">
					</p>
				</div>

				<div class="wpmcp-card">
					<h2><?php esc_html_e( 'Remote MCP Server', 'wpmcp' ); ?></h2>
					<p>
						<label>
							<input type="checkbox" name="mcp_enabled" value="1" <?php checked( $s['mcp_enabled'], '1' ); ?>>
							<strong><?php esc_html_e( 'Enable Remote MCP Server', 'wpmcp' ); ?></strong>
						</label>
					</p>
					<p class="description"><?php esc_html_e( 'Allows Claude Desktop, Cursor, and IDE agents to connect to your WordPress site.', 'wpmcp' ); ?></p>
					<p><strong><?php esc_html_e( 'MCP SSE Endpoint:', 'wpmcp' ); ?></strong><br>
					<code><?php echo esc_html( rest_url( 'wpmcp/v1/sse' ) ); ?></code></p>
				</div>

				<div class="wpmcp-card">
					<h2><?php esc_html_e( 'Generate MCP API Key', 'wpmcp' ); ?></h2>
					<div id="wpmcp-token-generator">
						<input type="text" id="wpmcp-token-name" placeholder="Token Name (e.g. Claude Desktop)" class="widefat" style="margin-bottom:8px;">
						<button type="button" id="wpmcp-btn-create-token" class="button button-secondary"><?php esc_html_e( 'Create Token', 'wpmcp' ); ?></button>
						<div id="wpmcp-new-token-display" style="margin-top:10px; display:none;"></div>
					</div>
				</div>

				<p>
					<input type="submit" name="wpmcp_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save All Settings', 'wpmcp' ); ?>">
				</p>
			</div>
		</div>
	</form>
</div>
