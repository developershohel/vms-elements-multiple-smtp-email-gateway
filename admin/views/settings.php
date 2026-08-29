<?php
/**
 * Settings tab.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

$settings     = VMS_MSG_Settings::get_all();
$days         = isset( $settings['log_retention_days'] ) ? (int) $settings['log_retention_days'] : 30;
$body_mode    = isset( $settings['log_body_mode'] ) ? $settings['log_body_mode'] : 'full';
$body_max     = isset( $settings['log_body_max_chars'] ) ? (int) $settings['log_body_max_chars'] : 5000;
$redact       = ! empty( $settings['log_redact_body'] );
$debug_on     = VMS_MSG_Settings::is_smtp_debug_active();
$debug_until  = isset( $settings['smtp_debug_until'] ) ? (int) $settings['smtp_debug_until'] : 0;
$debug_log    = VMS_MSG_Settings::get_debug_log();
$smoke        = get_transient( 'vms_msg_smoke_result_' . get_current_user_id() );
$current_user = wp_get_current_user();
$default_to   = is_email( $current_user->user_email ) ? $current_user->user_email : get_option( 'admin_email' );
?>
<section class="vms-msg-card">
	<h2><?php esc_html_e( 'Plugin settings', 'vms-elements-multiple-smtp-email-gateway' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="vms_msg_save_settings" />
		<?php wp_nonce_field( 'vms_msg_save_settings' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="log_retention_days"><?php esc_html_e( 'Log retention (days)', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
				<td>
					<input type="number" min="0" max="3650" class="small-text" name="log_retention_days" id="log_retention_days" value="<?php echo esc_attr( (string) $days ); ?>" />
					<p class="description">
						<?php esc_html_e( 'Automatically delete email logs older than this many days. Use 0 to keep logs forever. Runs daily via WP-Cron.', 'vms-elements-multiple-smtp-email-gateway' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="log_body_mode"><?php esc_html_e( 'Message body in logs', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
				<td>
					<select name="log_body_mode" id="log_body_mode">
						<option value="full" <?php selected( $body_mode, 'full' ); ?>><?php esc_html_e( 'Store full body', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
						<option value="truncate" <?php selected( $body_mode, 'truncate' ); ?>><?php esc_html_e( 'Truncate body', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
						<option value="omit" <?php selected( $body_mode, 'omit' ); ?>><?php esc_html_e( 'Omit body', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Controls how much of the email body is stored for resend and auditing.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="log_body_max_chars"><?php esc_html_e( 'Truncate after (characters)', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
				<td>
					<input type="number" min="500" max="200000" class="small-text" name="log_body_max_chars" id="log_body_max_chars" value="<?php echo esc_attr( (string) $body_max ); ?>" />
					<p class="description"><?php esc_html_e( 'Used when “Truncate body” is selected.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Privacy / GDPR', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="log_redact_body" value="1" <?php checked( $redact ); ?> />
						<?php esc_html_e( 'Redact message bodies in logs (store “[redacted]” instead)', 'vms-elements-multiple-smtp-email-gateway' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When enabled, resend cannot restore the original body. Subject and recipients are still logged.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'SMTP debug mode', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="smtp_debug" value="1" <?php checked( $debug_on ); ?> />
						<?php esc_html_e( 'Capture PHPMailer SMTP conversation (auto-off after 1 hour)', 'vms-elements-multiple-smtp-email-gateway' ); ?>
					</label>
					<?php if ( $debug_on && $debug_until > 0 ) : ?>
						<p class="description">
							<?php
							printf(
								/* translators: %s: local datetime */
								esc_html__( 'Active until %s.', 'vms-elements-multiple-smtp-email-gateway' ),
								esc_html( wp_date( 'Y-m-d H:i:s', $debug_until ) )
							);
							?>
						</p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'Enable only while troubleshooting. Debug output may include sensitive SMTP details.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Failure failover', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="failure_failover" value="1" <?php checked( ! empty( $settings['failure_failover'] ) ); ?> />
						<?php esc_html_e( 'On SMTP send failure, retry via ordered fallback chain then global default', 'vms-elements-multiple-smtp-email-gateway' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Background mail queue', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="queue_enabled" value="1" <?php checked( ! empty( $settings['queue_enabled'] ) ); ?> />
						<?php esc_html_e( 'Queue outgoing mail and send via WP-Cron (uses Action Scheduler when available)', 'vms-elements-multiple-smtp-email-gateway' ); ?>
					</label>
					<p>
						<label for="queue_batch_size"><?php esc_html_e( 'Batch size', 'vms-elements-multiple-smtp-email-gateway' ); ?></label>
						<input type="number" min="1" max="50" class="small-text" name="queue_batch_size" id="queue_batch_size" value="<?php echo esc_attr( (string) (int) $settings['queue_batch_size'] ); ?>" />
					</p>
					<p class="description"><?php esc_html_e( 'Callers return immediately; delivery happens in the background. Test/resend emails bypass the queue.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="alert_email"><?php esc_html_e( 'Alert email', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
				<td>
					<input type="email" class="regular-text" name="alert_email" id="alert_email" value="<?php echo esc_attr( (string) $settings['alert_email'] ); ?>" placeholder="<?php echo esc_attr( (string) get_option( 'admin_email' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Leave blank to use the site admin email.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Health failure alerts', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="alert_on_health_fail" value="1" <?php checked( ! empty( $settings['alert_on_health_fail'] ) ); ?> />
						<?php esc_html_e( 'Email when an SMTP health check fails (max once per account per day)', 'vms-elements-multiple-smtp-email-gateway' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Failure spike alerts', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="alert_on_failure_spike" value="1" <?php checked( ! empty( $settings['alert_on_failure_spike'] ) ); ?> />
						<?php esc_html_e( 'Email when failed sends in the last hour exceed the threshold', 'vms-elements-multiple-smtp-email-gateway' ); ?>
					</label>
					<p>
						<label for="failure_spike_threshold"><?php esc_html_e( 'Threshold', 'vms-elements-multiple-smtp-email-gateway' ); ?></label>
						<input type="number" min="1" class="small-text" name="failure_spike_threshold" id="failure_spike_threshold" value="<?php echo esc_attr( (string) (int) $settings['failure_spike_threshold'] ); ?>" />
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save settings', 'vms-elements-multiple-smtp-email-gateway' ) ); ?>
	</form>

	<hr />

	<h3><?php esc_html_e( 'Mail queue', 'vms-elements-multiple-smtp-email-gateway' ); ?></h3>
	<p>
		<?php
		printf(
			/* translators: %d: pending count */
			esc_html__( 'Pending queued emails: %d', 'vms-elements-multiple-smtp-email-gateway' ),
			(int) VMS_MSG_Queue::count_pending()
		);
		?>
	</p>
	<p>
		<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vms_msg_process_queue' ), 'vms_msg_process_queue' ) ); ?>">
			<?php esc_html_e( 'Process queue now', 'vms-elements-multiple-smtp-email-gateway' ); ?>
		</a>
	</p>

	<hr />

	<h3><?php esc_html_e( 'Smoke test', 'vms-elements-multiple-smtp-email-gateway' ); ?></h3>
	<p><?php esc_html_e( 'Verify tables, OpenSSL, accounts, conflicts, and default-account health. Optionally send a live test email.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="vms-msg-smoke-form">
		<input type="hidden" name="action" value="vms_msg_smoke_test" />
		<?php wp_nonce_field( 'vms_msg_smoke_test' ); ?>
		<p>
			<label>
				<input type="checkbox" name="send_test" value="1" />
				<?php esc_html_e( 'Also send a live test email via the global default account', 'vms-elements-multiple-smtp-email-gateway' ); ?>
			</label>
		</p>
		<p>
			<label for="vms_msg_smoke_to"><?php esc_html_e( 'Test recipient', 'vms-elements-multiple-smtp-email-gateway' ); ?></label>
			<input type="email" class="regular-text" name="to_email" id="vms_msg_smoke_to" value="<?php echo esc_attr( $default_to ); ?>" />
		</p>
		<?php submit_button( __( 'Run smoke test', 'vms-elements-multiple-smtp-email-gateway' ), 'secondary', 'submit', false ); ?>
	</form>

	<?php if ( is_array( $smoke ) && ! empty( $smoke['steps'] ) ) : ?>
		<ul class="vms-msg-smoke-results">
			<?php foreach ( $smoke['steps'] as $step ) : ?>
				<li class="vms-msg-smoke-results__item vms-msg-smoke-results__item--<?php echo esc_attr( $step['status'] ); ?>">
					<strong><?php echo esc_html( $step['label'] ); ?></strong>
					— <?php echo esc_html( $step['detail'] ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<hr />

	<h3><?php esc_html_e( 'SMTP debug log', 'vms-elements-multiple-smtp-email-gateway' ); ?></h3>
	<?php if ( '' === $debug_log ) : ?>
		<p class="description"><?php esc_html_e( 'No debug output yet. Enable SMTP debug mode and send a test email.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
	<?php else : ?>
		<textarea class="large-text code vms-msg-debug-log" rows="12" readonly><?php echo esc_textarea( $debug_log ); ?></textarea>
		<p>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vms_msg_clear_debug_log' ), 'vms_msg_clear_debug_log' ) ); ?>">
				<?php esc_html_e( 'Clear debug log', 'vms-elements-multiple-smtp-email-gateway' ); ?>
			</a>
		</p>
	<?php endif; ?>

	<hr />

	<h3><?php esc_html_e( 'Maintenance', 'vms-elements-multiple-smtp-email-gateway' ); ?></h3>
	<p><?php esc_html_e( 'Run log pruning now using the current retention setting.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
	<p>
		<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vms_msg_prune_logs' ), 'vms_msg_prune_logs' ) ); ?>">
			<?php esc_html_e( 'Prune old logs now', 'vms-elements-multiple-smtp-email-gateway' ); ?>
		</a>
	</p>
	<p class="description">
		<?php esc_html_e( 'WP-CLI: wp vms-msg test | wp vms-msg prune | wp vms-msg health', 'vms-elements-multiple-smtp-email-gateway' ); ?>
	</p>

	<hr />

	<h3><?php esc_html_e( 'Quick start checklist', 'vms-elements-multiple-smtp-email-gateway' ); ?></h3>
	<ol class="vms-msg-checklist">
		<li><?php esc_html_e( 'Add an SMTP account and set one as Global default.', 'vms-elements-multiple-smtp-email-gateway' ); ?></li>
		<li><?php esc_html_e( 'Enable Force From for provider accounts that require a verified sender.', 'vms-elements-multiple-smtp-email-gateway' ); ?></li>
		<li><?php esc_html_e( 'Optional: set Fallback priority (1 = first) for ordered limit failover.', 'vms-elements-multiple-smtp-email-gateway' ); ?></li>
		<li><?php esc_html_e( 'Run Smoke test, then Check health and Test email.', 'vms-elements-multiple-smtp-email-gateway' ); ?></li>
		<li><?php esc_html_e( 'Confirm the result under Email Logs (Sent / Failed).', 'vms-elements-multiple-smtp-email-gateway' ); ?></li>
		<li><?php esc_html_e( 'Review privacy settings if you store personal data in message bodies.', 'vms-elements-multiple-smtp-email-gateway' ); ?></li>
	</ol>
</section>
