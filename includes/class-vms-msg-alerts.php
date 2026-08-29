<?php
/**
 * Admin email alerts for health / failure spikes.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends throttled notification emails to the site admin.
 */
class VMS_MSG_Alerts {

	/**
	 * Notify when an account health check fails.
	 *
	 * @param int    $account_id Account ID.
	 * @param string $message    Failure message.
	 * @return void
	 */
	public static function maybe_health_fail( $account_id, $message ) {
		if ( ! VMS_MSG_Settings::get( 'alert_on_health_fail', 1 ) ) {
			return;
		}

		$account_id = absint( $account_id );
		$throttle   = 'vms_msg_alert_health_' . $account_id;
		if ( get_transient( $throttle ) ) {
			return;
		}

		$account = VMS_MSG_Accounts::get( $account_id, false );
		$name    = $account ? $account->account_name : ( '#' . $account_id );

		$subject = sprintf(
			/* translators: %s: account name */
			__( '[VMS Multi Mailer] Health check failed: %s', 'vms-elements-multiple-smtp-email-gateway' ),
			$name
		);

		$body = sprintf(
			/* translators: 1: account name, 2: error, 3: site name */
			__( "SMTP health check failed for “%1\$s”.\n\nError: %2\$s\n\nSite: %3\$s", 'vms-elements-multiple-smtp-email-gateway' ),
			$name,
			$message,
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		if ( self::send( $subject, $body ) ) {
			set_transient( $throttle, 1, DAY_IN_SECONDS );
		}
	}

	/**
	 * Check failure-rate spike (called from daily/hourly maintenance).
	 *
	 * @return void
	 */
	public static function maybe_failure_spike() {
		if ( ! VMS_MSG_Settings::get( 'alert_on_failure_spike', 1 ) ) {
			return;
		}

		$threshold = (int) VMS_MSG_Settings::get( 'failure_spike_threshold', 10 );
		if ( $threshold < 1 ) {
			return;
		}

		$count = VMS_MSG_Analytics::failures_in_minutes( 60 );
		if ( $count < $threshold ) {
			return;
		}

		if ( get_transient( 'vms_msg_alert_spike' ) ) {
			return;
		}

		$subject = __( '[VMS Multi Mailer] Email failure spike detected', 'vms-elements-multiple-smtp-email-gateway' );
		$body    = sprintf(
			/* translators: 1: fail count, 2: threshold, 3: site name */
			__( "%1\$d email failures occurred in the last hour (threshold: %2\$d).\n\nReview Email Logs in VMS Multi Mailer.\n\nSite: %3\$s", 'vms-elements-multiple-smtp-email-gateway' ),
			$count,
			$threshold,
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		if ( self::send( $subject, $body ) ) {
			set_transient( 'vms_msg_alert_spike', 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Send alert to configured address (bypasses queue).
	 *
	 * @param string $subject Subject.
	 * @param string $body    Plain body.
	 * @return bool
	 */
	private static function send( $subject, $body ) {
		$to = VMS_MSG_Settings::get( 'alert_email', '' );
		if ( ! is_email( $to ) ) {
			$to = sanitize_email( get_option( 'admin_email' ) );
		}
		if ( ! is_email( $to ) ) {
			return false;
		}

		// Avoid re-entering the mail queue for system alerts.
		$prev = ! empty( $GLOBALS['vms_msg_bypass_queue'] );
		$GLOBALS['vms_msg_bypass_queue'] = true;
		$result = wp_mail( $to, $subject, $body );
		$GLOBALS['vms_msg_bypass_queue'] = $prev;

		return (bool) $result;
	}
}
