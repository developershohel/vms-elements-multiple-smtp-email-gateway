<?php
/**
 * SMTP connection health checks.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Verifies SMTP connectivity for an account using WordPress PHPMailer.
 */
class VMS_MSG_Health {

	/**
	 * Run a live SMTP connect + auth check and persist status.
	 *
	 * @param int $account_id Account ID.
	 * @return true|WP_Error
	 */
	public static function check_account( $account_id ) {
		$account_id = absint( $account_id );
		$account    = VMS_MSG_Accounts::get( $account_id, true );

		if ( ! $account ) {
			return new WP_Error( 'vms_msg_missing', __( 'SMTP account not found.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$creds = VMS_MSG_Accounts::get_credentials( $account );
		if ( is_wp_error( $creds ) ) {
			self::store_result( $account_id, 'fail', $creds->get_error_message() );
			return $creds;
		}

		if ( ! class_exists( 'PHPMailer\\PHPMailer\\PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		$mail = new PHPMailer\PHPMailer\PHPMailer( true );

		try {
			$mail->Timeout    = 15;
			$mail->isSMTP();
			$mail->Host       = $creds['host'];
			$mail->Port       = $creds['port'];
			$mail->SMTPAuth   = true;
			$mail->Username   = $creds['username'];
			$mail->Password   = $creds['password'];
			$mail->SMTPAutoTLS = true;

			switch ( $creds['encryption'] ) {
				case 'ssl':
					$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
					break;
				case 'none':
					$mail->SMTPSecure  = '';
					$mail->SMTPAutoTLS = false;
					break;
				case 'tls':
				default:
					$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
					break;
			}

			$connected = $mail->smtpConnect();
			if ( ! $connected ) {
				$msg = $mail->ErrorInfo ? $mail->ErrorInfo : __( 'SMTP connection failed.', 'vms-elements-multiple-smtp-email-gateway' );
				self::store_result( $account_id, 'fail', $msg );
				return new WP_Error( 'vms_msg_health_fail', $msg );
			}

			$mail->smtpClose();
			self::store_result( $account_id, 'ok', __( 'SMTP connection and authentication succeeded.', 'vms-elements-multiple-smtp-email-gateway' ) );
			return true;
		} catch ( Exception $e ) {
			$msg = $e->getMessage();
			self::store_result( $account_id, 'fail', $msg );
			return new WP_Error( 'vms_msg_health_fail', $msg );
		}
	}

	/**
	 * Persist health columns on the account row.
	 *
	 * @param int    $account_id Account ID.
	 * @param string $status     ok|fail|unknown.
	 * @param string $message    Status message.
	 * @return void
	 */
	public static function store_result( $account_id, $status, $message ) {
		global $wpdb;

		$account_id = absint( $account_id );
		$status     = sanitize_key( $status );
		if ( ! in_array( $status, array( 'ok', 'fail', 'unknown' ), true ) ) {
			$status = 'unknown';
		}

		$wpdb->update(
			VMS_MSG_Accounts::table_name(),
			array(
				'health_status'     => $status,
				'health_message'    => sanitize_textarea_field( $message ),
				'health_checked_at' => current_time( 'mysql' ),
			),
			array( 'id' => $account_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( 'fail' === $status ) {
			VMS_MSG_Alerts::maybe_health_fail( $account_id, $message );
		}
	}
}
