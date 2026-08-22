<?php
/**
 * WP-MCP Multi-Provider AI Client
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles communication with OpenAI, Anthropic Claude, Google Gemini, OpenRouter, and Ollama.
 */
class WPMCP_AI_Client {

	/**
	 * Send chat request to configured AI provider with tool declarations.
	 *
	 * @param array<int, array{role: string, content: string|array<mixed>}> $messages Conversation history.
	 * @param array<string, mixed>                                         $tools_schema Tool schemas for the provider.
	 * @param array{provider: string, api_key: string, model: string, endpoint: string} $config Active provider config.
	 * @return array<string, mixed> Normalized response.
	 */
	public function send_chat( array $messages, array $tools_schema, array $config ): array {
		$provider = $config['provider'] ?? 'openai';

		switch ( $provider ) {
			case 'anthropic':
				return $this->send_anthropic( $messages, $tools_schema, $config );

			case 'gemini':
				return $this->send_gemini( $messages, $tools_schema, $config );

			case 'openai':
			case 'openrouter':
			case 'ollama':
			case 'custom':
			default:
				return $this->send_openai_compatible( $messages, $tools_schema, $config );
		}
	}

	/**
	 * Send request to OpenAI or OpenAI-compatible endpoint (OpenRouter, Ollama, Groq, etc.).
	 */
	private function send_openai_compatible( array $messages, array $tools, array $config ): array {
		$endpoint = ! empty( $config['endpoint'] ) ? $config['endpoint'] : 'https://api.openai.com/v1/chat/completions';
		$api_key  = $config['api_key'] ?? '';
		$model    = ! empty( $config['model'] ) ? $config['model'] : 'gpt-4o';

		$headers = array(
			'Content-Type' => 'application/json',
		);

		if ( ! empty( $api_key ) && 'ollama' !== $config['provider'] ) {
			$headers['Authorization'] = 'Bearer ' . $api_key;
		}

		if ( 'openrouter' === $config['provider'] ) {
			$headers['HTTP-Referer'] = site_url();
			$headers['X-Title']      = get_bloginfo( 'name' );
		}

		$payload = array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => 0.2,
		);

		if ( ! empty( $tools ) ) {
			$payload['tools']       = $tools;
			$payload['tool_choice'] = 'auto';
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$json   = json_decode( $body, true );

		if ( $status >= 400 || empty( $json ) || ! empty( $json['error'] ) ) {
			$err_msg = $json['error']['message'] ?? $json['message'] ?? ( 'HTTP ' . $status . ': ' . substr( $body, 0, 200 ) );
			return array(
				'success' => false,
				'error'   => $err_msg,
			);
		}

		$choice     = $json['choices'][0]['message'] ?? array();
		$content    = $choice['content'] ?? '';
		$tool_calls = array();

		if ( ! empty( $choice['tool_calls'] ) && is_array( $choice['tool_calls'] ) ) {
			foreach ( $choice['tool_calls'] as $tc ) {
				$args_raw = $tc['function']['arguments'] ?? '{}';
				$args     = is_string( $args_raw ) ? json_decode( $args_raw, true ) : $args_raw;

				$tool_calls[] = array(
					'id'        => $tc['id'] ?? uniqid( 'call_' ),
					'name'      => $tc['function']['name'],
					'arguments' => is_array( $args ) ? $args : array(),
				);
			}
		}

		return array(
			'success'       => true,
			'content'       => $content,
			'tool_calls'    => $tool_calls,
			'finish_reason' => $json['choices'][0]['finish_reason'] ?? 'stop',
			'raw'           => $json,
		);
	}

