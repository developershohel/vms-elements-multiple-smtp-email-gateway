<?php
/**
 * SMTP account import / export (JSON, no passwords).
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Moves account configuration between sites without exporting secrets.
 */
class VMS_MSG_Import_Export {

	/**
	 * Build export array (passwords omitted).
	 *
	 * @return array
	 */
	public static function export_data() {
		$accounts = array();
		foreach ( VMS_MSG_Accounts::get_all( false ) as $row ) {
			$accounts[] = array(
				'account_name'      => $row->account_name,
				'provider'          => isset( $row->provider ) ? $row->provider : 'other',
				'provider_meta'     => isset( $row->provider_meta ) ? VMS_MSG_Providers::decode_meta( $row->provider_meta ) : array(),
				'sender_email'      => $row->sender_email,
				'smtp_host'         => $row->smtp_host,
				'smtp_port'         => (int) $row->smtp_port,
				'smtp_encryption'   => $row->smtp_encryption,
				'smtp_username'     => $row->smtp_username,
				'is_default'        => ! empty( $row->is_default ) ? 1 : 0,
				'force_from'        => ! empty( $row->force_from ) ? 1 : 0,
				'daily_limit'       => isset( $row->daily_limit ) ? (int) $row->daily_limit : 0,
				'fallback_priority' => isset( $row->fallback_priority ) ? (int) $row->fallback_priority : 0,
			);
		}

		return array(
			'plugin'   => 'vms-elements-multiple-smtp-email-gateway',
			'version'  => VMS_MSG_VERSION,
			'exported' => gmdate( 'c' ),
			'accounts' => $accounts,
			'note'     => 'Passwords are not included. Re-enter SMTP passwords after import.',
		);
	}

	/**
	 * Stream JSON download.
	 *
	 * @return void
	 */
	public static function download_export() {
		$data = self::export_data();
		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( ! $json ) {
			wp_die( esc_html__( 'Could not build export.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$filename = 'vms-multi-mailer-accounts-' . gmdate( 'Y-m-d-His' ) . '.json';
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Import accounts from decoded JSON (passwords left empty — accounts need re-save).
	 *
	 * @param array $data Decoded JSON.
	 * @return array{imported:int,skipped:int,errors:array<int,string>}
	 */
	public static function import_data( array $data ) {
		$imported = 0;
		$skipped  = 0;
		$errors   = array();

		$accounts = isset( $data['accounts'] ) && is_array( $data['accounts'] ) ? $data['accounts'] : array();
		if ( empty( $accounts ) ) {
			return array(
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => array( __( 'No accounts found in import file.', 'vms-elements-multiple-smtp-email-gateway' ) ),
			);
		}

		foreach ( $accounts as $index => $row ) {
			if ( ! is_array( $row ) ) {
				++$skipped;
				continue;
			}

			$sender = isset( $row['sender_email'] ) ? sanitize_email( $row['sender_email'] ) : '';
			if ( is_email( $sender ) && VMS_MSG_Accounts::get_by_sender_email( $sender ) ) {
				$errors[] = sprintf(
					/* translators: %s: email */
					__( 'Skipped %s — sender already exists.', 'vms-elements-multiple-smtp-email-gateway' ),
					$sender
				);
				++$skipped;
				continue;
			}

			// Placeholder password; user must edit account and set real credentials.
			$result = VMS_MSG_Accounts::save(
				array(
					'account_name'      => isset( $row['account_name'] ) ? $row['account_name'] : '',
					'provider'          => isset( $row['provider'] ) ? $row['provider'] : 'other',
					'provider_meta'     => isset( $row['provider_meta'] ) && is_array( $row['provider_meta'] ) ? $row['provider_meta'] : array(),
					'sender_email'      => $sender,
					'smtp_host'         => isset( $row['smtp_host'] ) ? $row['smtp_host'] : '',
					'smtp_port'         => isset( $row['smtp_port'] ) ? $row['smtp_port'] : 587,
					'smtp_encryption'   => isset( $row['smtp_encryption'] ) ? $row['smtp_encryption'] : 'tls',
					'smtp_username'     => isset( $row['smtp_username'] ) ? $row['smtp_username'] : '',
					'smtp_password'     => 'IMPORT_PLACEHOLDER_CHANGE_ME',
					'is_default'        => ! empty( $row['is_default'] ),
					'force_from'        => ! empty( $row['force_from'] ),
					'daily_limit'       => isset( $row['daily_limit'] ) ? $row['daily_limit'] : 0,
					'fallback_priority' => isset( $row['fallback_priority'] ) ? $row['fallback_priority'] : 0,
				),
				0
			);

			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf(
					/* translators: 1: row number, 2: error */
					__( 'Row %1$d: %2$s', 'vms-elements-multiple-smtp-email-gateway' ),
					(int) $index + 1,
					$result->get_error_message()
				);
				++$skipped;
				continue;
			}

			++$imported;
		}

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}
}
