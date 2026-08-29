<?php
/**
 * Resend logged emails with an explicit SMTP provider override.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles resending a stored log entry through a chosen SMTP account.
 */
class VMS_MSG_Resend {

	/**
	 * Resend a log entry using a specific SMTP account.
	 *
	 * The forced account ID tells VMS_MSG_Mailer to skip From-based routing
	 * and apply the selected provider credentials on phpmailer_init.
	 *
	 * @param int $log_id     Original log ID.
	 * @param int $account_id SMTP account to force.
	 * @return true|WP_Error
	 */
	public static function resend( $log_id, $account_id ) {
		$log_id     = absint( $log_id );
		$account_id = absint( $account_id );

		if ( $log_id < 1 || $account_id < 1 ) {
			return new WP_Error( 'vms_msg_invalid_args', __( 'Invalid log or account ID.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$log = VMS_MSG_Logger::get( $log_id );
		if ( ! $log ) {
			return new WP_Error( 'vms_msg_log_missing', __( 'Email log entry not found.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$account = VMS_MSG_Accounts::get( $account_id, true );
		if ( ! $account ) {
			return new WP_Error( 'vms_msg_account_missing', __( 'SMTP account not found.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$to = VMS_MSG_Logger::normalize_recipients( $log->to_email );
		if ( '' === $to ) {
			return new WP_Error( 'vms_msg_missing_to', __( 'Log entry has no valid recipient.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$subject = (string) $log->subject;
		$message = (string) $log->message_body;
		$headers = VMS_MSG_Logger::headers_to_array( $log->headers );

		// Force PHPMailer to use the selected provider, then restore normal routing.
		VMS_MSG_Mailer::set_forced_account( $account_id, true, $log_id );

		$sent = wp_mail( $to, $subject, $message, $headers );

		VMS_MSG_Mailer::clear_forced_account();

		if ( ! $sent ) {
			return new WP_Error(
				'vms_msg_resend_failed',
				__( 'Resend failed. Check the new log entry for the SMTP error details.', 'vms-elements-multiple-smtp-email-gateway' )
			);
		}

		return true;
	}

	/**
	 * Send a test message through a specific SMTP account.
	 *
	 * @param int    $account_id Account ID.
	 * @param string $to_email   Destination email.
	 * @return true|WP_Error
	 */
	public static function send_test( $account_id, $to_email ) {
		$account_id = absint( $account_id );
		$to_email   = sanitize_email( $to_email );

		if ( $account_id < 1 ) {
			return new WP_Error( 'vms_msg_invalid_account', __( 'Invalid account ID.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}
		if ( ! is_email( $to_email ) ) {
			return new WP_Error( 'vms_msg_invalid_to', __( 'Please provide a valid test recipient email.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$account = VMS_MSG_Accounts::get( $account_id, true );
		if ( ! $account ) {
			return new WP_Error( 'vms_msg_account_missing', __( 'SMTP account not found.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$from    = sanitize_email( $account->sender_email );
		$subject = sprintf(
			/* translators: %s: account name */
			__( '[VMS Multi Mailer] Test email — %s', 'vms-elements-multiple-smtp-email-gateway' ),
			$account->account_name
		);
		$message = sprintf(
			/* translators: 1: site name, 2: account name, 3: datetime */
			__( "This is a test email from %1\$s.\n\nSMTP account: %2\$s\nSent at: %3\$s\n", 'vms-elements-multiple-smtp-email-gateway' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$account->account_name,
			current_time( 'mysql' )
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( is_email( $from ) ) {
			$headers[] = 'From: ' . $from;
		}

		VMS_MSG_Mailer::set_forced_account( $account_id, false, 0 );
		$sent = wp_mail( $to_email, $subject, $message, $headers );
		VMS_MSG_Mailer::clear_forced_account();

		if ( ! $sent ) {
			return new WP_Error(
				'vms_msg_test_failed',
				__( 'Test email failed to send. Check Email Logs for the error message.', 'vms-elements-multiple-smtp-email-gateway' )
			);
		}

		return true;
	}
}
