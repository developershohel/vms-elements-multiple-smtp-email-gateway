<?php
/**
 * Built-in smoke test suite.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Runs environment and configuration checks (optional live test email).
 */
class VMS_MSG_Smoke_Test {

	/**
	 * Run smoke tests.
	 *
	 * @param bool   $send_test Whether to send a real test email.
	 * @param string $to_email  Recipient for live test.
	 * @return array{ok:bool,steps:array<int,array{label:string,status:string,detail:string}>}
	 */
	public static function run( $send_test = false, $to_email = '' ) {
		$steps = array();

		$steps[] = self::step(
			__( 'Plugin tables', 'vms-elements-multiple-smtp-email-gateway' ),
			self::check_tables()
		);

		$steps[] = self::step(
			__( 'OpenSSL available', 'vms-elements-multiple-smtp-email-gateway' ),
			function_exists( 'openssl_encrypt' )
				? array( true, __( 'OpenSSL encryption is available.', 'vms-elements-multiple-smtp-email-gateway' ) )
				: array( false, __( 'OpenSSL is missing; password encryption will fail.', 'vms-elements-multiple-smtp-email-gateway' ) )
		);

		$accounts = VMS_MSG_Accounts::get_all( false );
		$steps[]  = self::step(
			__( 'SMTP accounts configured', 'vms-elements-multiple-smtp-email-gateway' ),
			count( $accounts ) > 0
				? array( true, sprintf( /* translators: %d: count */ __( '%d account(s) found.', 'vms-elements-multiple-smtp-email-gateway' ), count( $accounts ) ) )
				: array( false, __( 'No SMTP accounts yet. Add at least one account.', 'vms-elements-multiple-smtp-email-gateway' ) )
		);

		$default = VMS_MSG_Accounts::get_default();
		$steps[] = self::step(
			__( 'Global default account', 'vms-elements-multiple-smtp-email-gateway' ),
			$default
				? array( true, $default->account_name )
				: array( false, __( 'No global default account set.', 'vms-elements-multiple-smtp-email-gateway' ) )
		);

		$conflicts = VMS_MSG_Conflicts::get_active_conflicts();
		$steps[]   = self::step(
			__( 'SMTP plugin conflicts', 'vms-elements-multiple-smtp-email-gateway' ),
			empty( $conflicts )
				? array( true, __( 'No known conflicting mail plugins active.', 'vms-elements-multiple-smtp-email-gateway' ) )
				: array( false, implode( ', ', $conflicts ) )
		);

		if ( $default ) {
			$health = VMS_MSG_Health::check_account( (int) $default->id );
			$steps[] = self::step(
				__( 'Default account health', 'vms-elements-multiple-smtp-email-gateway' ),
				is_wp_error( $health )
					? array( false, $health->get_error_message() )
					: array( true, __( 'SMTP connect/auth succeeded.', 'vms-elements-multiple-smtp-email-gateway' ) )
			);
		}

		if ( $send_test && $default ) {
			$to = sanitize_email( $to_email );
			if ( ! is_email( $to ) ) {
				$to = sanitize_email( get_option( 'admin_email' ) );
			}
			$result  = VMS_MSG_Resend::send_test( (int) $default->id, $to );
			$steps[] = self::step(
				__( 'Live test email', 'vms-elements-multiple-smtp-email-gateway' ),
				is_wp_error( $result )
					? array( false, $result->get_error_message() )
					: array( true, sprintf( /* translators: %s: email */ __( 'Test sent to %s. Check Email Logs.', 'vms-elements-multiple-smtp-email-gateway' ), $to ) )
			);
		}

		$ok = true;
		foreach ( $steps as $step ) {
			if ( 'fail' === $step['status'] ) {
				$ok = false;
				break;
			}
		}

		return array(
			'ok'    => $ok,
			'steps' => $steps,
		);
	}

	/**
	 * Format a step.
	 *
	 * @param string        $label Label.
	 * @param array{0:bool,1:string}|true|false $result Result.
	 * @return array{label:string,status:string,detail:string}
	 */
	private static function step( $label, $result ) {
		if ( is_array( $result ) ) {
			$pass   = ! empty( $result[0] );
			$detail = isset( $result[1] ) ? (string) $result[1] : '';
		} else {
			$pass   = (bool) $result;
			$detail = '';
		}

		return array(
			'label'  => $label,
			'status' => $pass ? 'pass' : 'fail',
			'detail' => $detail,
		);
	}

	/**
	 * Ensure custom tables exist.
	 *
	 * @return array{0:bool,1:string}
	 */
	private static function check_tables() {
		global $wpdb;

		$accounts = VMS_MSG_Accounts::table_name();
		$logs     = VMS_MSG_Logger::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$a = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $accounts ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$l = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $logs ) );

		if ( $a === $accounts && $l === $logs ) {
			return array( true, __( 'Accounts and logs tables exist.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		VMS_MSG_Activator::create_tables();
		return array( false, __( 'Tables were missing; schema repair was attempted. Re-run the smoke test.', 'vms-elements-multiple-smtp-email-gateway' ) );
	}
}
