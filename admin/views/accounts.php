<?php
/**
 * SMTP Accounts tab.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

$accounts      = VMS_MSG_Accounts::get_all( false );
$providers     = VMS_MSG_Providers::all();
$edit_id       = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$edit          = $edit_id ? VMS_MSG_Accounts::get( $edit_id, false ) : null;
$form_title    = $edit
	? __( 'Edit SMTP account', 'vms-elements-multiple-smtp-email-gateway' )
	: __( 'Add SMTP account', 'vms-elements-multiple-smtp-email-gateway' );
$current_user  = wp_get_current_user();
$default_test  = is_email( $current_user->user_email ) ? $current_user->user_email : get_option( 'admin_email' );
$edit_provider = $edit && ! empty( $edit->provider ) ? VMS_MSG_Providers::sanitize_id( $edit->provider ) : 'other';
$edit_meta     = $edit && ! empty( $edit->provider_meta ) ? VMS_MSG_Providers::decode_meta( $edit->provider_meta ) : array();
$ses_region    = isset( $edit_meta['ses_region'] ) ? $edit_meta['ses_region'] : 'us-east-1';
$mg_region     = isset( $edit_meta['mailgun_region'] ) ? $edit_meta['mailgun_region'] : 'us';
$zoho_region   = isset( $edit_meta['zoho_region'] ) ? $edit_meta['zoho_region'] : 'us';
$mailtrap_mode = isset( $edit_meta['mailtrap_mode'] ) ? $edit_meta['mailtrap_mode'] : 'live';
?>
<div class="vms-msg-grid">
	<section class="vms-msg-card">
		<h2><?php echo esc_html( $form_title ); ?></h2>
		<p class="description">
			<?php
			printf(
				/* translators: %d: number of built-in gateways */
				esc_html__( '%d built-in PHPMailer SMTP gateways — pick one, or use Other SMTP for any custom host.', 'vms-elements-multiple-smtp-email-gateway' ),
				(int) VMS_MSG_Providers::count()
			);
			?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="vms-msg-form" id="vms-msg-account-form" autocomplete="off">
			<input type="hidden" name="action" value="vms_msg_save_account" />
			<input type="hidden" name="account_id" value="<?php echo esc_attr( $edit ? (string) $edit->id : '0' ); ?>" />
			<input type="hidden" name="provider" id="vms-msg-provider" value="<?php echo esc_attr( $edit_provider ); ?>" />
			<?php wp_nonce_field( 'vms_msg_save_account' ); ?>

			<p class="vms-msg-provider-label"><strong><?php esc_html_e( 'Email gateway (PHPMailer SMTP)', 'vms-elements-multiple-smtp-email-gateway' ); ?></strong></p>
			<div class="vms-msg-provider-grid" role="listbox" aria-label="<?php esc_attr_e( 'Select email gateway', 'vms-elements-multiple-smtp-email-gateway' ); ?>">
				<?php foreach ( $providers as $provider ) : ?>
					<button
						type="button"
						class="vms-msg-provider-card<?php echo $edit_provider === $provider['id'] ? ' is-selected' : ''; ?>"
						data-provider="<?php echo esc_attr( $provider['id'] ); ?>"
						aria-pressed="<?php echo $edit_provider === $provider['id'] ? 'true' : 'false'; ?>"
					>
						<span class="vms-msg-prov-icon vms-msg-prov-icon--<?php echo esc_attr( $provider['icon'] ); ?>" aria-hidden="true">
							<span class="vms-msg-prov-icon__mark"><?php echo esc_html( $provider['mark'] ); ?></span>
						</span>
						<span class="vms-msg-provider-card__label"><?php echo esc_html( $provider['label'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
			<p id="vms-msg-provider-help" class="description"><?php echo esc_html( $providers[ $edit_provider ]['help'] ); ?></p>

			<div id="vms-msg-connection-map" class="vms-msg-connection-map" <?php echo ( 'other' === $edit_provider ) ? 'hidden' : ''; ?>>
				<strong><?php esc_html_e( 'Mapped connection (auto-filled)', 'vms-elements-multiple-smtp-email-gateway' ); ?></strong>
				<ul>
					<li><span><?php esc_html_e( 'Host', 'vms-elements-multiple-smtp-email-gateway' ); ?>:</span> <code id="vms-msg-map-host"><?php echo esc_html( $edit ? $edit->smtp_host : '—' ); ?></code></li>
					<li><span><?php esc_html_e( 'Port', 'vms-elements-multiple-smtp-email-gateway' ); ?>:</span> <code id="vms-msg-map-port"><?php echo esc_html( $edit ? (string) $edit->smtp_port : '—' ); ?></code></li>
					<li><span><?php esc_html_e( 'Encryption', 'vms-elements-multiple-smtp-email-gateway' ); ?>:</span> <code id="vms-msg-map-enc"><?php echo esc_html( $edit ? strtoupper( $edit->smtp_encryption ) : '—' ); ?></code></li>
					<li id="vms-msg-map-user-row"><span><?php esc_html_e( 'Username', 'vms-elements-multiple-smtp-email-gateway' ); ?>:</span> <code id="vms-msg-map-user"><?php echo esc_html( $edit ? $edit->smtp_username : '—' ); ?></code></li>
				</ul>
				<p class="description"><?php esc_html_e( 'For mapped gateways you normally only enter SMTP username and/or password. Host, port, and encryption are set automatically.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="account_name"><?php esc_html_e( 'Account name', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td><input name="account_name" id="account_name" type="text" class="regular-text" required value="<?php echo esc_attr( $edit ? $edit->account_name : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="sender_email"><?php esc_html_e( 'Sender email (routing key)', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<input name="sender_email" id="sender_email" type="email" class="regular-text" required value="<?php echo esc_attr( $edit ? $edit->sender_email : '' ); ?>" />
						<p class="description"><?php esc_html_e( 'Outgoing mail whose From address matches this email uses this SMTP account.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
					</td>
				</tr>
				<tr class="vms-msg-row-ses-region" <?php echo 'amazon_ses' === $edit_provider ? '' : 'hidden'; ?>>
					<th scope="row"><label for="ses_region"><?php esc_html_e( 'SES region', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<select name="ses_region" id="ses_region">
							<?php foreach ( VMS_MSG_Providers::ses_regions() as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $ses_region, $code ); ?>><?php echo esc_html( $label . ' — ' . $code ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="vms-msg-row-mailgun-region" <?php echo 'mailgun' === $edit_provider ? '' : 'hidden'; ?>>
					<th scope="row"><label for="mailgun_region"><?php esc_html_e( 'Mailgun region', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<select name="mailgun_region" id="mailgun_region">
							<?php foreach ( VMS_MSG_Providers::mailgun_regions() as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $mg_region, $code ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="vms-msg-row-zoho-region" <?php echo 'zoho' === $edit_provider ? '' : 'hidden'; ?>>
					<th scope="row"><label for="zoho_region"><?php esc_html_e( 'Zoho region', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<select name="zoho_region" id="zoho_region">
							<?php foreach ( VMS_MSG_Providers::zoho_regions() as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $zoho_region, $code ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="vms-msg-row-mailtrap-mode" <?php echo 'mailtrap' === $edit_provider ? '' : 'hidden'; ?>>
					<th scope="row"><label for="mailtrap_mode"><?php esc_html_e( 'Mailtrap mode', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<select name="mailtrap_mode" id="mailtrap_mode">
							<?php foreach ( VMS_MSG_Providers::mailtrap_modes() as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $mailtrap_mode, $code ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="vms-msg-row-smtp-manual">
					<th scope="row"><label for="smtp_host"><?php esc_html_e( 'SMTP host', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td><input name="smtp_host" id="smtp_host" type="text" class="regular-text" required placeholder="smtp.example.com" value="<?php echo esc_attr( $edit ? $edit->smtp_host : '' ); ?>" /></td>
				</tr>
				<tr class="vms-msg-row-smtp-manual">
					<th scope="row"><label for="smtp_port"><?php esc_html_e( 'Port', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td><input name="smtp_port" id="smtp_port" type="number" min="1" max="65535" class="small-text" required value="<?php echo esc_attr( $edit ? (string) $edit->smtp_port : '587' ); ?>" /></td>
				</tr>
				<tr class="vms-msg-row-smtp-manual">
					<th scope="row"><label for="smtp_encryption"><?php esc_html_e( 'Encryption', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<?php $enc = $edit ? $edit->smtp_encryption : 'tls'; ?>
						<select name="smtp_encryption" id="smtp_encryption">
							<option value="tls" <?php selected( $enc, 'tls' ); ?>><?php esc_html_e( 'TLS', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
							<option value="ssl" <?php selected( $enc, 'ssl' ); ?>><?php esc_html_e( 'SSL', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
							<option value="none" <?php selected( $enc, 'none' ); ?>><?php esc_html_e( 'None', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
						</select>
					</td>
				</tr>
				<tr class="vms-msg-row-smtp-username">
					<th scope="row"><label for="smtp_username"><?php esc_html_e( 'SMTP username', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<input name="smtp_username" id="smtp_username" type="text" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $edit ? $edit->smtp_username : '' ); ?>" />
						<p class="description" id="vms-msg-username-hint"></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="smtp_password"><?php esc_html_e( 'SMTP password / API key', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<input name="smtp_password" id="smtp_password" type="password" class="regular-text" autocomplete="new-password" <?php echo $edit ? '' : 'required'; ?> />
						<p class="description" id="vms-msg-password-hint">
							<?php
							echo $edit
								? esc_html__( 'Leave blank to keep the existing encrypted password.', 'vms-elements-multiple-smtp-email-gateway' )
								: esc_html__( 'Stored encrypted with OpenSSL using WordPress salts.', 'vms-elements-multiple-smtp-email-gateway' );
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Force From email', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="force_from" value="1" <?php checked( $edit && ! empty( $edit->force_from ) ); ?> />
							<?php esc_html_e( 'Always set From to this account’s sender email (recommended for provider compliance)', 'vms-elements-multiple-smtp-email-gateway' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="daily_limit"><?php esc_html_e( 'Daily send limit', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<input name="daily_limit" id="daily_limit" type="number" min="0" class="small-text" value="<?php echo esc_attr( $edit && isset( $edit->daily_limit ) ? (string) (int) $edit->daily_limit : '0' ); ?>" />
						<p class="description"><?php esc_html_e( '0 = unlimited. When reached, the ordered fallback chain is tried, then the global default; otherwise the send fails and is logged.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fallback_priority"><?php esc_html_e( 'Fallback priority', 'vms-elements-multiple-smtp-email-gateway' ); ?></label></th>
					<td>
						<input name="fallback_priority" id="fallback_priority" type="number" min="0" max="9999" class="small-text" value="<?php echo esc_attr( $edit && isset( $edit->fallback_priority ) ? (string) (int) $edit->fallback_priority : '0' ); ?>" />
						<p class="description"><?php esc_html_e( '0 = not in the chain. Lower numbers are tried first when another account hits its daily limit (1 before 2, etc.).', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Global default', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="is_default" value="1" <?php checked( $edit && ! empty( $edit->is_default ) ); ?> />
							<?php esc_html_e( 'Use as fallback when no sender match is found', 'vms-elements-multiple-smtp-email-gateway' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( $edit ? __( 'Update account', 'vms-elements-multiple-smtp-email-gateway' ) : __( 'Add account', 'vms-elements-multiple-smtp-email-gateway' ) ); ?>
			<?php if ( $edit ) : ?>
				<a class="button" href="<?php echo esc_url( add_query_arg( 'tab', 'accounts', $base_url ) ); ?>"><?php esc_html_e( 'Cancel edit', 'vms-elements-multiple-smtp-email-gateway' ); ?></a>
			<?php endif; ?>
		</form>
	</section>

	<section class="vms-msg-card">
		<h2><?php esc_html_e( 'Configured accounts', 'vms-elements-multiple-smtp-email-gateway' ); ?></h2>
		<?php if ( empty( $accounts ) ) : ?>
			<p><?php esc_html_e( 'No SMTP accounts yet. Pick a gateway and add your first account.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
		<?php else : ?>
			<table class="widefat striped vms-msg-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Gateway', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Name', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Sender', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Usage today', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Fallback', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Health', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Default', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'vms-elements-multiple-smtp-email-gateway' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $accounts as $account ) : ?>
						<?php
						$pid        = ! empty( $account->provider ) ? VMS_MSG_Providers::sanitize_id( $account->provider ) : 'other';
						$prov       = VMS_MSG_Providers::get( $pid );
						$sent_today = VMS_MSG_Logger::count_sent_today( (int) $account->id );
						$limit      = isset( $account->daily_limit ) ? (int) $account->daily_limit : 0;
						$fb_pri     = isset( $account->fallback_priority ) ? (int) $account->fallback_priority : 0;
						$health     = isset( $account->health_status ) ? $account->health_status : 'unknown';
						?>
						<tr>
							<td>
								<span class="vms-msg-prov-chip">
									<span class="vms-msg-prov-icon vms-msg-prov-icon--<?php echo esc_attr( $prov['icon'] ); ?> vms-msg-prov-icon--sm" aria-hidden="true">
										<span class="vms-msg-prov-icon__mark"><?php echo esc_html( $prov['mark'] ); ?></span>
									</span>
									<?php echo esc_html( $prov['label'] ); ?>
								</span>
								<?php if ( ! empty( $account->force_from ) ) : ?>
									<br /><span class="vms-msg-badge"><?php esc_html_e( 'Force From', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
								<?php endif; ?>
							</td>
							<td><strong><?php echo esc_html( $account->account_name ); ?></strong></td>
							<td><?php echo esc_html( $account->sender_email ); ?></td>
							<td>
								<?php
								if ( $limit > 0 ) {
									echo esc_html( (string) $sent_today . ' / ' . (string) $limit );
								} else {
									echo esc_html( (string) $sent_today . ' / ∞' );
								}
								?>
							</td>
							<td>
								<?php
								echo $fb_pri > 0
									? esc_html( (string) $fb_pri )
									: esc_html__( '—', 'vms-elements-multiple-smtp-email-gateway' );
								?>
							</td>
							<td class="vms-msg-health-cell" data-account-id="<?php echo esc_attr( (string) $account->id ); ?>">
								<?php if ( 'ok' === $health ) : ?>
									<span class="vms-msg-badge vms-msg-badge--ok"><?php esc_html_e( 'Healthy', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
								<?php elseif ( 'fail' === $health ) : ?>
									<span class="vms-msg-badge vms-msg-badge--err" title="<?php echo esc_attr( isset( $account->health_message ) ? $account->health_message : '' ); ?>"><?php esc_html_e( 'Failed', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
								<?php else : ?>
									<span class="vms-msg-badge"><?php esc_html_e( 'Unknown', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $account->health_checked_at ) && '0000-00-00 00:00:00' !== $account->health_checked_at ) : ?>
									<br /><small><?php echo esc_html( $account->health_checked_at ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $account->is_default ) ) : ?>
									<span class="vms-msg-badge vms-msg-badge--ok"><?php esc_html_e( 'Default', 'vms-elements-multiple-smtp-email-gateway' ); ?></span>
								<?php else : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vms_msg_set_default&account_id=' . (int) $account->id ), 'vms_msg_set_default' ) ); ?>">
										<?php esc_html_e( 'Make default', 'vms-elements-multiple-smtp-email-gateway' ); ?>
									</a>
								<?php endif; ?>
							</td>
							<td class="vms-msg-actions">
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'accounts', 'edit' => (int) $account->id ), $base_url ) ); ?>"><?php esc_html_e( 'Edit', 'vms-elements-multiple-smtp-email-gateway' ); ?></a>
								<button type="button" class="button button-small vms-msg-health-btn" data-account-id="<?php echo esc_attr( (string) $account->id ); ?>"><?php esc_html_e( 'Check health', 'vms-elements-multiple-smtp-email-gateway' ); ?></button>
								<button
									type="button"
									class="button button-small vms-msg-test-btn"
									data-account-id="<?php echo esc_attr( (string) $account->id ); ?>"
									data-default-to="<?php echo esc_attr( $default_test ); ?>"
								><?php esc_html_e( 'Test email', 'vms-elements-multiple-smtp-email-gateway' ); ?></button>
								<a
									class="button button-small button-link-delete"
									href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vms_msg_delete_account&account_id=' . (int) $account->id ), 'vms_msg_delete_account' ) ); ?>"
									onclick="return confirm('<?php echo esc_js( __( 'Delete this SMTP account?', 'vms-elements-multiple-smtp-email-gateway' ) ); ?>');"
								><?php esc_html_e( 'Delete', 'vms-elements-multiple-smtp-email-gateway' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>

	<section class="vms-msg-card">
		<h2><?php esc_html_e( 'Import / export accounts', 'vms-elements-multiple-smtp-email-gateway' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Export JSON without passwords. After import, edit each account and set the SMTP password again.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
		<p>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vms_msg_export_accounts' ), 'vms_msg_export_accounts' ) ); ?>">
				<?php esc_html_e( 'Export accounts (JSON)', 'vms-elements-multiple-smtp-email-gateway' ); ?>
			</a>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="vms_msg_import_accounts" />
			<?php wp_nonce_field( 'vms_msg_import_accounts' ); ?>
			<p>
				<input type="file" name="import_file" accept=".json,application/json" required />
			</p>
			<?php submit_button( __( 'Import accounts', 'vms-elements-multiple-smtp-email-gateway' ), 'secondary', 'submit', false ); ?>
		</form>
	</section>
</div>