	/**
	 * Send request to Anthropic Claude Messages API.
	 */
	private function send_anthropic( array $messages, array $tools, array $config ): array {
		$endpoint = 'https://api.anthropic.com/v1/messages';
		$api_key  = $config['api_key'] ?? '';
		$model    = ! empty( $config['model'] ) ? $config['model'] : 'claude-3-7-sonnet-20250219';

		$headers = array(
			'x-api-key'         => $api_key,
			'anthropic-version' => '2023-06-01',
			'content-type'      => 'application/json',
		);

		// Extract system prompt if present in messages
		$system_prompt = '';
		$filtered_msgs = array();

		foreach ( $messages as $msg ) {
			if ( 'system' === $msg['role'] ) {
				$system_prompt .= ( is_string( $msg['content'] ) ? $msg['content'] : '' ) . "\n";
			} else {
				$filtered_msgs[] = $msg;
			}
		}

		$payload = array(
			'model'       => $model,
			'messages'    => $filtered_msgs,
			'max_tokens'  => 4096,
			'temperature' => 0.2,
		);

		if ( ! empty( $system_prompt ) ) {
			$payload['system'] = trim( $system_prompt );
		}

		if ( ! empty( $tools ) ) {
			$payload['tools'] = $tools;
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$json   = json_decode( $body, true );

		if ( $status >= 400 || empty( $json ) || ! empty( $json['error'] ) ) {
			$err_msg = $json['error']['message'] ?? ( 'HTTP ' . $status . ': ' . substr( $body, 0, 200 ) );
			return array(
				'success' => false,
				'error'   => $err_msg,
			);
		}

		$content_blocks = $json['content'] ?? array();
		$text_content   = '';
		$tool_calls     = array();

		foreach ( $content_blocks as $block ) {
			if ( 'text' === ( $block['type'] ?? '' ) ) {
				$text_content .= $block['text'] . "\n";
			} elseif ( 'tool_use' === ( $block['type'] ?? '' ) ) {
				$tool_calls[] = array(
					'id'        => $block['id'] ?? uniqid( 'call_' ),
					'name'      => $block['name'],
					'arguments' => (array) ( $block['input'] ?? array() ),
				);
			}
		}

		return array(
			'success'       => true,
			'content'       => trim( $text_content ),
			'tool_calls'    => $tool_calls,
			'finish_reason' => $json['stop_reason'] ?? 'end_turn',
			'raw'           => $json,
		);
	}

	/**
	 * Send request to Google Gemini API.
	 */
	private function send_gemini( array $messages, array $tools, array $config ): array {
		$api_key = $config['api_key'] ?? '';
		$model   = ! empty( $config['model'] ) ? $config['model'] : 'gemini-2.5-pro';
		$url     = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

		$contents = array();
		$system_instruction = null;

		foreach ( $messages as $msg ) {
			if ( 'system' === $msg['role'] ) {
				$system_instruction = array(
					'parts' => array( array( 'text' => is_string( $msg['content'] ) ? $msg['content'] : '' ) ),
				);
			} else {
				$gemini_role = ( 'assistant' === $msg['role'] ) ? 'model' : 'user';
				$contents[]  = array(
					'role'  => $gemini_role,
					'parts' => array( array( 'text' => is_string( $msg['content'] ) ? $msg['content'] : wp_json_encode( $msg['content'] ) ) ),
				);
			}
		}

		$payload = array(
			'contents' => $contents,
		);

		if ( $system_instruction ) {
			$payload['systemInstruction'] = $system_instruction;
		}

		if ( ! empty( $tools['functionDeclarations'] ) ) {
			$payload['tools'] = array( $tools );
		}

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$json   = json_decode( $body, true );

		if ( $status >= 400 || empty( $json ) || ! empty( $json['error'] ) ) {
			$err_msg = $json['error']['message'] ?? ( 'HTTP ' . $status . ': ' . substr( $body, 0, 200 ) );
			return array(
				'success' => false,
				'error'   => $err_msg,
			);
		}

		$candidates = $json['candidates'][0]['content']['parts'] ?? array();
		$text       = '';
		$tool_calls = array();

		foreach ( $candidates as $part ) {
			if ( isset( $part['text'] ) ) {
				$text .= $part['text'] . "\n";
			}
			if ( isset( $part['functionCall'] ) ) {
				$tool_calls[] = array(
					'id'        => uniqid( 'gemini_call_' ),
					'name'      => $part['functionCall']['name'],
					'arguments' => (array) ( $part['functionCall']['args'] ?? array() ),
				);
			}
		}

		return array(
			'success'       => true,
			'content'       => trim( $text ),
			'tool_calls'    => $tool_calls,
			'finish_reason' => $json['candidates'][0]['finishReason'] ?? 'STOP',
			'raw'           => $json,
		);
	}
}
