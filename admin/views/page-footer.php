<?php
/**
 * Admin page footer + resend modal markup.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;
?>
	</div><!-- .vms-msg-panel -->
</div><!-- .wrap -->

<div id="vms-msg-resend-modal" class="vms-msg-modal" hidden>
	<div class="vms-msg-modal__backdrop" data-vms-msg-close></div>
	<div class="vms-msg-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="vms-msg-resend-title">
		<header class="vms-msg-modal__header">
			<h2 id="vms-msg-resend-title"><?php echo esc_html__( 'Resend email', 'vms-elements-multiple-smtp-email-gateway' ); ?></h2>
			<button type="button" class="vms-msg-modal__close" data-vms-msg-close aria-label="<?php esc_attr_e( 'Close', 'vms-elements-multiple-smtp-email-gateway' ); ?>">&times;</button>
		</header>
		<div class="vms-msg-modal__body">
			<p><?php echo esc_html__( 'Choose which SMTP provider should send this message. Routing by From address is skipped for this resend.', 'vms-elements-multiple-smtp-email-gateway' ); ?></p>
			<p>
				<label for="vms-msg-resend-account"><strong><?php echo esc_html__( 'SMTP provider', 'vms-elements-multiple-smtp-email-gateway' ); ?></strong></label>
				<select id="vms-msg-resend-account" class="regular-text">
					<option value=""><?php echo esc_html__( 'Select an SMTP provider', 'vms-elements-multiple-smtp-email-gateway' ); ?></option>
				</select>
			</p>
			<input type="hidden" id="vms-msg-resend-log-id" value="" />
			<p id="vms-msg-resend-feedback" class="vms-msg-feedback" hidden></p>
		</div>
		<footer class="vms-msg-modal__footer">
			<button type="button" class="button" data-vms-msg-close><?php echo esc_html__( 'Cancel', 'vms-elements-multiple-smtp-email-gateway' ); ?></button>
			<button type="button" class="button button-primary" id="vms-msg-resend-confirm"><?php echo esc_html__( 'Resend now', 'vms-elements-multiple-smtp-email-gateway' ); ?></button>
		</footer>
	</div>
</div>
