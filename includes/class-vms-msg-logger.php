<?php
/**
 * Email log data access layer.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores and queries outgoing email logs.
 */
class VMS_MSG_Logger {

	/**
	 * Logs table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'vms_msg_email_logs';
	}

	/**
	 * Insert a log row.
	 *
	 * @param array $data Log fields.
	 * @return int|false Insert ID or false.
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$table = self::table_name();

		$to      = isset( $data['to_email'] ) ? self::normalize_recipients( $data['to_email'] ) : '';
		$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : '';
		$body    = isset( $data['message_body'] ) ? (string) $data['message_body'] : '';
		$headers = isset( $data['headers'] ) ? self::normalize_headers( $data['headers'] ) : '';
		$status  = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'failed';

		if ( ! in_array( $status, array( 'sent', 'failed' ), true ) ) {
			$status = 'failed';
		}

		$body = self::prepare_message_body( $body );

		$row = array(
			'date_time'         => isset( $data['date_time'] ) ? sanitize_text_field( $data['date_time'] ) : current_time( 'mysql' ),
			'to_email'          => $to,
			'subject'           => $subject,
			'message_body'      => $body,
			'headers'           => $headers,
			'status'            => $status,
			'error_message'     => isset( $data['error_message'] ) ? sanitize_textarea_field( $data['error_message'] ) : '',
			'used_smtp_account' => isset( $data['used_smtp_account'] ) ? sanitize_text_field( $data['used_smtp_account'] ) : '',
			'is_resend'         => ! empty( $data['is_resend'] ) ? 1 : 0,
		);
		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' );

		if ( ! empty( $data['smtp_account_id'] ) ) {
			$row['smtp_account_id'] = absint( $data['smtp_account_id'] );
			$formats[]              = '%d';
		}

		if ( ! empty( $data['parent_log_id'] ) ) {
			$row['parent_log_id'] = absint( $data['parent_log_id'] );
			$formats[]            = '%d';
		}

		$inserted = $wpdb->insert( $table, $row, $formats );
		if ( ! $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get a single log entry.
	 *
	 * @param int $id Log ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;

		$id    = absint( $id );
		$table = self::table_name();

		if ( $id < 1 ) {
			return null;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
				$id
			)
		);

		return $row ? $row : null;
	}

	/**
	 * Query logs with pagination and filters.
	 *
	 * @param array $args Query args.
	 * @return array{items: array, total: int, pages: int}
	 */
	public static function query( array $args = array() ) {
		global $wpdb;

		$table      = self::table_name();
		$per_page   = isset( $args['per_page'] ) ? max( 1, absint( $args['per_page'] ) ) : 20;
		$page       = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
		$status     = isset( $args['status'] ) ? sanitize_key( $args['status'] ) : '';
		$search     = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$account_id = isset( $args['account_id'] ) ? absint( $args['account_id'] ) : 0;
		$date_from  = isset( $args['date_from'] ) ? sanitize_text_field( $args['date_from'] ) : '';
		$date_to    = isset( $args['date_to'] ) ? sanitize_text_field( $args['date_to'] ) : '';
		$offset     = ( $page - 1 ) * $per_page;

		$built  = self::build_where( $status, $search, $account_id, $date_from, $date_to );
		$where  = $built['where'];
		$values = $built['values'];

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
		}

