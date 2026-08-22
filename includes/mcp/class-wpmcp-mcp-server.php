<?php
/**
 * WP-MCP Model Context Protocol (MCP) Server
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles JSON-RPC 2.0 requests conforming to the Model Context Protocol (MCP).
 */
class WPMCP_MCP_Server {

	public const PROTOCOL_VERSION = '2024-11-05';

	private WPMCP_Tool_Registry $tool_registry;
	private WPMCP_Settings $settings;

	public function __construct( WPMCP_Tool_Registry $tool_registry, WPMCP_Settings $settings ) {
		$this->tool_registry = $tool_registry;
		$this->settings      = $settings;
	}

	/**
	 * Process an incoming MCP JSON-RPC payload.
	 *
	 * @param array<string, mixed> $request_json Decoded JSON-RPC request.
	 * @param int                  $user_id      Authenticated user ID.
	 * @return array<string, mixed> JSON-RPC response.
	 */
	public function handle_rpc_message( array $request_json, int $user_id ): array {
		$id     = $request_json['id'] ?? null;
		$method = (string) ( $request_json['method'] ?? '' );
		$params = (array) ( $request_json['params'] ?? array() );

		switch ( $method ) {
			case 'initialize':
				return $this->rpc_response( $id, array(
					'protocolVersion' => self::PROTOCOL_VERSION,
					'serverInfo'      => array(
						'name'    => 'WP-MCP',
						'version' => WPMCP_VERSION,
					),
					'capabilities'    => array(
						'tools'     => array( 'listChanged' => false ),
						'resources' => array( 'subscribe' => false, 'listChanged' => false ),
						'prompts'   => array( 'listChanged' => false ),
					),
				) );

			case 'notifications/initialized':
				return $this->rpc_response( $id, array( 'status' => 'initialized' ) );

			case 'tools/list':
				$tools = $this->tool_registry->to_mcp_format( $user_id );
				return $this->rpc_response( $id, array( 'tools' => $tools ) );

			case 'tools/call':
				$tool_name = (string) ( $params['name'] ?? '' );
				$arguments = (array) ( $params['arguments'] ?? array() );

				$result = $this->tool_registry->execute_tool(
					$tool_name,
					$arguments,
					$user_id,
					'MCP Remote Client: ' . $tool_name
				);

				$is_error = empty( $result['success'] );
				$content  = array(
					array(
						'type' => 'text',
						'text' => wp_json_encode( $result, JSON_PRETTY_PRINT ),
					),
				);

				return $this->rpc_response( $id, array(
					'content' => $content,
					'isError' => $is_error,
				) );

			case 'resources/list':
				return $this->rpc_response( $id, array(
					'resources' => $this->get_available_resources(),
				) );

			case 'resources/read':
				$uri = (string) ( $params['uri'] ?? '' );
				return $this->handle_resource_read( $id, $uri );

			case 'prompts/list':
				return $this->rpc_response( $id, array(
					'prompts' => $this->get_prompt_templates(),
				) );

			case 'prompts/get':
				$prompt_name = (string) ( $params['name'] ?? '' );
				return $this->handle_prompt_get( $id, $prompt_name, $params['arguments'] ?? array() );

			case 'ping':
				return $this->rpc_response( $id, array( 'status' => 'pong' ) );

			default:
				return $this->rpc_error( $id, -32601, sprintf( 'Method "%s" not found', $method ) );
		}
	}

	/**
	 * Returns available MCP Resources exposed by WordPress.
	 */
	private function get_available_resources(): array {
		return array(
			array(
				'uri'         => 'wp://site/info',
				'name'        => 'WordPress Site Information',
				'description' => 'Current site title, URL, active theme, and WordPress environment.',
				'mimeType'    => 'application/json',
			),
			array(
				'uri'         => 'wp://plugins/active',
				'name'        => 'Active WordPress Plugins',
				'description' => 'List of currently active plugins on this site.',
				'mimeType'    => 'application/json',
			),
			array(
				'uri'         => 'wp://customizer/css',
				'name'        => 'Theme Custom CSS',
				'description' => 'Current custom CSS stylesheet in theme customizer.',
				'mimeType'    => 'text/css',
			),
		);
	}

