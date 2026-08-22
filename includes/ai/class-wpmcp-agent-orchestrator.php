<?php
/**
 * WP-MCP Agent Orchestrator
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates multi-turn prompt reasoning, tool calling loop, and confirmation states.
 */
class WPMCP_Agent_Orchestrator {

	private WPMCP_Settings $settings;
	private WPMCP_Tool_Registry $tool_registry;
	private WPMCP_AI_Client $ai_client;

	public function __construct( WPMCP_Settings $settings, WPMCP_Tool_Registry $tool_registry ) {
		$this->settings      = $settings;
		$this->tool_registry = $tool_registry;
		$this->ai_client     = new WPMCP_AI_Client();
	}

	/**
	 * Process a user prompt through the agent reasoning and tool execution loop.
	 *
	 * @param string                      $prompt       User's prompt message.
	 * @param array<int, array<string, mixed>> $history  Previous conversation history.
	 * @param int                         $user_id      User ID initiating the request.
	 * @param array<string, mixed>|null   $confirmed_action If user confirmed a previously paused destructive action.
	 * @return array<string, mixed> Execution result with messages, executed tools, and status.
	 */
	public function handle_prompt(
		string $prompt,
		array $history = array(),
		int $user_id = 0,
		?array $confirmed_action = null
	): array {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$config   = $this->settings->get_active_provider_config();
		$provider = $config['provider'];

		if ( empty( $config['api_key'] ) && 'ollama' !== $provider ) {
			return array(
				'success' => false,
				'error'   => sprintf(
					__( 'API Key for provider "%s" is not configured. Please set it in WP-MCP Settings.', 'wpmcp' ),
					ucfirst( $provider )
				),
			);
		}

		// Format tools for active provider
		$tools_schema = match ( $provider ) {
			'anthropic' => $this->tool_registry->to_anthropic_format( $user_id ),
			'gemini'    => $this->tool_registry->to_gemini_format( $user_id ),
			default     => $this->tool_registry->to_openai_format( $user_id ),
		};

		$max_turns = (int) $this->settings->get( 'max_agent_turns', 8 );
		$messages  = $this->build_messages_payload( $prompt, $history, $user_id );

		$executed_tools = array();
		$current_turn   = 0;
		$final_answer   = '';

		// If this is a resume after user confirmation of a destructive action:
		if ( ! empty( $confirmed_action ) ) {
			$tool_res = $this->tool_registry->execute_tool(
				$confirmed_action['name'],
				$confirmed_action['arguments'],
				$user_id,
				$prompt
			);

			$executed_tools[] = array(
				'tool'      => $confirmed_action['name'],
				'arguments' => $confirmed_action['arguments'],
				'result'    => $tool_res,
			);

			// Append tool output to messages
			$messages[] = array(
				'role'    => 'assistant',
				'content' => sprintf( 'Executed %s with confirmed arguments.', $confirmed_action['name'] ),
			);
			$messages[] = array(
				'role'    => 'user',
				'content' => 'Tool Result: ' . wp_json_encode( $tool_res ),
			);
		}

		while ( $current_turn < $max_turns ) {
			++$current_turn;

			$ai_response = $this->ai_client->send_chat( $messages, $tools_schema, $config );

			if ( empty( $ai_response['success'] ) ) {
				return array(
					'success'        => false,
					'error'          => $ai_response['error'] ?? __( 'Unknown AI communication error.', 'wpmcp' ),
					'executed_tools' => $executed_tools,
				);
			}

			$tool_calls = $ai_response['tool_calls'] ?? array();
			$text       = $ai_response['content'] ?? '';

			if ( ! empty( $text ) ) {
				$final_answer .= $text . "\n\n";
			}

			// If no tool calls, LLM is done thinking
			if ( empty( $tool_calls ) ) {
				break;
			}

			// Process each tool call
			foreach ( $tool_calls as $tc ) {
				$tool_name = $tc['name'];
				$tool_args = $tc['arguments'];
				$tool_obj  = $this->tool_registry->get_tool( $tool_name );

				if ( ! $tool_obj ) {
					$messages[] = array(
						'role'    => 'user',
						'content' => sprintf( 'Tool error: tool "%s" is not registered.', $tool_name ),
					);
					continue;
				}

				// Check destructive confirmation requirement
				$auto_confirm = (bool) $this->settings->get( 'auto_confirm_destructive', '0' );
				if ( 'destructive' === $tool_obj->get_risk_level() && ! $auto_confirm && empty( $confirmed_action ) ) {
					return array(
						'success'              => true,
						'require_confirmation' => true,
						'pending_action'       => array(
							'id'          => $tc['id'],
							'name'        => $tool_name,
							'description' => $tool_obj->get_description(),
							'arguments'   => $tool_args,
							'risk_level'  => 'destructive',
						),
						'message'              => sprintf(
							__( 'The AI wants to run a destructive action: %s (%s). Please review and confirm.', 'wpmcp' ),
							$tool_name,
							$tool_obj->get_description()
						),
						'executed_tools'       => $executed_tools,
					);
				}

				// Execute tool
				$tool_res = $this->tool_registry->execute_tool( $tool_name, $tool_args, $user_id, $prompt );

				$executed_tools[] = array(
					'id'        => $tc['id'],
					'tool'      => $tool_name,
					'arguments' => $tool_args,
					'result'    => $tool_res,
				);

				// Feed result back into conversation
				$messages[] = array(
					'role'    => 'assistant',
					'content' => sprintf( 'Calling tool: %s with args: %s', $tool_name, wp_json_encode( $tool_args ) ),
				);
				$messages[] = array(
					'role'    => 'user',
					'content' => 'Tool execution result for ' . $tool_name . ': ' . wp_json_encode( $tool_res ),
				);
			}
		}

		return array(
			'success'        => true,
			'answer'         => trim( $final_answer ),
			'executed_tools' => $executed_tools,
			'turns'          => $current_turn,
		);
	}

