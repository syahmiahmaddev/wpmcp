<?php
/**
 * WP-MCP Rollback Manager
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles capturing snapshots before tool execution and reverting state changes.
 */
class WPMCP_Rollback_Manager {

	private WPMCP_Audit_Logger $audit_logger;

	public function __construct( WPMCP_Audit_Logger $audit_logger ) {
		$this->audit_logger = $audit_logger;
	}

	/**
	 * Capture state of affected entities before a tool runs.
	 *
	 * @param string               $tool_name Tool identifier.
	 * @param array<string, mixed> $params    Tool arguments.
	 * @return array<string, mixed>|null Pre-execution snapshot or null if not applicable.
	 */
	public function capture_pre_state( string $tool_name, array $params ): ?array {
		switch ( $tool_name ) {
			case 'wpmcp_update_post':
			case 'wpmcp_delete_post':
				$post_id = (int) ( $params['post_id'] ?? 0 );
				if ( ! $post_id ) {
					return null;
				}
				$post = get_post( $post_id );
				if ( ! $post ) {
					return null;
				}
				return array(
					'entity'       => 'post',
					'post_id'      => $post->ID,
					'post_title'   => $post->post_title,
					'post_content' => $post->post_content,
					'post_excerpt' => $post->post_excerpt,
					'post_status'  => $post->post_status,
					'categories'   => wp_get_post_categories( $post->ID, array( 'fields' => 'ids' ) ),
					'tags'         => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ),
				);

			case 'wpmcp_update_site_option':
				$opt_name = sanitize_key( $params['option_name'] ?? '' );
				if ( ! $opt_name ) {
					return null;
				}
				return array(
					'entity'      => 'option',
					'option_name' => $opt_name,
					'value'       => get_option( $opt_name ),
				);

			case 'wpmcp_update_custom_css':
				return array(
					'entity'     => 'custom_css',
					'custom_css' => function_exists( 'wp_get_custom_css' ) ? wp_get_custom_css() : '',
				);

			default:
				return null;
		}
	}

	/**
	 * Rollback an action using its audit log ID.
	 *
	 * @param int $log_id  Log ID.
	 * @param int $user_id User requesting the rollback.
	 * @return array<string, mixed>
	 */
	public function rollback( int $log_id, int $user_id = 0 ): array {
		$log = $this->audit_logger->get_log( $log_id );
		if ( ! $log ) {
			return array(
				'success' => false,
				'error'   => __( 'Audit log not found.', 'wpmcp' ),
			);
		}

		if ( 'rolled_back' === $log->status ) {
			return array(
				'success' => false,
				'error'   => __( 'This action has already been rolled back.', 'wpmcp' ),
			);
		}

		if ( empty( $log->snapshot_before ) ) {
			return array(
				'success' => false,
				'error'   => __( 'No rollback snapshot available for this action.', 'wpmcp' ),
			);
		}

		$snapshot = json_decode( $log->snapshot_before, true );
		if ( ! is_array( $snapshot ) || empty( $snapshot['entity'] ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Invalid snapshot data.', 'wpmcp' ),
			);
		}

		$entity = $snapshot['entity'];

		switch ( $entity ) {
			case 'post':
				$post_id  = (int) $snapshot['post_id'];
				$post_arr = array(
					'ID'           => $post_id,
					'post_title'   => $snapshot['post_title'],
					'post_content' => $snapshot['post_content'],
					'post_excerpt' => $snapshot['post_excerpt'],
					'post_status'  => $snapshot['post_status'],
				);

				$updated = wp_update_post( $post_arr, true );
				if ( is_wp_error( $updated ) ) {
					return array( 'success' => false, 'error' => $updated->get_error_message() );
				}

				if ( ! empty( $snapshot['categories'] ) ) {
					wp_set_post_categories( $post_id, $snapshot['categories'] );
				}
				if ( ! empty( $snapshot['tags'] ) ) {
					wp_set_post_tags( $post_id, $snapshot['tags'] );
				}

				$this->audit_logger->mark_rolled_back( $log_id );
				return array(
					'success' => true,
					'message' => sprintf( __( 'Post ID %d restored to previous state.', 'wpmcp' ), $post_id ),
				);

			case 'option':
				$opt_name = sanitize_key( $snapshot['option_name'] );
				update_option( $opt_name, $snapshot['value'] );
				$this->audit_logger->mark_rolled_back( $log_id );
				return array(
					'success' => true,
					'message' => sprintf( __( 'Option "%s" restored to "%s".', 'wpmcp' ), $opt_name, (string) $snapshot['value'] ),
				);

			case 'custom_css':
				if ( function_exists( 'wp_update_custom_css_post' ) ) {
					wp_update_custom_css_post( (string) ( $snapshot['custom_css'] ?? '' ) );
					$this->audit_logger->mark_rolled_back( $log_id );
					return array(
						'success' => true,
						'message' => __( 'Custom CSS restored to previous version.', 'wpmcp' ),
					);
				}
				break;
		}

		return array(
			'success' => false,
			'error'   => __( 'Unable to process rollback for this entity type.', 'wpmcp' ),
		);
	}
}
