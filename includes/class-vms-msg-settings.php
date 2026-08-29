<?php
/**
 * Plugin settings helpers.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores global plugin options.
 */
class VMS_MSG_Settings {

	const OPTION_KEY    = 'vms_msg_settings';
	const DEBUG_LOG_KEY = 'vms_msg_smtp_debug_log';

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'log_retention_days'      => 30,
			'log_body_mode'           => 'full',
			'log_body_max_chars'      => 5000,
			'log_redact_body'         => 0,
			'smtp_debug'              => 0,
			'smtp_debug_until'        => 0,
			'failure_failover'        => 1,
			'queue_enabled'           => 0,
			'queue_batch_size'        => 10,
			'alert_email'             => '',
			'alert_on_health_fail'    => 1,
			'alert_on_failure_spike'  => 1,
			'failure_spike_threshold' => 10,
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_all() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Get one setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::get_all();
		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}
		return $default;
	}

	/**
	 * Whether SMTP debug capture is currently active.
	 *
	 * @return bool
	 */
	public static function is_smtp_debug_active() {
		$all = self::get_all();
		if ( empty( $all['smtp_debug'] ) ) {
			return false;
		}
		$until = isset( $all['smtp_debug_until'] ) ? (int) $all['smtp_debug_until'] : 0;
		if ( $until > 0 && time() > $until ) {
			$all['smtp_debug']       = 0;
			$all['smtp_debug_until'] = 0;
			update_option( self::OPTION_KEY, $all, false );
			return false;
		}
		return true;
	}

	/**
	 * Append a line to the SMTP debug log (size-capped).
	 *
	 * @param string $line Debug line.
	 * @return void
	 */
	public static function append_debug_log( $line ) {
		$line = trim( (string) $line );
		if ( '' === $line ) {
			return;
		}

		$stamp   = current_time( 'mysql' );
		$entry   = '[' . $stamp . '] ' . $line . "\n";
		$current = (string) get_option( self::DEBUG_LOG_KEY, '' );
		$current = $entry . $current;

		if ( strlen( $current ) > 100000 ) {
			$current = substr( $current, 0, 100000 );
		}

		update_option( self::DEBUG_LOG_KEY, $current, false );
	}

	/**
	 * Get debug log text.
	 *
	 * @return string
	 */
	public static function get_debug_log() {
		return (string) get_option( self::DEBUG_LOG_KEY, '' );
	}

	/**
	 * Clear debug log.
	 *
	 * @return void
	 */
	public static function clear_debug_log() {
		delete_option( self::DEBUG_LOG_KEY );
	}

	/**
	 * Save settings from sanitized input.
	 *
	 * @param array $input Raw input.
	 * @return array Saved settings.
	 */
	public static function save( array $input ) {
		$current = self::get_all();

		$days = isset( $input['log_retention_days'] ) ? absint( $input['log_retention_days'] ) : (int) $current['log_retention_days'];
		if ( $days > 3650 ) {
			$days = 3650;
		}

		$mode = isset( $input['log_body_mode'] ) ? sanitize_key( $input['log_body_mode'] ) : $current['log_body_mode'];
		if ( ! in_array( $mode, array( 'full', 'truncate', 'omit' ), true ) ) {
			$mode = 'full';
		}

		$max = isset( $input['log_body_max_chars'] ) ? absint( $input['log_body_max_chars'] ) : (int) $current['log_body_max_chars'];
		if ( $max < 500 ) {
			$max = 500;
		}
		if ( $max > 200000 ) {
			$max = 200000;
		}

		$redact = ! empty( $input['log_redact_body'] ) ? 1 : 0;
		$debug  = ! empty( $input['smtp_debug'] ) ? 1 : 0;
		$until  = 0;
		if ( $debug ) {
			$until = ! empty( $current['smtp_debug_until'] ) && (int) $current['smtp_debug_until'] > time()
				? (int) $current['smtp_debug_until']
				: time() + HOUR_IN_SECONDS;
		}

		$batch = isset( $input['queue_batch_size'] ) ? absint( $input['queue_batch_size'] ) : (int) $current['queue_batch_size'];
		if ( $batch < 1 ) {
			$batch = 1;
		}
		if ( $batch > 50 ) {
			$batch = 50;
		}

		$spike = isset( $input['failure_spike_threshold'] ) ? absint( $input['failure_spike_threshold'] ) : (int) $current['failure_spike_threshold'];
		if ( $spike < 1 ) {
			$spike = 1;
		}
		if ( $spike > 10000 ) {
			$spike = 10000;
		}

		$alert_email = isset( $input['alert_email'] ) ? sanitize_email( $input['alert_email'] ) : $current['alert_email'];

		$settings = array(
			'log_retention_days'      => $days,
			'log_body_mode'           => $mode,
			'log_body_max_chars'      => $max,
			'log_redact_body'         => $redact,
			'smtp_debug'              => $debug,
			'smtp_debug_until'        => $until,
			'failure_failover'        => ! empty( $input['failure_failover'] ) ? 1 : 0,
			'queue_enabled'           => ! empty( $input['queue_enabled'] ) ? 1 : 0,
			'queue_batch_size'        => $batch,
			'alert_email'             => $alert_email,
			'alert_on_health_fail'    => ! empty( $input['alert_on_health_fail'] ) ? 1 : 0,
			'alert_on_failure_spike'  => ! empty( $input['alert_on_failure_spike'] ) ? 1 : 0,
			'failure_spike_threshold' => $spike,
		);

		update_option( self::OPTION_KEY, $settings, false );

		if ( ! empty( $settings['queue_enabled'] ) ) {
			VMS_MSG_Queue::maybe_schedule();
		}

		return $settings;
	}
}
