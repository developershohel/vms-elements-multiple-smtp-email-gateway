<?php
/**
 * Activation / deactivation routines.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates custom tables and stores schema version.
 */
class VMS_MSG_Activator {

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		update_option( 'vms_msg_db_version', VMS_MSG_DB_VERSION );

		if ( ! get_option( VMS_MSG_Settings::OPTION_KEY ) ) {
			update_option( VMS_MSG_Settings::OPTION_KEY, VMS_MSG_Settings::defaults(), false );
		}

		VMS_MSG_Capabilities::install();

		if ( ! wp_next_scheduled( 'vms_msg_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'vms_msg_daily_maintenance' );
		}
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'vms_msg_daily_maintenance' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'vms_msg_daily_maintenance' );
		}

		$queue_ts = wp_next_scheduled( 'vms_msg_process_queue' );
		if ( $queue_ts ) {
			wp_unschedule_event( $queue_ts, 'vms_msg_process_queue' );
		}

		$alert_ts = wp_next_scheduled( 'vms_msg_hourly_alerts' );
		if ( $alert_ts ) {
			wp_unschedule_event( $alert_ts, 'vms_msg_hourly_alerts' );
		}
	}

	/**
	 * Create or upgrade custom database tables.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$accounts_table  = $wpdb->prefix . 'vms_msg_smtp_accounts';
		$logs_table      = $wpdb->prefix . 'vms_msg_email_logs';
		$queue_table     = $wpdb->prefix . 'vms_msg_mail_queue';

		$sql_accounts = "CREATE TABLE {$accounts_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			account_name varchar(191) NOT NULL DEFAULT '',
			provider varchar(50) NOT NULL DEFAULT 'other',
			provider_meta longtext NOT NULL,
			sender_email varchar(191) NOT NULL DEFAULT '',
			smtp_host varchar(191) NOT NULL DEFAULT '',
			smtp_port smallint(5) unsigned NOT NULL DEFAULT 587,
			smtp_encryption varchar(20) NOT NULL DEFAULT 'tls',
			smtp_username varchar(191) NOT NULL DEFAULT '',
			smtp_password longtext NOT NULL,
			is_default tinyint(1) NOT NULL DEFAULT 0,
			force_from tinyint(1) NOT NULL DEFAULT 0,
			daily_limit int(10) unsigned NOT NULL DEFAULT 0,
			fallback_priority smallint(5) unsigned NOT NULL DEFAULT 0,
			health_status varchar(20) NOT NULL DEFAULT 'unknown',
			health_message text NOT NULL,
			health_checked_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY sender_email (sender_email),
			KEY provider (provider),
			KEY is_default (is_default),
			KEY fallback_priority (fallback_priority),
			KEY health_status (health_status)
		) {$charset_collate};";

		$sql_logs = "CREATE TABLE {$logs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			to_email text NOT NULL,
			subject text NOT NULL,
			message_body longtext NOT NULL,
			headers longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'failed',
			error_message text NOT NULL,
			used_smtp_account varchar(191) NOT NULL DEFAULT '',
			smtp_account_id bigint(20) unsigned DEFAULT NULL,
			is_resend tinyint(1) NOT NULL DEFAULT 0,
			parent_log_id bigint(20) unsigned DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY date_time (date_time),
			KEY smtp_account_id (smtp_account_id),
			KEY parent_log_id (parent_log_id)
		) {$charset_collate};";

		$sql_queue = "CREATE TABLE {$queue_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			payload longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			last_error text NOT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_accounts );
		dbDelta( $sql_logs );
		dbDelta( $sql_queue );
	}

	/**
	 * Ensure schema exists (for upgrades without re-activation).
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$installed = get_option( 'vms_msg_db_version', '' );
		if ( VMS_MSG_DB_VERSION !== $installed ) {
			self::create_tables();
			VMS_MSG_Capabilities::install();
			update_option( 'vms_msg_db_version', VMS_MSG_DB_VERSION );
		}
	}
}
