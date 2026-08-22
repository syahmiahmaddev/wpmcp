<?php
/**
 * WP-MCP Settings Class
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages plugin settings, retrieval, encryption/masking, and sanitization.
 */
class WPMCP_Settings {

	public const OPTION_NAME = 'wpmcp_settings';

	/**
	 * Cached settings.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $settings = null;

	/**
	 * Default settings array.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return array(
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
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if not set.
	 * @return mixed
	 */
	public function get( string $key, mixed $default = null ): mixed {
		if ( null === $this->settings ) {
			$this->settings = get_option( self::OPTION_NAME, self::get_defaults() );
		}

		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		}

		$defaults = self::get_defaults();
		return $defaults[ $key ] ?? $default;
	}

	/**
	 * Get all settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		if ( null === $this->settings ) {
			$this->settings = get_option( self::OPTION_NAME, self::get_defaults() );
		}
		return wp_parse_args( $this->settings, self::get_defaults() );
	}

	/**
	 * Update all or subset of settings.
	 *
	 * @param array<string, mixed> $new_values New values to merge and save.
	 * @return bool True if updated, false otherwise.
	 */
	public function update( array $new_values ): bool {
		$current = $this->get_all();
		$sanitized = $this->sanitize_settings( wp_parse_args( $new_values, $current ) );
		$updated   = update_option( self::OPTION_NAME, $sanitized );
		if ( $updated ) {
			$this->settings = $sanitized;
		}
		return $updated;
	}

	/**
	 * Get active provider's credentials and model.
	 *
	 * @return array{provider: string, api_key: string, model: string, endpoint: string}
	 */
	public function get_active_provider_config(): array {
		$provider = (string) $this->get( 'ai_provider', 'openai' );

		switch ( $provider ) {
			case 'anthropic':
				return array(
					'provider' => 'anthropic',
					'api_key'  => (string) $this->get( 'anthropic_api_key', '' ),
					'model'    => (string) $this->get( 'anthropic_model', 'claude-3-7-sonnet-20250219' ),
					'endpoint' => 'https://api.anthropic.com/v1/messages',
				);

			case 'gemini':
				return array(
					'provider' => 'gemini',
					'api_key'  => (string) $this->get( 'gemini_api_key', '' ),
					'model'    => (string) $this->get( 'gemini_model', 'gemini-2.5-pro' ),
					'endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
				);

			case 'openrouter':
				return array(
					'provider' => 'openrouter',
					'api_key'  => (string) $this->get( 'openrouter_api_key', '' ),
					'model'    => (string) $this->get( 'openrouter_model', 'anthropic/claude-3.7-sonnet' ),
					'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
				);

			case 'ollama':
				return array(
					'provider' => 'ollama',
					'api_key'  => 'ollama',
					'model'    => (string) $this->get( 'ollama_model', 'llama3.3' ),
					'endpoint' => rtrim( (string) $this->get( 'ollama_endpoint', 'http://localhost:11434/v1' ), '/' ) . '/chat/completions',
				);

			case 'custom':
				return array(
					'provider' => 'custom',
					'api_key'  => (string) $this->get( 'custom_api_key', '' ),
					'model'    => (string) $this->get( 'custom_model', '' ),
					'endpoint' => (string) $this->get( 'custom_endpoint', '' ),
				);

			case 'openai':
			default:
				return array(
					'provider' => 'openai',
					'api_key'  => (string) $this->get( 'openai_api_key', '' ),
					'model'    => (string) $this->get( 'openai_model', 'gpt-4o' ),
					'endpoint' => 'https://api.openai.com/v1/chat/completions',
				);
		}
	}

	/**
	 * Sanitize all settings fields.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed> Sanitized array.
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		$sanitized['ai_provider'] = sanitize_text_field( $input['ai_provider'] ?? 'openai' );

		// Keys
		$sanitized['openai_api_key']     = sanitize_text_field( trim( $input['openai_api_key'] ?? '' ) );
		$sanitized['openai_model']       = sanitize_text_field( trim( $input['openai_model'] ?? 'gpt-4o' ) );
		$sanitized['anthropic_api_key']  = sanitize_text_field( trim( $input['anthropic_api_key'] ?? '' ) );
		$sanitized['anthropic_model']    = sanitize_text_field( trim( $input['anthropic_model'] ?? 'claude-3-7-sonnet-20250219' ) );
		$sanitized['gemini_api_key']     = sanitize_text_field( trim( $input['gemini_api_key'] ?? '' ) );
		$sanitized['gemini_model']       = sanitize_text_field( trim( $input['gemini_model'] ?? 'gemini-2.5-pro' ) );
		$sanitized['openrouter_api_key'] = sanitize_text_field( trim( $input['openrouter_api_key'] ?? '' ) );
		$sanitized['openrouter_model']   = sanitize_text_field( trim( $input['openrouter_model'] ?? 'anthropic/claude-3.7-sonnet' ) );

		// Local / Custom
		$sanitized['ollama_endpoint'] = esc_url_raw( trim( $input['ollama_endpoint'] ?? 'http://localhost:11434/v1' ) );
		$sanitized['ollama_model']    = sanitize_text_field( trim( $input['ollama_model'] ?? 'llama3.3' ) );
		$sanitized['custom_endpoint'] = esc_url_raw( trim( $input['custom_endpoint'] ?? '' ) );
		$sanitized['custom_api_key']  = sanitize_text_field( trim( $input['custom_api_key'] ?? '' ) );
		$sanitized['custom_model']    = sanitize_text_field( trim( $input['custom_model'] ?? '' ) );

		// MCP & General
		$sanitized['mcp_enabled']              = ! empty( $input['mcp_enabled'] ) ? '1' : '0';
		$sanitized['auto_confirm_destructive'] = ! empty( $input['auto_confirm_destructive'] ) ? '1' : '0';
		$sanitized['max_agent_turns']          = max( 1, min( 20, (int) ( $input['max_agent_turns'] ?? 8 ) ) );
		$sanitized['system_prompt_custom']     = sanitize_textarea_field( $input['system_prompt_custom'] ?? '' );

		return $sanitized;
	}
}