	/**
	 * Build messages array with detailed system prompt.
	 */
	private function build_messages_payload( string $prompt, array $history, int $user_id ): array {
		$system_prompt = $this->get_system_prompt( $user_id );
		$messages      = array(
			array(
				'role'    => 'system',
				'content' => $system_prompt,
			),
		);

		// Append recent history
		foreach ( $history as $msg ) {
			if ( ! empty( $msg['role'] ) && isset( $msg['content'] ) ) {
				$messages[] = array(
					'role'    => in_array( $msg['role'], array( 'user', 'assistant', 'system' ), true ) ? $msg['role'] : 'user',
					'content' => (string) $msg['content'],
				);
			}
		}

		// Append new user prompt
		$messages[] = array(
			'role'    => 'user',
			'content' => $prompt,
		);

		return $messages;
	}

	/**
	 * Dynamic system prompt providing WordPress context and guidelines.
	 */
	public function get_system_prompt( int $user_id = 0 ): string {
		$site_name = get_bloginfo( 'name' );
		$site_url  = site_url();
		$theme     = wp_get_theme()->get( 'Name' );
		$user      = get_userdata( $user_id > 0 ? $user_id : get_current_user_id() );
		$user_name = $user ? $user->display_name : 'Admin';
		$custom_p  = (string) $this->settings->get( 'system_prompt_custom', '' );

		$prompt = <<<PROMPT
You are WP-MCP, an intelligent WordPress AI Copilot embedded directly in this WordPress installation.
Your mission is to help the site administrator ({$user_name}) manage, customize, troubleshoot, write content, and configure their WordPress site ({$site_name} at {$site_url}) using prompt-driven actions.

Active Theme: {$theme}
WordPress Environment: PHP {phpversion()}, WordPress {get_bloginfo('version')}

Core Operating Guidelines:
1. When asked to inspect, query, or check something, call the appropriate read tools (e.g. wpmcp_get_posts, wpmcp_get_site_info, wpmcp_list_plugins).
2. When asked to make a change (e.g., create a post, update options, write custom CSS, activate a plugin), use the dedicated tool with well-structured parameters.
3. Be concise and helpful. Format your responses with clean Markdown, summarizing the actions taken and providing direct links/URLs when available.
4. If a requested action fails, explain the exact error and suggest a corrective path.
PROMPT;

		if ( ! empty( $custom_p ) ) {
			$prompt .= "\n\nAdditional Site Instructions:\n" . $custom_p;
		}

		return apply_filters( 'wpmcp_system_prompt', $prompt, $user_id );
	}
}
