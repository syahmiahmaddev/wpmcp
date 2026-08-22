<?php
/**
 * WP-MCP Audit Logger
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Records and retrieves all AI prompts, executed tools, arguments, snapshots, and outcomes.
 */
class WPMCP_Audit_Logger {

	/**
	 * Log a tool execution event into the database.
	 *
	 * @param int                  $user_id         User ID.
	 * @param string               $tool_name       Tool name.
	 * @param string               $risk_level      Risk level ('read', 'write', 'destructive').
	 * @param string               $prompt          Originating user prompt.
	 * @param array<string, mixed> $arguments       Tool parameters.
	 * @param array<string, mixed> $result          Tool result payload.
	 * @param array<string, mixed>|null $snapshot_before Reversible state snapshot before execution.
	 * @param string               $status          Outcome ('success', 'error', 'rolled_back').
	 * @return int Inserted log ID.
	 */
	public function log_action(
		int $user_id,
		string $tool_name,
		string $risk_level = 'read',
		string $prompt = '',
		array $arguments = array(),
		array $result = array(),
		?array $snapshot_before = null,
		string $status = 'success'
	): int {
		global $wpdb;

		$table = $wpdb->prefix . 'wpmcp_audit_logs';

		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'         => $user_id,
				'tool_name'       => sanitize_text_field( $tool_name ),
				'risk_level'      => sanitize_key( $risk_level ),
				'prompt'          => sanitize_textarea_field( $prompt ),
				'arguments'       => wp_json_encode( $arguments ),
				'result'          => wp_json_encode( $result ),
				'snapshot_before' => $snapshot_before ? wp_json_encode( $snapshot_before ) : null,
				'status'          => sanitize_key( $status ),
				'created_at'      => current_time( 'mysql', 1 ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Retrieve paginated audit logs.
	 *
	 * @param int         $limit     Number of logs to retrieve.
	 * @param int         $offset    Offset for pagination.
	 * @param string|null $tool_name Optional tool name filter.
	 * @param int|null    $user_id   Optional user ID filter.
	 * @return array{total: int, logs: array<int, object>}
	 */
	public function get_logs( int $limit = 20, int $offset = 0, ?string $tool_name = null, ?int $user_id = null ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'wpmcp_audit_logs';
		$where = array( '1=1' );
		$args  = array();

		if ( ! empty( $tool_name ) ) {
			$where[] = 'tool_name = %s';
			$args[]  = $tool_name;
		}

		if ( ! empty( $user_id ) && $user_id > 0 ) {
			$where[] = 'user_id = %d';
			$args[]  = $user_id;
		}

		$where_clause = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM $table WHERE $where_clause";
		$total     = (int) ( ! empty( $args ) ? $wpdb->get_var( $wpdb->prepare( $count_sql, $args ) ) : $wpdb->get_var( $count_sql ) );

		$query_sql = "SELECT * FROM $table WHERE $where_clause ORDER BY id DESC LIMIT %d OFFSET %d";
		$query_args = array_merge( $args, array( $limit, $offset ) );

		$logs = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_args ) );

		return array(
			'total' => $total,
			'logs'  => is_array( $logs ) ? $logs : array(),
		);
	}

	/**
	 * Retrieve a single log by ID.
	 *
	 * @param int $log_id Log ID.
	 * @return object|null
	 */
	public function get_log( int $log_id ): ?object {
		global $wpdb;
		$table = $wpdb->prefix . 'wpmcp_audit_logs';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d LIMIT 1", $log_id ) );
	}

	/**
	 * Mark a log entry as rolled back.
	 *
	 * @param int $log_id Log ID.
	 * @return bool
	 */
	public function mark_rolled_back( int $log_id ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wpmcp_audit_logs';
		return (bool) $wpdb->update(
			$table,
			array( 'status' => 'rolled_back' ),
			array( 'id' => $log_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}
