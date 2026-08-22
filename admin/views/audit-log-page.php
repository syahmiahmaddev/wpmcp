<?php
/**
 * WP-MCP Audit Log View
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

$logger = $this->audit_logger;
$page   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$limit  = 20;
$offset = ( $page - 1 ) * $limit;

$data  = $logger->get_logs( $limit, $offset );
$logs  = $data['logs'];
$total = $data['total'];
$pages = ceil( $total / $limit );
?>

<div class="wrap wpmcp-admin-wrap">
	<h1><?php esc_html_e( 'WP-MCP Audit Log & Action History', 'wpmcp' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Complete audit trail of all AI prompts and tool executions on your WordPress site with 1-click rollback capability.', 'wpmcp' ); ?></p>

	<div class="wpmcp-card" style="padding:0; overflow:hidden;">
		<table class="wp-list-table widefat fixed striped table-view-list">
			<thead>
				<tr>
					<th style="width: 50px;"><?php esc_html_e( 'ID', 'wpmcp' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Tool', 'wpmcp' ); ?></th>
					<th style="width: 90px;"><?php esc_html_e( 'Risk', 'wpmcp' ); ?></th>
					<th style="width: 90px;"><?php esc_html_e( 'Status', 'wpmcp' ); ?></th>
					<th><?php esc_html_e( 'Prompt / Action Details', 'wpmcp' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'Date (UTC)', 'wpmcp' ); ?></th>
					<th style="width: 100px;"><?php esc_html_e( 'Rollback', 'wpmcp' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $logs ) ) : ?>
					<tr>
						<td colspan="7" style="text-align: center; padding: 20px;">
							<?php esc_html_e( 'No tool actions recorded yet.', 'wpmcp' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $logs as $log ) : ?>
						<?php
						$has_snapshot = ! empty( $log->snapshot_before );
						$is_rolled_back = ( 'rolled_back' === $log->status );
						$risk_class   = match ( $log->risk_level ) {
							'destructive' => 'wpmcp-badge-danger',
							'write'       => 'wpmcp-badge-warning',
							default       => 'wpmcp-badge-info',
						};
						$status_class = match ( $log->status ) {
							'success'     => 'wpmcp-badge-success',
							'rolled_back' => 'wpmcp-badge-secondary',
							default       => 'wpmcp-badge-danger',
						};
						?>
						<tr>
							<td>#<?php echo esc_html( $log->id ); ?></td>
							<td><code><?php echo esc_html( $log->tool_name ); ?></code></td>
							<td><span class="wpmcp-badge <?php echo esc_attr( $risk_class ); ?>"><?php echo esc_html( ucfirst( $log->risk_level ) ); ?></span></td>
							<td><span class="wpmcp-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( ucfirst( $log->status ) ); ?></span></td>
							<td>
								<?php if ( ! empty( $log->prompt ) ) : ?>
									<strong><?php esc_html_e( 'Prompt:', 'wpmcp' ); ?></strong> <em>"<?php echo esc_html( $log->prompt ); ?>"</em><br>
								<?php endif; ?>
								<details style="margin-top:4px;">
									<summary style="cursor:pointer; color:#2271b1;"><?php esc_html_e( 'View Arguments & Result Payload', 'wpmcp' ); ?></summary>
									<pre style="background:#f0f0f1; padding:8px; border-radius:4px; max-height:150px; overflow:auto; font-size:11px;">Args: <?php echo esc_html( $log->arguments ); ?>&#10;Result: <?php echo esc_html( $log->result ); ?></pre>
								</details>
							</td>
							<td><?php echo esc_html( $log->created_at ); ?></td>
							<td>
								<?php if ( $is_rolled_back ) : ?>
									<span style="color:#646970; font-size:12px;">Rolled Back</span>
								<?php elseif ( $has_snapshot && 'success' === $log->status ) : ?>
									<button class="button button-small wpmcp-btn-rollback" data-log-id="<?php echo esc_attr( $log->id ); ?>"><?php esc_html_e( 'Undo', 'wpmcp' ); ?></button>
								<?php else : ?>
									<span style="color:#a7aaad;">—</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php printf( esc_html__( '%d items', 'wpmcp' ), $total ); ?></span>
				<?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
					<a class="page-numbers <?php echo ( $i === $page ) ? 'current' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>"><?php echo esc_html( $i ); ?></a>
				<?php endfor; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
