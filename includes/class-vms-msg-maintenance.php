<?php
/**
 * Daily maintenance (log retention + alerts).
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Runs scheduled cleanup jobs.
 */
class VMS_MSG_Maintenance {

	/**
	 * Register cron callback.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'vms_msg_daily_maintenance', array( __CLASS__, 'run' ) );
		add_action( 'vms_msg_hourly_alerts', array( 'VMS_MSG_Alerts', 'maybe_failure_spike' ) );
		add_filter( 'cron_schedules', array( 'VMS_MSG_Queue', 'cron_schedules' ) );
	}

	/**
	 * Ensure the daily event is scheduled.
	 *
	 * @return void
	 */
	public static function maybe_schedule() {
		if ( ! wp_next_scheduled( 'vms_msg_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'vms_msg_daily_maintenance' );
		}
		if ( ! wp_next_scheduled( 'vms_msg_hourly_alerts' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'vms_msg_hourly_alerts' );
		}
	}

	/**
	 * Cron entry point.
	 *
	 * @return void
	 */
	public static function run() {
		self::prune_logs();
		VMS_MSG_Alerts::maybe_failure_spike();
	}

	/**
	 * Delete logs older than the retention window.
	 *
	 * @return int Number of rows deleted.
	 */
	public static function prune_logs() {
		global $wpdb;

		$days = (int) VMS_MSG_Settings::get( 'log_retention_days', 30 );
		if ( $days < 1 ) {
			return 0;
		}

		$table        = VMS_MSG_Logger::table_name();
		$cutoff_local = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE date_time < %s",
				$cutoff_local
			)
		);

		return false === $deleted ? 0 : (int) $deleted;
	}
}
