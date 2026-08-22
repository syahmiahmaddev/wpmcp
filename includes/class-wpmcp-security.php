<?php
/**
 * WP-MCP Security and Authentication Class
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles permissions, capability checks, token verification, and input security.
 */
class WPMCP_Security {

	private WPMCP_Settings $settings;

	public function __construct( WPMCP_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Verify admin AJAX nonce and capabilities.
	 *
	 * @param string $action Nonce action name.
	 * @return bool True if valid, false otherwise.
	 */
	public function verify_admin_request( string $action = 'wpmcp_admin_nonce' ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_ajax_nonce'] ?? $_SERVER['HTTP_X_WP_NONCE'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			return false;
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
	}

	/**
	 * Check if current user has permission to execute a specific tool.
	 *
	 * @param WPMCP_Tool_Interface $tool Tool instance.
	 * @param int                  $user_id Optional user ID (defaults to current user).
	 * @return bool
	 */
	public function check_tool_permission( WPMCP_Tool_Interface $tool, int $user_id = 0 ): bool {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$required_cap = $tool->get_required_capability();

		/**
		 * Filter the required capability for a given tool.
		 */
		$required_cap = apply_filters( 'wpmcp_tool_required_capability', $required_cap, $tool->get_name(), $user_id );

		return user_can( $user_id, $required_cap );
	}

	/**
	 * Authenticate incoming MCP REST request.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_User|WP_Error Authenticated WP_User or WP_Error.
	 */
	public function authenticate_rest_request( WP_REST_Request $request ) {
		// 1. Check logged in cookie session (e.g. from WP Admin)
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( user_can( $user, 'manage_options' ) || user_can( $user, 'edit_posts' ) ) {
				return $user;
			}
		}

		// 2. Check Authorization Header (Bearer token or Basic App Password)
		$auth_header = $request->get_header( 'Authorization' );
		if ( empty( $auth_header ) ) {
			return new WP_Error(
				'wpmcp_unauthorized',
				__( 'Missing Authorization header. Please provide an MCP API Token or Application Password.', 'wpmcp' ),
				array( 'status' => 401 )
			);
		}

		// Bearer Token Check
		if ( preg_match( '/Bearer\s+(wpmcp_[a-zA-Z0-9]+)/i', $auth_header, $matches ) ) {
			$token = $matches[1];
			$user  = $this->validate_mcp_token( $token );
			if ( is_wp_error( $user ) ) {
				return $user;
			}
			return $user;
		}

		// Basic Auth (Application Password)
		if ( preg_match( '/Basic\s+(.*)$/i', $auth_header, $matches ) ) {
			$credentials = base64_decode( $matches[1] );
			if ( strpos( $credentials, ':' ) !== false ) {
				list( $username, $app_password ) = explode( ':', $credentials, 2 );
				$user = wp_authenticate_application_password( null, $username, $app_password );
				if ( is_a( $user, 'WP_User' ) ) {
					return $user;
				}
			}
		}

		return new WP_Error(
			'wpmcp_invalid_credentials',
			__( 'Invalid MCP token or application password.', 'wpmcp' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Validate a custom MCP API Token against the database.
	 *
	 * @param string $token Raw token string.
	 * @return WP_User|WP_Error
	 */
	public function validate_mcp_token( string $token ) {
		global $wpdb;

		$token_hash = hash( 'sha256', $token );
		$table      = $wpdb->prefix . 'wpmcp_api_tokens';

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE token_hash = %s LIMIT 1", $token_hash )
		);

		if ( ! $row ) {
			return new WP_Error( 'wpmcp_invalid_token', __( 'Invalid or revoked MCP API token.', 'wpmcp' ), array( 'status' => 401 ) );
		}

		$user = get_user_by( 'id', (int) $row->user_id );
		if ( ! $user ) {
			return new WP_Error( 'wpmcp_user_not_found', __( 'User associated with token no longer exists.', 'wpmcp' ), array( 'status' => 401 ) );
		}

		// Update last used timestamp.
		$wpdb->update(
			$table,
			array( 'last_used_at' => current_time( 'mysql', 1 ) ),
			array( 'id' => $row->id ),
			array( '%s' ),
			array( '%d' )
		);

		return $user;
	}

	/**
	 * Generate a new MCP API token for a user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $name Name/Label for the token.
	 * @param string $permissions Permission level ('read_only', 'read_write', 'full_admin').
	 * @return array{token: string, name: string, id: int}
	 */
	public function create_mcp_token( int $user_id, string $name, string $permissions = 'read_write' ): array {
		global $wpdb;

		$raw_token  = 'wpmcp_' . wp_generate_password( 40, false );
		$token_hash = hash( 'sha256', $raw_token );
		$table      = $wpdb->prefix . 'wpmcp_api_tokens';

		$wpdb->insert(
			$table,
			array(
				'user_id'     => $user_id,
				'token_hash'  => $token_hash,
				'name'        => sanitize_text_field( $name ),
				'permissions' => sanitize_text_field( $permissions ),
				'created_at'  => current_time( 'mysql', 1 ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		return array(
			'id'          => $wpdb->insert_id,
			'token'       => $raw_token,
			'name'        => $name,
			'permissions' => $permissions,
		);
	}

	/**
	 * Revoke an MCP API Token by ID.
	 *
	 * @param int $token_id Token ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function revoke_mcp_token( int $token_id, int $user_id = 0 ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wpmcp_api_tokens';

		$where = array( 'id' => $token_id );
		if ( $user_id > 0 ) {
			$where['user_id'] = $user_id;
		}

		return (bool) $wpdb->delete( $table, $where );
	}

	/**
	 * List all active MCP tokens for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, object>
	 */
	public function list_mcp_tokens( int $user_id = 0 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'wpmcp_api_tokens';

		if ( $user_id > 0 ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, name, permissions, last_used_at, created_at FROM $table WHERE user_id = %d ORDER BY created_at DESC", $user_id ) );
		}

		return $wpdb->get_results( "SELECT id, user_id, name, permissions, last_used_at, created_at FROM $table ORDER BY created_at DESC" );
	}
}
