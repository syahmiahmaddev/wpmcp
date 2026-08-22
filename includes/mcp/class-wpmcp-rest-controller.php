<?php
/**
 * WP-MCP REST API Controller
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers REST API endpoints for Remote MCP protocol and In-Admin Copilot.
 */
class WPMCP_REST_Controller {

	public const REST_NAMESPACE = 'wpmcp/v1';

	private WPMCP_MCP_Server $mcp_server;
	private WPMCP_Security $security;
	private WPMCP_Agent_Orchestrator $orchestrator;

	public function __construct(
		WPMCP_MCP_Server $mcp_server,
		WPMCP_Security $security,
		WPMCP_Agent_Orchestrator $orchestrator
	) {
		$this->mcp_server   = $mcp_server;
		$this->security     = $security;
		$this->orchestrator = $orchestrator;
	}

	/**
	 * Register all REST API routes under /wp-json/wpmcp/v1/
	 */
	public function register_routes(): void {
		// 1. MCP SSE Stream Endpoint
		register_rest_route(
			self::REST_NAMESPACE,
			'/sse',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_sse_stream' ),
				'permission_callback' => array( $this, 'check_mcp_permission' ),
			)
		);

		// 2. MCP JSON-RPC Message Endpoint
		register_rest_route(
			self::REST_NAMESPACE,
			'/messages',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_mcp_message' ),
				'permission_callback' => array( $this, 'check_mcp_permission' ),
			)
		);

		// 3. In-Admin AI Chat Endpoint
		register_rest_route(
			self::REST_NAMESPACE,
			'/chat',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_admin_chat' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		// 4. Test AI Connection
		register_rest_route(
			self::REST_NAMESPACE,
			'/test-connection',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_test_connection' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		// 5. Rollback Endpoint
		register_rest_route(
			self::REST_NAMESPACE,
			'/rollback',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_rollback' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		// 6. Token Management Endpoint
		register_rest_route(
			self::REST_NAMESPACE,
			'/tokens',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_list_tokens' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_create_token' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'handle_revoke_token' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);
	}

	/**
	 * Permission callback for MCP routes.
	 */
	public function check_mcp_permission( WP_REST_Request $request ): true|WP_Error {
		$auth = $this->security->authenticate_rest_request( $request );
		if ( is_wp_error( $auth ) ) {
			return $auth;
		}
		return true;
	}

	/**
	 * Permission callback for In-Admin routes.
	 */
	public function check_admin_permission( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
	}

	/**
	 * Handle MCP Server-Sent Events stream.
	 */
	public function handle_sse_stream( WP_REST_Request $request ) {
		// Set headers for SSE
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' );

		// Flush buffers
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
		flush();

		$endpoint_url = rest_url( self::REST_NAMESPACE . '/messages' );

		// Send initial endpoint event
		echo "event: endpoint\n";
		echo 'data: ' . $endpoint_url . "\n\n";
		flush();

		// Keep connection alive for 30s
		$start = time();
		while ( time() - $start < 30 ) {
			if ( connection_aborted() ) {
				break;
			}
			echo ": keep-alive\n\n";
			flush();
			sleep( 5 );
		}
		exit;
	}

	/**
	 * Handle JSON-RPC message over POST.
	 */
	public function handle_mcp_message( WP_REST_Request $request ): WP_REST_Response {
		$auth = $this->security->authenticate_rest_request( $request );
		$user_id = is_a( $auth, 'WP_User' ) ? $auth->ID : get_current_user_id();

		$payload = $request->get_json_params();
		if ( empty( $payload ) || ! is_array( $payload ) ) {
			return new WP_REST_Response(
				array(
					'jsonrpc' => '2.0',
					'id'      => null,
					'error'   => array( 'code' => -32700, 'message' => 'Parse error: invalid JSON' ),
				),
				400
			);
		}

		$response = $this->mcp_server->handle_rpc_message( $payload, $user_id );
		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Handle In-Admin AI Chat prompts.
	 */
	public function handle_admin_chat( WP_REST_Request $request ): WP_REST_Response {
		$prompt           = sanitize_textarea_field( $request->get_param( 'prompt' ) ?? '' );
		$history          = (array) ( $request->get_param( 'history' ) ?? array() );
		$confirmed_action = $request->get_param( 'confirmed_action' );
		$user_id          = get_current_user_id();

		if ( empty( $prompt ) && empty( $confirmed_action ) ) {
			return new WP_REST_Response(
				array( 'success' => false, 'error' => __( 'Prompt cannot be empty.', 'wpmcp' ) ),
				400
			);
		}

		$result = $this->orchestrator->handle_prompt(
			$prompt,
			$history,
			$user_id,
			is_array( $confirmed_action ) ? $confirmed_action : null
		);

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Handle Test Connection to AI Provider.
	 */
	public function handle_test_connection( WP_REST_Request $request ): WP_REST_Response {
		$provider = sanitize_text_field( $request->get_param( 'provider' ) ?? 'openai' );
		$api_key  = sanitize_text_field( $request->get_param( 'api_key' ) ?? '' );
		$model    = sanitize_text_field( $request->get_param( 'model' ) ?? '' );
		$endpoint = esc_url_raw( $request->get_param( 'endpoint' ) ?? '' );

		$config = array(
			'provider' => $provider,
			'api_key'  => $api_key,
			'model'    => $model,
			'endpoint' => $endpoint,
		);

		$ai_client = new WPMCP_AI_Client();
		$messages  = array(
			array( 'role' => 'user', 'content' => 'Respond with the word "connected".' ),
		);

		$res = $ai_client->send_chat( $messages, array(), $config );

		if ( ! empty( $res['success'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => sprintf( __( 'Successfully connected to %s (%s)!', 'wpmcp' ), ucfirst( $provider ), $model ),
					'reply'   => $res['content'],
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'success' => false,
				'error'   => $res['error'] ?? __( 'Connection failed. Please check your API key and model.', 'wpmcp' ),
			),
			400
		);
	}

	/**
	 * Handle Rollback request.
	 */
	public function handle_rollback( WP_REST_Request $request ): WP_REST_Response {
		$log_id  = (int) $request->get_param( 'log_id' );
		$user_id = get_current_user_id();

		if ( ! $log_id ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => __( 'Missing log_id.', 'wpmcp' ) ), 400 );
		}

		$rollback_mgr = new WPMCP_Rollback_Manager( new WPMCP_Audit_Logger() );
		$res          = $rollback_mgr->rollback( $log_id, $user_id );

		return new WP_REST_Response( $res, $res['success'] ? 200 : 400 );
	}

	/**
	 * List MCP API Tokens.
	 */
	public function handle_list_tokens( WP_REST_Request $request ): WP_REST_Response {
		$tokens = $this->security->list_mcp_tokens( get_current_user_id() );
		return new WP_REST_Response( array( 'success' => true, 'tokens' => $tokens ), 200 );
	}

	/**
	 * Create MCP API Token.
	 */
	public function handle_create_token( WP_REST_Request $request ): WP_REST_Response {
		$name        = sanitize_text_field( $request->get_param( 'name' ) ?? 'MCP Client' );
		$permissions = sanitize_key( $request->get_param( 'permissions' ) ?? 'read_write' );

		$token_data = $this->security->create_mcp_token( get_current_user_id(), $name, $permissions );
		return new WP_REST_Response( array( 'success' => true, 'token_data' => $token_data ), 201 );
	}

	/**
	 * Revoke MCP API Token.
	 */
	public function handle_revoke_token( WP_REST_Request $request ): WP_REST_Response {
		$token_id = (int) $request->get_param( 'id' );
		if ( ! $token_id ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => __( 'Missing token ID.', 'wpmcp' ) ), 400 );
		}

		$success = $this->security->revoke_mcp_token( $token_id, get_current_user_id() );
		return new WP_REST_Response( array( 'success' => $success ), 200 );
	}
}
