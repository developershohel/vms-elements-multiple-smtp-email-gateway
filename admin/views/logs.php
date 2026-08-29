<?php
/**
 * Email Logs tab.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

$status     = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$account_id = isset( $_GET['account_id'] ) ? absint( wp_unslash( $_GET['account_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$date_from  = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$date_to    = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$page       = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$accounts = VMS_MSG_Accounts::get_all( false );

$result = VMS_MSG_Logger::query(
	array(
		'status'     => $status,
		'search'     => $search,
		'account_id' => $account_id,
		'date_from'  => $date_from,
		'date_to'    => $date_to,
		'page'       => $page,
		'per_page'   => 20,
	)
);

$items = $result['items'];
$pages = max( 1, (int) $result['pages'] );
$total = (int) $result['total'];

$export_url = wp_nonce_url(
	add_query_arg(
		array(
			'action'     => 'vms_msg_export_logs',
			'status'     => $status,
			's'          => $search,
			'account_id' => $account_id,
			'date_from'  => $date_from,
			'date_to'    => $date_to,
		),
		admin_url( 'admin-post.php' )
	),
	'vms_msg_export_logs'
);
?>
<section class="vms-msg-card">
	<form method="get" class="vms-msg-filters">
		<input type="hidden" name="page" value="<?php echo esc_attr( VMS_MSG_SLUG ); ?>" />
		<input type="hidden" name="tab" value="logs" />
		<label>
			<span class="screen-reader-text"><?php esc_html_e( 'Status', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
				<option value="sent" <?php selected( $status, 'sent' ); ?>><?php esc_html_e( 'Sent', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
				<option value="failed" <?php selected( $status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
			</select>
		</label>
		<label>
			<span class="screen-reader-text"><?php esc_html_e( 'Account', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
			<select name="account_id">
				<option value="0"><?php esc_html_e( 'All accounts', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
				<?php foreach ( $accounts as $acc ) : ?>
					<option value="<?php echo esc_attr( (string) $acc->id ); ?>" <?php selected( $account_id, (int) $acc->id ); ?>><?php echo esc_html( $acc->account_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span class="screen-reader-text"><?php esc_html_e( 'From date', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
			<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
		</label>
		<label>
			<span class="screen-reader-text"><?php esc_html_e( 'To date', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
			<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
		</label>
		<label>
			<span class="screen-reader-text"><?php esc_html_e( 'Search logs', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search to, subject, error…', 'vms-elements-multiple-smtp-email-gateway' ); ?>" />
		</label>
		<?php submit_button( __( 'Filter', 'vms-elements-multiple-smtp-email-gateway' ), 'secondary', '', false ); ?>
		<a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export CSV', 'vms-elements-multiple-smtp-email-gateway' ); ?></a>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="vms-msg-logs-bulk">
		<input type="hidden" name="action" value="vms_msg_bulk_logs" />
		<?php wp_nonce_field( 'vms_msg_bulk_logs' ); ?>
		<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>" />
		<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>" />
		<input type="hidden" name="account_id" value="<?php echo esc_attr( (string) $account_id ); ?>" />
		<input type="hidden" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
		<input type="hidden" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />

		<div class="vms-msg-bulk-bar">
			<select name="bulk_action">
				<option value=""><?php esc_html_e( 'Bulk actions', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete selected', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
				<option value="delete_failed"><?php esc_html_e( 'Delete all failed (matching filters)', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
			</select>
			<?php submit_button( __( 'Apply', 'vms-elements-multiple-smtp-email-gateway' ), 'secondary', 'submit', false ); ?>
		</div>

		<p class="vms-msg-meta">
			<?php
			printf(
				/* translators: %d: total log count */
				esc_html( _n( '%d log entry', '%d log entries', $total, 'vms-elements-multiple-smtp-email-gateway' ) ),
				(int) $total
			);
			?>
		</p>

		<?php if ( empty( $items ) ) : ?>
			<p><?php esc_html_e( 'No email logs found.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
		<?php else : ?>
			<table class="widefat striped vms-msg-table">
				<thead>
					<tr>
						<td class="check-column"><input type="checkbox" id="vms-msg-check-all" /></td>
						<th><?php esc_html_e( 'Date', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'To', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Subject', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Status', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'SMTP account', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Error reason', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $log ) : ?>
						<tr>
							<th class="check-column">
								<input type="checkbox" name="log_ids[]" value="<?php echo esc_attr( (string) $log->id ); ?>" />
							</th>
							<td>
								<?php echo esc_html( $log->date_time ); ?>
								<?php if ( ! empty( $log->is_resend ) ) : ?>
									<br /><span class="vms-msg-badge"><?php esc_html_e( 'Resend', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $log->to_email ); ?></td>
							<td><?php echo esc_html( $log->subject ); ?></td>
							<td>
								<?php if ( 'sent' === $log->status ) : ?>
									<span class="vms-msg-badge vms-msg-badge--ok"><?php esc_html_e( 'Sent', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
								<?php else : ?>
									<span class="vms-msg-badge vms-msg-badge--err"><?php esc_html_e( 'Failed', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $log->used_smtp_account ); ?></td>
							<td class="vms-msg-error-cell"><?php echo esc_html( $log->error_message ); ?></td>
							<td class="vms-msg-actions">
								<button
									type="button"
									class="button button-small button-primary vms-msg-resend-btn"
									data-log-id="<?php echo esc_attr( (string) $log->id ); ?>"
								><?php esc_html_e( 'Resend', 'vms-elements-multiple-smtp-email-gateway' ); ?></button>
								<a
									class="button button-small button-link-delete"
									href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vms_msg_delete_log&log_id=' . (int) $log->id ), 'vms_msg_delete_log' ) ); ?>"
									onclick="return confirm('<?php echo esc_js( __( 'Delete this log entry?', 'vms-elements-multiple-smtp-email-gateway' ) ); ?>');"
								><?php esc_html_e( 'Delete', 'vms-elements-multiple-smtp-email-gateway' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'total'     => $pages,
									'current'   => $page,
								)
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</form>
</section>
