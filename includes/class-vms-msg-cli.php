<?php
/**
 * WP-CLI commands.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage VMS Multi Mailer from the command line.
 */
class VMS_MSG_CLI {

	/**
	 * Run smoke tests.
	 *
	 * ## OPTIONS
	 *
	 * [--send-test]
	 * : Also send a live test email via the default account.
	 *
	 * [--to=<email>]
	 * : Recipient for --send-test (defaults to admin email).
	 *
	 * ## EXAMPLES
	 *
	 *     wp vms-msg test
	 *     wp vms-msg test --send-test --to=you@example.com
	 *
	 * @param array $args       Positional.
	 * @param array $assoc_args Flags.
	 * @return void
	 */
	public function test( $args, $assoc_args ) {
		$send = isset( $assoc_args['send-test'] );
		$to   = isset( $assoc_args['to'] ) ? $assoc_args['to'] : '';
		$out  = VMS_MSG_Smoke_Test::run( $send, $to );

		foreach ( $out['steps'] as $step ) {
			$prefix = ( 'pass' === $step['status'] ) ? 'OK' : 'FAIL';
			WP_CLI::log( sprintf( '[%s] %s — %s', $prefix, $step['label'], $step['detail'] ) );
		}

		if ( $out['ok'] ) {
			WP_CLI::success( 'Smoke test passed.' );
		} else {
			WP_CLI::error( 'Smoke test failed.' );
		}
	}

	/**
	 * Prune old email logs using retention settings.
	 *
	 * ## EXAMPLES
	 *
	 *     wp vms-msg prune
	 *
	 * @return void
	 */
	public function prune() {
		$deleted = VMS_MSG_Maintenance::prune_logs();
		WP_CLI::success( sprintf( 'Deleted %d log row(s).', (int) $deleted ) );
	}

	/**
	 * Run SMTP health check for an account.
	 *
	 * ## OPTIONS
	 *
	 * [--id=<id>]
	 * : Account ID. Defaults to the global default account.
	 *
	 * ## EXAMPLES
	 *
	 *     wp vms-msg health
	 *     wp vms-msg health --id=3
	 *
	 * @param array $args       Positional.
	 * @param array $assoc_args Flags.
	 * @return void
	 */
	public function health( $args, $assoc_args ) {
		$id = isset( $assoc_args['id'] ) ? absint( $assoc_args['id'] ) : 0;
		if ( $id < 1 ) {
			$default = VMS_MSG_Accounts::get_default();
			if ( ! $default ) {
				WP_CLI::error( 'No default account found.' );
			}
			$id = (int) $default->id;
		}

		$result = VMS_MSG_Health::check_account( $id );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( sprintf( 'Account #%d is healthy.', $id ) );
	}
}

WP_CLI::add_command( 'vms-msg', 'VMS_MSG_CLI' );
