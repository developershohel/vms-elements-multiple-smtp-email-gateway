<?php
/**
 * Optional background mail queue.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores wp_mail payloads and sends them via WP-Cron (or Action Scheduler if present).
 */
class VMS_MSG_Queue {

	/**
	 * Whether a queue worker is currently dispatching.
	 *
	 * @var bool
	 */
	private static $processing = false;

	/**
	 * Hook queue filters / cron.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'pre_wp_mail', array( __CLASS__, 'maybe_enqueue' ), 1, 2 );
		add_action( 'vms_msg_process_queue', array( __CLASS__, 'process_batch' ) );
		add_action( 'init', array( __CLASS__, 'maybe_schedule' ) );

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			add_action( 'vms_msg_as_process_queue', array( __CLASS__, 'process_batch' ) );
		}
	}

	/**
	 * Queue table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'vms_msg_mail_queue';
	}

	/**
	 * Ensure cron is scheduled when queue is enabled.
	 *
	 * @return void
	 */
	public static function maybe_schedule() {
		if ( ! VMS_MSG_Settings::get( 'queue_enabled', 0 ) ) {
			return;
		}

		if ( ! wp_next_scheduled( 'vms_msg_process_queue' ) ) {
			wp_schedule_event( time() + 60, 'vms_msg_every_minute', 'vms_msg_process_queue' );
		}
	}

	/**
	 * Register a one-minute cron schedule.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public static function cron_schedules( $schedules ) {
		if ( ! isset( $schedules['vms_msg_every_minute'] ) ) {
			$schedules['vms_msg_every_minute'] = array(
				'interval' => 60,
				'display'  => __( 'Every minute (VMS Multi Mailer)', 'vms-elements-multiple-smtp-email-gateway' ),
			);
		}
		return $schedules;
	}

	/**
	 * Intercept wp_mail and enqueue when enabled.
	 *
	 * @param mixed $null Short-circuit.
	 * @param array $atts Mail args.
	 * @return mixed
	 */
	public static function maybe_enqueue( $null, $atts ) {
		if ( null !== $null ) {
			return $null;
		}

		if ( ! VMS_MSG_Settings::get( 'queue_enabled', 0 ) ) {
			return null;
		}

		if ( self::$processing || ! empty( $GLOBALS['vms_msg_bypass_queue'] ) ) {
			return null;
		}

		// Do not queue forced test/resend or failover retries mid-flight.
		if ( VMS_MSG_Mailer::is_forced() ) {
			return null;
		}

		$id = self::enqueue( is_array( $atts ) ? $atts : array() );
		if ( ! $id ) {
			return null;
		}

		VMS_MSG_Mailer::clear_pending_capture();
		self::kick();

		// Short-circuit: caller sees success; actual send happens in worker.
		return true;
	}

	/**
	 * Store a mail payload.
	 *
	 * @param array $atts wp_mail atts.
	 * @return int|false
	 */
	public static function enqueue( array $atts ) {
		global $wpdb;

		$payload = wp_json_encode(
			array(
				'to'          => isset( $atts['to'] ) ? $atts['to'] : '',
				'subject'     => isset( $atts['subject'] ) ? $atts['subject'] : '',
				'message'     => isset( $atts['message'] ) ? $atts['message'] : '',
				'headers'     => isset( $atts['headers'] ) ? $atts['headers'] : '',
				'attachments' => isset( $atts['attachments'] ) ? $atts['attachments'] : array(),
			)
		);

		if ( ! $payload ) {
			return false;
		}

		$inserted = $wpdb->insert(
			self::table_name(),
			array(
				'payload'     => $payload,
				'status'      => 'pending',
				'attempts'    => 0,
				'created_at'  => current_time( 'mysql' ),
				'updated_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Trigger async processing soon.
	 *
	 * @return void
	 */
	public static function kick() {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( ! as_has_scheduled_action( 'vms_msg_as_process_queue' ) ) {
				as_enqueue_async_action( 'vms_msg_as_process_queue' );
			}
			return;
		}

		if ( ! wp_next_scheduled( 'vms_msg_process_queue' ) ) {
			wp_schedule_single_event( time() + 5, 'vms_msg_process_queue' );
		}
	}

	/**
	 * Process a batch of queued mails.
	 *
	 * @return int Number processed.
	 */
	public static function process_batch() {
		global $wpdb;

		if ( ! VMS_MSG_Settings::get( 'queue_enabled', 0 ) ) {
			return 0;
		}

		$table = self::table_name();
		$limit = (int) VMS_MSG_Settings::get( 'queue_batch_size', 10 );
		if ( $limit < 1 ) {
			$limit = 10;
		}
		if ( $limit > 50 ) {
			$limit = 50;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = %s ORDER BY id ASC LIMIT %d",
				'pending',
				$limit
			)
		);

		if ( empty( $rows ) ) {
			return 0;
		}

		$done = 0;
		foreach ( $rows as $row ) {
			$wpdb->update(
				$table,
				array(
					'status'     => 'processing',
					'attempts'   => (int) $row->attempts + 1,
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => (int) $row->id ),
				array( '%s', '%d', '%s' ),
				array( '%d' )
			);

			$data = json_decode( $row->payload, true );
			if ( ! is_array( $data ) ) {
				$wpdb->update(
					$table,
					array(
						'status'     => 'failed',
						'last_error' => 'Invalid payload',
						'updated_at' => current_time( 'mysql' ),
					),
					array( 'id' => (int) $row->id ),
					array( '%s', '%s', '%s' ),
					array( '%d' )
				);
				continue;
			}

			self::$processing = true;
			$ok               = wp_mail(
				isset( $data['to'] ) ? $data['to'] : '',
				isset( $data['subject'] ) ? $data['subject'] : '',
				isset( $data['message'] ) ? $data['message'] : '',
				isset( $data['headers'] ) ? $data['headers'] : '',
				isset( $data['attachments'] ) ? $data['attachments'] : array()
			);
			self::$processing = false;

			$wpdb->update(
				$table,
				array(
					'status'     => $ok ? 'sent' : 'failed',
					'last_error' => $ok ? '' : __( 'wp_mail returned false', 'vms-elements-multiple-smtp-email-gateway' ),
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => (int) $row->id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
			++$done;
		}

		// Continue if more pending.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remaining = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'pending' ) );
		if ( $remaining > 0 ) {
			self::kick();
		}

		return $done;
	}

	/**
	 * Count pending queue items.
	 *
	 * @return int
	 */
	public static function count_pending() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'pending' ) );
	}
}