		$list_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY date_time DESC, id DESC LIMIT %d OFFSET %d";
		$list_args = array_merge( $values, array( $per_page, $offset ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_args ) );

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
			'pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Shared WHERE builder for query / export / bulk.
	 *
	 * @param string $status     Status.
	 * @param string $search     Search.
	 * @param int    $account_id Account ID.
	 * @param string $date_from  Y-m-d.
	 * @param string $date_to    Y-m-d.
	 * @return array{where:array,values:array}
	 */
	private static function build_where( $status, $search, $account_id = 0, $date_from = '', $date_to = '' ) {
		global $wpdb;

		$where  = array( '1=1' );
		$values = array();

		if ( in_array( $status, array( 'sent', 'failed' ), true ) ) {
			$where[]  = 'status = %s';
			$values[] = $status;
		}

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(to_email LIKE %s OR subject LIKE %s OR error_message LIKE %s OR used_smtp_account LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		if ( $account_id > 0 ) {
			$where[]  = 'smtp_account_id = %d';
			$values[] = $account_id;
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$where[]  = 'date_time >= %s';
			$values[] = $date_from . ' 00:00:00';
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$where[]  = 'date_time <= %s';
			$values[] = $date_to . ' 23:59:59';
		}

		return array(
			'where'  => $where,
			'values' => $values,
		);
	}

	/**
	 * Delete a log by ID.
	 *
	 * @param int $id Log ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( $id < 1 ) {
			return false;
		}

		return false !== $wpdb->delete( self::table_name(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Bulk delete by IDs.
	 *
	 * @param array<int, int> $ids IDs.
	 * @return int Deleted count.
	 */
	public static function delete_many( array $ids ) {
		global $wpdb;

		$ids = array_filter( array_map( 'absint', $ids ) );
		if ( empty( $ids ) ) {
			return 0;
		}

		$table        = self::table_name();
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ($placeholders)", $ids ) );

		return false === $deleted ? 0 : (int) $deleted;
	}

	/**
	 * Delete all failed logs (optionally filtered).
	 *
	 * @param array $args Optional filters (account_id, date_from, date_to, search).
	 * @return int
	 */
	public static function delete_failed( array $args = array() ) {
		global $wpdb;

		$table      = self::table_name();
		$account_id = isset( $args['account_id'] ) ? absint( $args['account_id'] ) : 0;
		$search     = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$date_from  = isset( $args['date_from'] ) ? sanitize_text_field( $args['date_from'] ) : '';
		$date_to    = isset( $args['date_to'] ) ? sanitize_text_field( $args['date_to'] ) : '';

		$built     = self::build_where( 'failed', $search, $account_id, $date_from, $date_to );
		$where_sql = implode( ' AND ', $built['where'] );

		$sql = "DELETE FROM {$table} WHERE {$where_sql}";
		if ( ! empty( $built['values'] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$deleted = $wpdb->query( $wpdb->prepare( $sql, $built['values'] ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$deleted = $wpdb->query( $sql );
		}

		return false === $deleted ? 0 : (int) $deleted;
	}

	/**
	 * Apply privacy / size policy to stored message bodies.
	 *
	 * @param string $body Raw body.
	 * @return string
	 */
	public static function prepare_message_body( $body ) {
		$body = (string) $body;

		if ( VMS_MSG_Settings::get( 'log_redact_body', 0 ) ) {
			return '[redacted]';
		}

		$mode = VMS_MSG_Settings::get( 'log_body_mode', 'full' );
		if ( 'omit' === $mode ) {
			return '';
		}

		$body = wp_kses_post( $body );

		if ( 'truncate' === $mode ) {
			$max = (int) VMS_MSG_Settings::get( 'log_body_max_chars', 5000 );
			if ( $max > 0 && strlen( $body ) > $max ) {
				$body = substr( $body, 0, $max ) . "\n…[truncated]";
			}
		}

		return $body;
	}

	/**
	 * Normalize recipients to a comma-separated string.
	 *
	 * @param mixed $to Recipients.
	 * @return string
	 */
	public static function normalize_recipients( $to ) {
		if ( is_array( $to ) ) {
			$clean = array();
			foreach ( $to as $email ) {
				$email = sanitize_email( $email );
				if ( is_email( $email ) ) {
					$clean[] = $email;
				}
			}
			return implode( ', ', $clean );
		}

		$parts = array_map( 'trim', explode( ',', (string) $to ) );
		$clean = array();
		foreach ( $parts as $part ) {
			$email = sanitize_email( $part );
			if ( is_email( $email ) ) {
				$clean[] = $email;
			} else {
				// Keep non-email display strings sanitized for log fidelity.
				$sanitized = sanitize_text_field( $part );
				if ( '' !== $sanitized ) {
					$clean[] = $sanitized;
				}
			}
		}

		return implode( ', ', $clean );
	}

	/**
	 * Normalize headers to a newline-separated string.
	 *
	 * @param mixed $headers Headers array or string.
	 * @return string
	 */
	public static function normalize_headers( $headers ) {
		if ( is_array( $headers ) ) {
			$lines = array();
			foreach ( $headers as $header ) {
				$line = sanitize_text_field( (string) $header );
				if ( '' !== $line ) {
					$lines[] = $line;
				}
			}
			return implode( "\n", $lines );
		}

		$raw   = str_replace( array( "\r\n", "\r" ), "\n", (string) $headers );
		$lines = explode( "\n", $raw );
		$out   = array();
		foreach ( $lines as $line ) {
			$line = sanitize_text_field( $line );
			if ( '' !== $line ) {
				$out[] = $line;
			}
		}

		return implode( "\n", $out );
	}

	/**
	 * Convert stored headers string back to an array for wp_mail().
	 *
	 * @param string $headers_string Stored headers.
	 * @return array
	 */
	public static function headers_to_array( $headers_string ) {
		$headers_string = (string) $headers_string;
		if ( '' === $headers_string ) {
			return array();
		}

		$lines = preg_split( '/\r\n|\r|\n/', $headers_string );
		$out   = array();
		foreach ( (array) $lines as $line ) {
			$line = trim( (string) $line );
			if ( '' !== $line ) {
				$out[] = $line;
			}
		}

		return $out;
	}

	/**
	 * Count successful sends for an account since local midnight.
	 *
	 * @param int $account_id Account ID.
	 * @return int
	 */
	public static function count_sent_today( $account_id ) {
		global $wpdb;

		$account_id = absint( $account_id );
		if ( $account_id < 1 ) {
			return 0;
		}

		$table = self::table_name();
		$start = date_i18n( 'Y-m-d 00:00:00' );

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE smtp_account_id = %d AND status = %s AND date_time >= %s",
				$account_id,
				'sent',
				$start
			)
		);

		return (int) $count;
	}

	/**
	 * Stream CSV export of logs matching filters.
	 *
	 * @param array $args Query args (status, search).
	 * @return void
	 */
	public static function export_csv( array $args = array() ) {
		global $wpdb;

		$table      = self::table_name();
		$status     = isset( $args['status'] ) ? sanitize_key( $args['status'] ) : '';
		$search     = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$account_id = isset( $args['account_id'] ) ? absint( $args['account_id'] ) : 0;
		$date_from  = isset( $args['date_from'] ) ? sanitize_text_field( $args['date_from'] ) : '';
		$date_to    = isset( $args['date_to'] ) ? sanitize_text_field( $args['date_to'] ) : '';

		$built     = self::build_where( $status, $search, $account_id, $date_from, $date_to );
		$where_sql = implode( ' AND ', $built['where'] );
		$values    = $built['values'];

		$sql = "SELECT id, date_time, to_email, subject, status, error_message, used_smtp_account, smtp_account_id, is_resend, parent_log_id FROM {$table} WHERE {$where_sql} ORDER BY date_time DESC, id DESC LIMIT 5000";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $sql, ARRAY_A );
		}

		$filename = 'vms-multi-mailer-logs-' . gmdate( 'Y-m-d-His' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			return;
		}

		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		fputcsv(
			$out,
			array(
				'ID',
				'Date Time',
				'To',
				'Subject',
				'Status',
				'Error',
				'SMTP Account',
				'SMTP Account ID',
				'Is Resend',
				'Parent Log ID',
			)
		);

		foreach ( (array) $rows as $row ) {
			fputcsv(
				$out,
				array(
					$row['id'],
					$row['date_time'],
					$row['to_email'],
					$row['subject'],
					$row['status'],
					$row['error_message'],
					$row['used_smtp_account'],
					$row['smtp_account_id'],
					$row['is_resend'],
					$row['parent_log_id'],
				)
			);
		}

		fclose( $out );
		exit;
	}
}
