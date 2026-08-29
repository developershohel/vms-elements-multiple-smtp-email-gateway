<?php
/**
 * SMTP account data access layer.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * CRUD helpers for SMTP accounts stored in a custom table.
 */
class VMS_MSG_Accounts {

	/**
	 * Accounts table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'vms_msg_smtp_accounts';
	}

	/**
	 * Get all accounts ordered by name.
	 *
	 * @param bool $include_password Whether to include encrypted password field.
	 * @return array<int, object>
	 */
	public static function get_all( $include_password = false ) {
		global $wpdb;

		$table   = self::table_name();
		$columns = $include_password
			? '*'
			: 'id, account_name, provider, provider_meta, sender_email, smtp_host, smtp_port, smtp_encryption, smtp_username, is_default, force_from, daily_limit, fallback_priority, health_status, health_message, health_checked_at, created_at, updated_at';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal; columns are fixed.
		$results = $wpdb->get_results( "SELECT {$columns} FROM {$table} ORDER BY is_default DESC, account_name ASC" );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get a single account by ID.
	 *
	 * @param int  $id               Account ID.
	 * @param bool $include_password Include encrypted password.
	 * @return object|null
	 */
	public static function get( $id, $include_password = true ) {
		global $wpdb;

		$id    = absint( $id );
		$table = self::table_name();

		if ( $id < 1 ) {
			return null;
		}

		$columns = $include_password
			? '*'
			: 'id, account_name, provider, provider_meta, sender_email, smtp_host, smtp_port, smtp_encryption, smtp_username, is_default, force_from, daily_limit, fallback_priority, health_status, health_message, health_checked_at, created_at, updated_at';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT {$columns} FROM {$table} WHERE id = %d LIMIT 1",
				$id
			)
		);