	/**
	 * Handle resources/read requests.
	 */
	private function handle_resource_read( mixed $id, string $uri ): array {
		switch ( $uri ) {
			case 'wp://site/info':
				$theme = wp_get_theme();
				$data  = array(
					'name'        => get_bloginfo( 'name' ),
					'description' => get_bloginfo( 'description' ),
					'url'         => site_url(),
					'theme'       => $theme->get( 'Name' ),
					'version'     => get_bloginfo( 'version' ),
				);
				return $this->rpc_response( $id, array(
					'contents' => array(
						array(
							'uri'      => $uri,
							'mimeType' => 'application/json',
							'text'     => wp_json_encode( $data, JSON_PRETTY_PRINT ),
						),
					),
				) );

			case 'wp://plugins/active':
				$active = (array) get_option( 'active_plugins', array() );
				return $this->rpc_response( $id, array(
					'contents' => array(
						array(
							'uri'      => $uri,
							'mimeType' => 'application/json',
							'text'     => wp_json_encode( $active, JSON_PRETTY_PRINT ),
						),
					),
				) );

			case 'wp://customizer/css':
				$css = function_exists( 'wp_get_custom_css' ) ? wp_get_custom_css() : '';
				return $this->rpc_response( $id, array(
					'contents' => array(
						array(
							'uri'      => $uri,
							'mimeType' => 'text/css',
							'text'     => $css,
						),
					),
				) );

			default:
				return $this->rpc_error( $id, -32602, sprintf( 'Resource URI "%s" not found', $uri ) );
		}
	}

	/**
	 * Pre-built prompt templates for MCP clients.
	 */
	private function get_prompt_templates(): array {
		return array(
			array(
				'name'        => 'draft_post',
				'description' => 'Draft a compelling blog post and insert it into WordPress with proper tags and categories.',
				'arguments'   => array(
					array( 'name' => 'topic', 'description' => 'The topic or title of the blog post', 'required' => true ),
					array( 'name' => 'tone', 'description' => 'Tone of voice (e.g. professional, playful)', 'required' => false ),
				),
			),
			array(
				'name'        => 'site_health_audit',
				'description' => 'Perform a comprehensive site health check and summarize recommendations.',
				'arguments'   => array(),
			),
		);
	}

	/**
	 * Handle prompts/get requests.
	 */
	private function handle_prompt_get( mixed $id, string $prompt_name, array $args ): array {
		if ( 'draft_post' === $prompt_name ) {
			$topic = $args['topic'] ?? 'New Article';
			$tone  = $args['tone'] ?? 'professional and engaging';
			return $this->rpc_response( $id, array(
				'description' => 'Draft a blog post',
				'messages'    => array(
					array(
						'role'    => 'user',
						'content' => array(
							'type' => 'text',
							'text' => "Please write a comprehensive, {$tone} blog post about '{$topic}' and use wpmcp_create_post to save it as a draft.",
						),
					),
				),
			) );
		}

		if ( 'site_health_audit' === $prompt_name ) {
			return $this->rpc_response( $id, array(
				'description' => 'Site health audit',
				'messages'    => array(
					array(
						'role'    => 'user',
						'content' => array(
							'type' => 'text',
							'text' => "Please use wpmcp_get_site_health and wpmcp_get_site_info to audit this WordPress site's configuration and provide actionable optimization advice.",
						),
					),
				),
			) );
		}

		return $this->rpc_error( $id, -32602, sprintf( 'Prompt template "%s" not found', $prompt_name ) );
	}

	/**
	 * Standard JSON-RPC 2.0 Success Response.
	 */
	private function rpc_response( mixed $id, array $result ): array {
		return array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'result'  => $result,
		);
	}

	/**
	 * Standard JSON-RPC 2.0 Error Response.
	 */
	private function rpc_error( mixed $id, int $code, string $message, mixed $data = null ): array {
		$error = array(
			'code'    => $code,
			'message' => $message,
		);
		if ( null !== $data ) {
			$error['data'] = $data;
		}

		return array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'error'   => $error,
		);
	}
}
