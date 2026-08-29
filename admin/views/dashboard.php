<?php
/**
 * Dashboard analytics tab.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

$stats   = VMS_MSG_Analytics::summary();
$pending = VMS_MSG_Queue::count_pending();
?>
<section class="vms-msg-card">
	<h2><?php esc_html_e( 'Email analytics', 'vms-elements-multiple-smtp-email-gateway' ); ?></h2>
	<div class="vms-msg-stats">
		<div class="vms-msg-stat">
			<span class="vms-msg-stat__value"><?php echo esc_html( (string) $stats['sent_today'] ); ?></span>
			<span class="vms-msg-stat__label"><?php esc_html_e( 'Sent today', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
		</div>
		<div class="vms-msg-stat">
			<span class="vms-msg-stat__value"><?php echo esc_html( (string) $stats['failed_today'] ); ?></span>
			<span class="vms-msg-stat__label"><?php esc_html_e( 'Failed today', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
		</div>
		<div class="vms-msg-stat">
			<span class="vms-msg-stat__value"><?php echo esc_html( (string) $stats['sent_7'] ); ?> / <?php echo esc_html( (string) $stats['failed_7'] ); ?></span>
			<span class="vms-msg-stat__label"><?php esc_html_e( 'Sent / failed (7 days)', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
		</div>
		<div class="vms-msg-stat">
			<span class="vms-msg-stat__value"><?php echo esc_html( (string) $stats['sent_30'] ); ?> / <?php echo esc_html( (string) $stats['failed_30'] ); ?></span>
			<span class="vms-msg-stat__label"><?php esc_html_e( 'Sent / failed (30 days)', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
		</div>
		<?php if ( VMS_MSG_Settings::get( 'queue_enabled', 0 ) ) : ?>
			<div class="vms-msg-stat">
				<span class="vms-msg-stat__value"><?php echo esc_html( (string) $pending ); ?></span>
				<span class="vms-msg-stat__label"><?php esc_html_e( 'Queued emails', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
			</div>
		<?php endif; ?>
	</div>
</section>

<div class="vms-msg-grid">
	<section class="vms-msg-card">
		<h2><?php esc_html_e( 'Today’s usage vs limits', 'vms-elements-multiple-smtp-email-gateway' ); ?></h2>
		<?php if ( empty( $stats['usage'] ) ) : ?>
			<p><?php esc_html_e( 'No SMTP accounts configured yet.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Account', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Sent today', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Daily limit', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $stats['usage'] as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['name'] ); ?></td>
							<td><?php echo esc_html( (string) $row['sent'] ); ?></td>
							<td><?php echo $row['limit'] > 0 ? esc_html( (string) $row['limit'] ) : esc_html__( 'Unlimited', 'vms-elements-multiple-smtp-email-gateway' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>

	<section class="vms-msg-card">
		<h2><?php esc_html_e( 'Top failing accounts (7 days)', 'vms-elements-multiple-smtp-email-gateway' ); ?></h2>
		<?php if ( empty( $stats['top_failures'] ) ) : ?>
			<p><?php esc_html_e( 'No failures in the last 7 days.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Account', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Failures', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $stats['top_failures'] as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->used_smtp_account ? $row->used_smtp_account : ( '#' . (int) $row->smtp_account_id ) ); ?></td>
							<td><?php echo esc_html( (string) $row->fail_count ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>
</div>
