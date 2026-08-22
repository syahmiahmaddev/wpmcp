<?php
/**
 * WP-MCP Tool Registry
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Central registry for discovering, registering, formatting, and executing tools.
 */
class WPMCP_Tool_Registry {

	/**
	 * Map of registered tools.
	 *
	 * @var array<string, WPMCP_Tool_Interface>
	 */
	private array $tools = array();

	private WPMCP_Security $security;
	private WPMCP_Audit_Logger $audit_logger;
	private WPMCP_Rollback_Manager $rollback_manager;

	public function __construct(
		WPMCP_Security $security,
		WPMCP_Audit_Logger $audit_logger,
		WPMCP_Rollback_Manager $rollback_manager
	) {
		$this->security         = $security;
		$this->audit_logger     = $audit_logger;
		$this->rollback_manager = $rollback_manager;
	}

	/**
	 * Register a tool instance.
	 *
	 * @param WPMCP_Tool_Interface $tool Tool instance.
	 */
	public function register_tool( WPMCP_Tool_Interface $tool ): void {
		$this->tools[ $tool->get_name() ] = $tool;
	}

	/**
	 * Unregister a tool by name.
	 *
	 * @param string $name Tool identifier.
	 */
	public function unregister_tool( string $name ): void {
		unset( $this->tools[ $name ] );
	}

	/**
	 * Get a registered tool by name.
	 *
	 * @param string $name Tool identifier.
	 * @return WPMCP_Tool_Interface|null
	 */
	public function get_tool( string $name ): ?WPMCP_Tool_Interface {
		return $this->tools[ $name ] ?? null;
	}

	/**
	 * Get all registered tools.
	 *
	 * @return array<string, WPMCP_Tool_Interface>
	 */
	public function get_all_tools(): array {
		return $this->tools;
	}

	/**
	 * Register all built-in core WordPress tools.
	 */
	public function register_core_tools(): void {
		// Content Tools
		WPMCP_Content_Tools::register( $this );

		// Site & Customizer Tools
		WPMCP_Site_Tools::register( $this );

		// System & Plugins Tools
		WPMCP_System_Tools::register( $this );

		// Gutenberg Block Tools
		WPMCP_Block_Tools::register( $this );

		// WooCommerce (if active)
		if ( class_exists( 'WooCommerce' ) ) {
			WPMCP_WooCommerce_Tools::register( $this );
		}

		/**
		 * Action hook allowing third-party plugins to register custom tools.
		 *
		 * @param WPMCP_Tool_Registry $this Registry instance.
		 */
		do_action( 'wpmcp_register_tools', $this );
	}

	/**
	 * Execute a tool safely with permissions check, audit logging, and error handling.
	 *
	 * @param string               $tool_name Tool name.
	 * @param array<string, mixed> $params    Input arguments.
	 * @param int                  $user_id   User ID executing the action.
	 * @param string               $prompt    Optional originating prompt.
	 * @return array<string, mixed>
	 */
	public function execute_tool( string $tool_name, array $params, int $user_id = 0, string $prompt = '' ): array {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$tool = $this->get_tool( $tool_name );
		if ( ! $tool ) {
			return array(
				'success' => false,
				'error'   => sprintf( __( 'Tool "%s" not found in registry.', 'wpmcp' ), $tool_name ),
			);
		}

		// Check capability.
		if ( ! $this->security->check_tool_permission( $tool, $user_id ) ) {
			return array(
				'success' => false,
				'error'   => sprintf(
					__( 'Permission denied: User does not have capability "%s" for tool "%s".', 'wpmcp' ),
					$tool->get_required_capability(),
					$tool_name
				),
			);
		}

		// Take pre-execution snapshot for reversible changes.
		$snapshot_before = $this->rollback_manager->capture_pre_state( $tool_name, $params );

		try {
			$result = $tool->execute( $params, $user_id );
		} catch ( Throwable $e ) {
			$result = array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}

		// Log to Audit Logger.
		$this->audit_logger->log_action(
			user_id: $user_id,
			tool_name: $tool_name,
			risk_level: $tool->get_risk_level(),
			prompt: $prompt,
			arguments: $params,
			result: $result,
			snapshot_before: $snapshot_before,
			status: ! empty( $result['success'] ) ? 'success' : 'error'
		);

		return $result;
	}

	/**
	 * Convert registered tools to MCP JSON-RPC format (`tools/list`).
	 *
	 * @param int $user_id User ID to filter capabilities for.
	 * @return array<int, array{name: string, description: string, inputSchema: array<string, mixed>}>
	 */
	public function to_mcp_format( int $user_id = 0 ): array {
		$output = array();
		foreach ( $this->tools as $tool ) {
			if ( $user_id > 0 && ! $this->security->check_tool_permission( $tool, $user_id ) ) {
				continue;
			}

			$output[] = array(
				'name'        => $tool->get_name(),
				'description' => $tool->get_description(),
				'inputSchema' => $tool->get_parameters_schema(),
			);
		}
		return $output;
	}

	/**
	 * Convert registered tools to OpenAI Function Calling format.
	 *
	 * @param int $user_id User ID to filter capabilities for.
	 * @return array<int, array{type: string, function: array{name: string, description: string, parameters: array<string, mixed>}}>
	 */
	public function to_openai_format( int $user_id = 0 ): array {
		$output = array();
		foreach ( $this->tools as $tool ) {
			if ( $user_id > 0 && ! $this->security->check_tool_permission( $tool, $user_id ) ) {
				continue;
			}

			$output[] = array(
				'type'     => 'function',
				'function' => array(
					'name'        => $tool->get_name(),
					'description' => $tool->get_description(),
					'parameters'  => $tool->get_parameters_schema(),
				),
			);
		}
		return $output;
	}

	/**
	 * Convert registered tools to Anthropic Claude Tools format.
	 *
	 * @param int $user_id User ID to filter capabilities for.
	 * @return array<int, array{name: string, description: string, input_schema: array<string, mixed>}>
	 */
	public function to_anthropic_format( int $user_id = 0 ): array {
		$output = array();
		foreach ( $this->tools as $tool ) {
			if ( $user_id > 0 && ! $this->security->check_tool_permission( $tool, $user_id ) ) {
				continue;
			}

			$output[] = array(
				'name'         => $tool->get_name(),
				'description'  => $tool->get_description(),
				'input_schema' => $tool->get_parameters_schema(),
			);
		}
		return $output;
	}

	/**
	 * Convert registered tools to Google Gemini format.
	 *
	 * @param int $user_id User ID to filter capabilities for.
	 * @return array{functionDeclarations: array<int, array{name: string, description: string, parameters: array<string, mixed>}>}
	 */
	public function to_gemini_format( int $user_id = 0 ): array {
		$declarations = array();
		foreach ( $this->tools as $tool ) {
			if ( $user_id > 0 && ! $this->security->check_tool_permission( $tool, $user_id ) ) {
				continue;
			}

			$declarations[] = array(
				'name'        => $tool->get_name(),
				'description' => $tool->get_description(),
				'parameters'  => $tool->get_parameters_schema(),
			);
		}
		return array( 'functionDeclarations' => $declarations );
	}
}