		return $row ? $row : null;
	}

	/**
	 * Find account by sender (From) email. Exact, case-insensitive match.
	 *
	 * @param string $sender_email From address.
	 * @return object|null Full row including encrypted password.
	 */
	public static function get_by_sender_email( $sender_email ) {
		global $wpdb;

		$sender_email = sanitize_email( $sender_email );
		if ( ! is_email( $sender_email ) ) {
			return null;
		}

		$table = self::table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE LOWER(sender_email) = LOWER(%s) LIMIT 1",
				$sender_email
			)
		);

		return $row ? $row : null;
	}

	/**
	 * Get the global default SMTP account.
	 *
	 * @return object|null
	 */
	public static function get_default() {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( "SELECT * FROM {$table} WHERE is_default = 1 ORDER BY id ASC LIMIT 1" );

		return $row ? $row : null;
	}

	/**
	 * Accounts in the ordered fallback chain (priority > 0).
	 *
	 * @param int $exclude_id Optional account ID to skip.
	 * @return array<int, object>
	 */
	public static function get_fallback_chain( $exclude_id = 0 ) {
		global $wpdb;

		$table      = self::table_name();
		$exclude_id = absint( $exclude_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE fallback_priority > 0 AND id != %d ORDER BY fallback_priority ASC, id ASC",
				$exclude_id
			)
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Insert or update an account.
	 *
	 * @param array $data Sanitized account data.
	 * @param int   $id   Existing ID (0 = insert).
	 * @return int|WP_Error Account ID or error.
	 */
	public static function save( array $data, $id = 0 ) {
		global $wpdb;

		$id    = absint( $id );
		$table = self::table_name();
		$now   = current_time( 'mysql' );

		$account_name    = isset( $data['account_name'] ) ? sanitize_text_field( $data['account_name'] ) : '';
		$provider        = isset( $data['provider'] ) ? VMS_MSG_Providers::sanitize_id( $data['provider'] ) : 'other';
		$provider_meta   = isset( $data['provider_meta'] ) && is_array( $data['provider_meta'] ) ? $data['provider_meta'] : array();
		$sender_email    = isset( $data['sender_email'] ) ? sanitize_email( $data['sender_email'] ) : '';
		$smtp_host       = isset( $data['smtp_host'] ) ? sanitize_text_field( $data['smtp_host'] ) : '';
		$smtp_port       = isset( $data['smtp_port'] ) ? absint( $data['smtp_port'] ) : 587;
		$smtp_encryption = isset( $data['smtp_encryption'] ) ? sanitize_key( $data['smtp_encryption'] ) : 'tls';
		$smtp_username   = isset( $data['smtp_username'] ) ? sanitize_text_field( $data['smtp_username'] ) : '';
		$smtp_password   = isset( $data['smtp_password'] ) ? (string) $data['smtp_password'] : '';
		$is_default      = ! empty( $data['is_default'] ) ? 1 : 0;
		$force_from         = ! empty( $data['force_from'] ) ? 1 : 0;
		$daily_limit        = isset( $data['daily_limit'] ) ? absint( $data['daily_limit'] ) : 0;
		$fallback_priority  = isset( $data['fallback_priority'] ) ? absint( $data['fallback_priority'] ) : 0;
		if ( $fallback_priority > 9999 ) {
			$fallback_priority = 9999;
		}

		// Apply gateway defaults from the selected PHPMailer provider preset.
		$resolved = VMS_MSG_Providers::resolve_connection( $provider, $provider_meta );
		if ( 'other' !== $provider ) {
			if ( '' !== $resolved['host'] ) {
				$smtp_host = $resolved['host'];
			}
			$smtp_port       = $resolved['port'];
			$smtp_encryption = $resolved['encryption'];
		}
		if ( '' !== $resolved['username_lock'] ) {
			$smtp_username = $resolved['username_lock'];
		} elseif ( ! empty( $resolved['mirror_pass'] ) && '' !== $smtp_password ) {
			$smtp_username = $smtp_password;
		} elseif ( ! empty( $resolved['username_from_sender'] ) && '' === $smtp_username && is_email( $sender_email ) ) {
			$smtp_username = $sender_email;
		} elseif ( ! empty( $resolved['username_from_sender'] ) && is_email( $sender_email ) && ( '' === $smtp_username || strtolower( $smtp_username ) === strtolower( $sender_email ) ) ) {
			$smtp_username = $sender_email;
		}

		$meta_json = VMS_MSG_Providers::encode_meta( $provider_meta );

		if ( '' === $account_name ) {
			return new WP_Error( 'vms_msg_missing_name', __( 'Account name is required.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}
		if ( ! is_email( $sender_email ) ) {
			return new WP_Error( 'vms_msg_invalid_sender', __( 'A valid sender email is required.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}
		if ( '' === $smtp_host ) {
			return new WP_Error( 'vms_msg_missing_host', __( 'SMTP host is required.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}
		if ( $smtp_port < 1 || $smtp_port > 65535 ) {
			return new WP_Error( 'vms_msg_invalid_port', __( 'SMTP port must be between 1 and 65535.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}
		if ( ! in_array( $smtp_encryption, array( 'none', 'ssl', 'tls' ), true ) ) {
			$smtp_encryption = 'tls';
		}

		$duplicate = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE LOWER(sender_email) = LOWER(%s) AND id != %d LIMIT 1",
				$sender_email,
				$id
			)
		);
		if ( $duplicate ) {
			return new WP_Error(
				'vms_msg_duplicate_sender',
				__( 'Another SMTP account already uses this sender email.', 'vms-elements-multiple-smtp-email-gateway' )
			);
		}

		$row = array(
			'account_name'    => $account_name,
			'provider'        => $provider,
			'provider_meta'   => $meta_json ? $meta_json : '{}',
			'sender_email'    => $sender_email,
			'smtp_host'       => $smtp_host,
			'smtp_port'       => $smtp_port,
			'smtp_encryption' => $smtp_encryption,
			'smtp_username'   => $smtp_username,
			'force_from'         => $force_from,
			'daily_limit'        => $daily_limit,
			'fallback_priority'  => $fallback_priority,
			'updated_at'         => $now,
		);
		$formats = array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s' );

		if ( $id > 0 ) {
			if ( '' !== $smtp_password ) {
				$encrypted = VMS_MSG_Encryption::encrypt( $smtp_password );
				if ( '' === $encrypted ) {
					return new WP_Error( 'vms_msg_encrypt_failed', __( 'Could not encrypt SMTP password.', 'vms-elements-multiple-smtp-email-gateway' ) );
				}
				$row['smtp_password'] = $encrypted;
				$formats[]            = '%s';
			}

			$updated = $wpdb->update( $table, $row, array( 'id' => $id ), $formats, array( '%d' ) );
			if ( false === $updated ) {
				return new WP_Error( 'vms_msg_db_error', __( 'Failed to update SMTP account.', 'vms-elements-multiple-smtp-email-gateway' ) );
			}

			if ( $is_default ) {
				self::set_default( $id );
			}

			return $id;
		}

		if ( '' === $smtp_password ) {
			return new WP_Error( 'vms_msg_missing_password', __( 'SMTP password is required for new accounts.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$encrypted = VMS_MSG_Encryption::encrypt( $smtp_password );
		if ( '' === $encrypted ) {
			return new WP_Error( 'vms_msg_encrypt_failed', __( 'Could not encrypt SMTP password.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$row['smtp_password'] = $encrypted;
		$row['is_default']    = 0;
		$row['created_at']    = $now;
		$formats[]            = '%s';
		$formats[]            = '%d';
		$formats[]            = '%s';

		$inserted = $wpdb->insert( $table, $row, $formats );
		if ( ! $inserted ) {
			return new WP_Error( 'vms_msg_db_error', __( 'Failed to create SMTP account.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$new_id = (int) $wpdb->insert_id;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $is_default || 1 === $count ) {
			self::set_default( $new_id );
		}

		return $new_id;
	}

	/**
	 * Delete an account by ID.
	 *
	 * @param int $id Account ID.
	 * @return true|WP_Error
	 */
	public static function delete( $id ) {
		global $wpdb;

		$id    = absint( $id );
		$table = self::table_name();

		if ( $id < 1 ) {
			return new WP_Error( 'vms_msg_invalid_id', __( 'Invalid account ID.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$account = self::get( $id, false );
		if ( ! $account ) {
			return new WP_Error( 'vms_msg_not_found', __( 'SMTP account not found.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
		if ( false === $deleted ) {
			return new WP_Error( 'vms_msg_db_error', __( 'Failed to delete SMTP account.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		if ( ! empty( $account->is_default ) ) {
			$next = $wpdb->get_var( "SELECT id FROM {$table} ORDER BY id ASC LIMIT 1" );
			if ( $next ) {
				self::set_default( (int) $next );
			}
		}

		return true;
	}

	/**
	 * Mark one account as the global default (clears others).
	 *
	 * @param int $id Account ID.
	 * @return true|WP_Error
	 */
	public static function set_default( $id ) {
		global $wpdb;

		$id    = absint( $id );
		$table = self::table_name();

		if ( $id < 1 || ! self::get( $id, false ) ) {
			return new WP_Error( 'vms_msg_invalid_id', __( 'Invalid account ID.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "UPDATE {$table} SET is_default = 0" );
		$wpdb->update( $table, array( 'is_default' => 1, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ), array( '%d', '%s' ), array( '%d' ) );

		return true;
	}

	/**
	 * Resolve decrypted credentials for PHPMailer.
	 *
	 * @param object $account Account row with encrypted password.
	 * @return array|WP_Error
	 */
	public static function get_credentials( $account ) {
		if ( ! is_object( $account ) ) {
			return new WP_Error( 'vms_msg_invalid_account', __( 'Invalid SMTP account.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$password = VMS_MSG_Encryption::decrypt( isset( $account->smtp_password ) ? $account->smtp_password : '' );
		if ( '' === $password && '' !== (string) $account->smtp_password ) {
			return new WP_Error( 'vms_msg_decrypt_failed', __( 'Could not decrypt SMTP password. Re-save the account password.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		return array(
			'id'         => (int) $account->id,
			'name'       => (string) $account->account_name,
			'provider'   => isset( $account->provider ) ? (string) $account->provider : 'other',
			'from_email' => (string) $account->sender_email,
			'host'       => (string) $account->smtp_host,
			'port'       => (int) $account->smtp_port,
			'encryption' => (string) $account->smtp_encryption,
			'username'   => (string) $account->smtp_username,
			'password'   => $password,
			'is_default' => ! empty( $account->is_default ),
		);
	}
}
