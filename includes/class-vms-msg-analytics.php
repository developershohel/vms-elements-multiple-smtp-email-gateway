<?php
/**
 * Email analytics helpers.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Aggregates send / fail stats for the dashboard.
 */
class VMS_MSG_Analytics {

	/**
	 * Count logs by status since a local datetime.
	 *
	 * @param string $status sent|failed|''.
	 * @param string $since  MySQL datetime.
	 * @return int
	 */
	public static function count_since( $status, $since ) {
		global $wpdb;

		$table = VMS_MSG_Logger::table_name();
		$since = sanitize_text_field( $since );

		if ( in_array( $status, array( 'sent', 'failed' ), true ) ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE status = %s AND date_time >= %s",
					$status,
					$since
				)
			);
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE date_time >= %s",
				$since
			)
		);
	}

	/**
	 * Dashboard summary payload.
	 *
	 * @return array
	 */
	public static function summary() {
		$now_ts   = current_time( 'timestamp' );
		$since_7  = date_i18n( 'Y-m-d H:i:s', $now_ts - ( 7 * DAY_IN_SECONDS ) );
		$since_30 = date_i18n( 'Y-m-d H:i:s', $now_ts - ( 30 * DAY_IN_SECONDS ) );
		$today    = date_i18n( 'Y-m-d 00:00:00' );

		return array(
			'sent_7'      => self::count_since( 'sent', $since_7 ),
			'failed_7'    => self::count_since( 'failed', $since_7 ),
			'sent_30'     => self::count_since( 'sent', $since_30 ),
			'failed_30'   => self::count_since( 'failed', $since_30 ),
			'sent_today'  => self::count_since( 'sent', $today ),
			'failed_today'=> self::count_since( 'failed', $today ),
			'top_failures'=> self::top_failing_accounts( 5, $since_7 ),
			'usage'       => self::account_usage_today(),
		);
	}

	/**
	 * Top failing accounts in a window.
	 *
	 * @param int    $limit Max rows.
	 * @param string $since Start datetime.
	 * @return array<int, object>
	 */
	public static function top_failing_accounts( $limit = 5, $since = '' ) {
		global $wpdb;

		$table = VMS_MSG_Logger::table_name();
		$limit = max( 1, absint( $limit ) );
		if ( '' === $since ) {
			$since = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( 7 * DAY_IN_SECONDS ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT smtp_account_id, used_smtp_account, COUNT(*) AS fail_count
				FROM {$table}
				WHERE status = %s AND date_time >= %s AND smtp_account_id IS NOT NULL
				GROUP BY smtp_account_id, used_smtp_account
				ORDER BY fail_count DESC
				LIMIT %d",
				'failed',
				$since,
				$limit
			)
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Today's usage vs limits for each account.
	 *
	 * @return array<int, array>
	 */
	public static function account_usage_today() {
		$out = array();
		foreach ( VMS_MSG_Accounts::get_all( false ) as $account ) {
			$limit = isset( $account->daily_limit ) ? (int) $account->daily_limit : 0;
			$sent  = VMS_MSG_Logger::count_sent_today( (int) $account->id );
			$out[] = array(
				'id'    => (int) $account->id,
				'name'  => (string) $account->account_name,
				'sent'  => $sent,
				'limit' => $limit,
			);
		}
		return $out;
	}

	/**
	 * Count failures in the last N minutes (for spike alerts).
	 *
	 * @param int $minutes Window.
	 * @return int
	 */
	public static function failures_in_minutes( $minutes = 60 ) {
		$minutes = max( 1, absint( $minutes ) );
		$since   = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $minutes * MINUTE_IN_SECONDS ) );
		return self::count_since( 'failed', $since );
	}
}
