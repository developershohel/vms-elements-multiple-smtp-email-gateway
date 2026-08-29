<?php
/**
 * PHPMailer routing and email attempt tracking.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Applies SMTP credentials on phpmailer_init and coordinates logging.
 */
class VMS_MSG_Mailer {

	/**
	 * Forced SMTP account ID for resend / test sends (overrides From routing).
	 *
	 * @var int|null
	 */
	private static $forced_account_id = null;

	/**
	 * Pending mail payload captured from the wp_mail filter.
	 *
	 * @var array|null
	 */
	private static $pending = null;

	/**
	 * Account metadata used for the current send.
	 *
	 * @var array|null
	 */
	private static $current_account = null;

	/**
	 * Whether the current send is a resend.
	 *
	 * @var bool
	 */
	private static $is_resend = false;

	/**
	 * Parent log ID when resending.
	 *
	 * @var int
	 */
	private static $parent_log_id = 0;

	/**
	 * Last PHPMailer error captured via Debugoutput callback.
	 *
	 * @var string
	 */
	private static $last_phpmailer_error = '';

	/**
	 * Account IDs already tried during failure failover for this send.
	 *
	 * @var array<int, int>
	 */
	private static $tried_account_ids = array();

	/**
	 * Whether a failure-failover retry is in progress.
	 *
	 * @var bool
	 */
	private static $in_failover = false;

	/**
	 * Register mail hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'wp_mail', array( __CLASS__, 'capture_mail' ), 1, 1 );
		add_filter( 'pre_wp_mail', array( __CLASS__, 'maybe_block_over_limit' ), 5, 2 );
		add_action( 'phpmailer_init', array( __CLASS__, 'configure_phpmailer' ), 10, 1 );
		add_action( 'wp_mail_succeeded', array( __CLASS__, 'on_mail_succeeded' ), 10, 1 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'on_mail_failed' ), 10, 1 );
	}

	/**
	 * Whether a forced account is active (resend/test/failover).
	 *
	 * @return bool
	 */
	public static function is_forced() {
		return ! empty( self::$forced_account_id );
	}

	/**
	 * Clear pending capture after queue short-circuit.
	 *
	 * @return void
	 */
	public static function clear_pending_capture() {
		self::reset_pending();
	}

	/**
	 * Force a specific SMTP account for the next wp_mail() call.
	 *
	 * @param int  $account_id    Account ID.
	 * @param bool $is_resend     Mark as resend for logging.
	 * @param int  $parent_log_id Original log ID.
	 * @return void
	 */
	public static function set_forced_account( $account_id, $is_resend = false, $parent_log_id = 0 ) {
		self::$forced_account_id = absint( $account_id );
		self::$is_resend         = (bool) $is_resend;
		self::$parent_log_id     = absint( $parent_log_id );
	}

	/**
	 * Clear forced routing / resend context.
	 *
	 * @return void
	 */
	public static function clear_forced_account() {
		self::$forced_account_id = null;
		self::$is_resend         = false;
		self::$parent_log_id     = 0;
	}

	/**
	 * Resolve which account would handle this send (without applying SMTP).
	 *
	 * @param array $atts wp_mail atts.
	 * @return object|null
	 */
	private static function resolve_account_for_atts( $atts ) {
		if ( self::$forced_account_id ) {
			return VMS_MSG_Accounts::get( self::$forced_account_id, true );
		}

		$from = '';
		if ( is_array( $atts ) && ! empty( $atts['headers'] ) ) {
			$headers = is_array( $atts['headers'] ) ? $atts['headers'] : explode( "\n", (string) $atts['headers'] );
			foreach ( $headers as $header ) {
				if ( stripos( $header, 'From:' ) === 0 ) {
					if ( preg_match( '/[\w.+-]+@[\w.-]+\.[a-zA-Z]{2,}/', $header, $m ) ) {
						$from = sanitize_email( $m[0] );
					}
					break;
				}
			}
		}

		if ( is_email( $from ) ) {
			$matched = VMS_MSG_Accounts::get_by_sender_email( $from );
			if ( $matched ) {
				return $matched;
			}
		}

		return VMS_MSG_Accounts::get_default();
	}

	/**
	 * Find next usable account when primary is over daily limit or failed.
	 *
	 * Order: fallback_priority chain → global default. Skips excluded IDs.
	 *
	 * @param array<int, int> $exclude_ids Account IDs to skip.
	 * @return object|null
	 */
	private static function find_chain_fallback( array $exclude_ids ) {
		$exclude_ids = array_map( 'absint', $exclude_ids );

		foreach ( VMS_MSG_Accounts::get_fallback_chain( 0 ) as $candidate ) {
			$id = (int) $candidate->id;
			if ( in_array( $id, $exclude_ids, true ) ) {
				continue;
			}
			if ( self::account_over_daily_limit( $candidate ) ) {
				continue;
			}
			return $candidate;
		}

		$default = VMS_MSG_Accounts::get_default();
		if ( $default ) {
			$id = (int) $default->id;
			if ( ! in_array( $id, $exclude_ids, true ) && ! self::account_over_daily_limit( $default ) ) {
				return $default;
			}
		}

		return null;
	}

	/**
	 * Find next usable account when primary is over daily limit.
	 *
	 * @param object $primary Primary account.
	 * @return object|null
	 */
	private static function find_limit_fallback( $primary ) {
		$exclude = is_object( $primary ) ? array( (int) $primary->id ) : array();
		return self::find_chain_fallback( $exclude );
	}

	/**
	 * Block sends when the resolved account is over its daily limit and no fallback exists.
	 *
	 * @param mixed $null Short-circuit value.
	 * @param array $atts Mail args.
	 * @return mixed
	 */
	public static function maybe_block_over_limit( $null, $atts ) {
		if ( null !== $null ) {
			return $null;
		}

		$account = self::resolve_account_for_atts( is_array( $atts ) ? $atts : array() );
		if ( ! $account || ! self::account_over_daily_limit( $account ) ) {
			return null;
		}

		if ( self::find_limit_fallback( $account ) ) {
			return null;
		}

		$message = sprintf(
			/* translators: 1: account name, 2: daily limit */
			__( 'Daily send limit reached for “%1$s” (%2$d) and no fallback account is available.', 'vms-elements-multiple-smtp-email-gateway' ),
			$account->account_name,
			(int) $account->daily_limit
		);

		self::$pending         = is_array( $atts ) ? $atts : array();
		self::$current_account = array(
			'id'   => (int) $account->id,
			'name' => (string) $account->account_name,
		);
		self::write_log( 'failed', $message, array() );
		self::reset_pending();

		return false;
	}

	/**
	 * Capture outgoing mail args before PHPMailer runs.
	 *
	 * @param array $args wp_mail arguments.
	 * @return array
	 */
	public static function capture_mail( $args ) {
		self::$pending              = is_array( $args ) ? $args : array();
		self::$current_account      = null;
		self::$last_phpmailer_error = '';

		return $args;
	}

	/**
	 * Override PHPMailer with matched or default (or forced) SMTP credentials.
	 *
	 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
	 * @return void
	 */
	public static function configure_phpmailer( $phpmailer ) {
		$account       = null;
		$primary_id    = 0;
		$limit_skipped = false;
		$note          = '';

		// 1) Forced provider override (resend / test / failover).
		if ( self::$forced_account_id ) {
			$account    = VMS_MSG_Accounts::get( self::$forced_account_id, true );
			$primary_id = self::$forced_account_id;
			if ( self::$in_failover ) {
				$note = __( '(failover after send failure)', 'vms-elements-multiple-smtp-email-gateway' );
			}
		}

		// 2) Match by sender (From) email.
		if ( ! $account ) {
			$from = '';
			if ( is_object( $phpmailer ) && ! empty( $phpmailer->From ) ) {
				$from = sanitize_email( $phpmailer->From );
			}
			if ( is_email( $from ) ) {
				$account = VMS_MSG_Accounts::get_by_sender_email( $from );
				if ( $account ) {
					$primary_id = (int) $account->id;
				}
			}
		}

		// 3) Fallback to global default.
		if ( ! $account ) {
			$account = VMS_MSG_Accounts::get_default();
			if ( $account ) {
				$primary_id = (int) $account->id;
			}
		}

		// Daily rate limit: walk fallback chain, then global default.
		if ( $account && self::account_over_daily_limit( $account ) ) {
			$limit_skipped = true;
			$fallback      = self::find_limit_fallback( $account );
			if ( $fallback ) {
				$account = $fallback;
			}
		}

		if ( ! $account ) {
			self::$current_account = array(
				'id'   => 0,
				'name' => __( 'WordPress default (no SMTP account)', 'vms-elements-multiple-smtp-email-gateway' ),
			);
			return;
		}

		$creds = VMS_MSG_Accounts::get_credentials( $account );
		if ( is_wp_error( $creds ) ) {
			self::$current_account = array(
				'id'   => (int) $account->id,
				'name' => (string) $account->account_name . ' (' . $creds->get_error_message() . ')',
			);
			self::$last_phpmailer_error = $creds->get_error_message();
			return;
		}

		try {
			$phpmailer->isSMTP();
			$phpmailer->Host        = $creds['host'];
			$phpmailer->Port        = $creds['port'];
			$phpmailer->SMTPAuth    = true;
			$phpmailer->Username    = $creds['username'];
			$phpmailer->Password    = $creds['password'];
			$phpmailer->Timeout     = 30;
			$phpmailer->SMTPAutoTLS = true;

			switch ( $creds['encryption'] ) {
				case 'ssl':
					$phpmailer->SMTPSecure = 'ssl';
					break;
				case 'none':
					$phpmailer->SMTPSecure  = '';
					$phpmailer->SMTPAutoTLS = false;
					break;
				case 'tls':
				default:
					$phpmailer->SMTPSecure = 'tls';
					break;
			}

			$should_force_from = self::$forced_account_id || ! empty( $account->force_from );
			if ( $should_force_from && is_email( $creds['from_email'] ) ) {
				$phpmailer->setFrom( $creds['from_email'], $phpmailer->FromName, false );
			}

			$phpmailer->SMTPDebug = 0;
			if ( VMS_MSG_Settings::is_smtp_debug_active() ) {
				$phpmailer->SMTPDebug = 2;
			}
			$phpmailer->Debugoutput = static function ( $str, $level ) {
				unset( $level );
				$str = trim( (string) $str );
				if ( '' === $str ) {
					return;
				}
				self::$last_phpmailer_error = $str;
				if ( VMS_MSG_Settings::is_smtp_debug_active() ) {
					VMS_MSG_Settings::append_debug_log( $str );
				}
			};

			$name = $creds['name'];
			if ( $limit_skipped && $primary_id && $primary_id !== (int) $creds['id'] ) {
				$name .= ' ' . __( '(fallback after daily limit)', 'vms-elements-multiple-smtp-email-gateway' );
			} elseif ( '' !== $note ) {
				$name .= ' ' . $note;
			}

			self::$current_account = array(
				'id'   => $creds['id'],
				'name' => $name,
			);
		} catch ( Exception $e ) {
			self::$last_phpmailer_error = $e->getMessage();
			self::$current_account      = array(
				'id'   => $creds['id'],
				'name' => $creds['name'],
			);
		}
	}

	/**
	 * Whether an account has hit its daily send cap.
	 *
	 * @param object $account Account row.
	 * @return bool
	 */
	private static function account_over_daily_limit( $account ) {
		if ( ! is_object( $account ) ) {
			return false;
		}
		$limit = isset( $account->daily_limit ) ? (int) $account->daily_limit : 0;
		if ( $limit < 1 ) {
			return false;
		}
		$sent = VMS_MSG_Logger::count_sent_today( (int) $account->id );
		return $sent >= $limit;
	}

	/**
	 * Log successful delivery (WP 5.9+).
	 *
	 * @param array $mail_data Mail data from wp_mail_succeeded.
	 * @return void
	 */
	public static function on_mail_succeeded( $mail_data ) {
		self::write_log( 'sent', '', is_array( $mail_data ) ? $mail_data : array() );
		self::$tried_account_ids = array();
		self::reset_pending();
	}

	/**
	 * Log failed delivery; optionally retry via fallback chain.
	 *
	 * @param WP_Error $error Failure error.
	 * @return void
	 */
	public static function on_mail_failed( $error ) {
		$message = '';

		if ( is_wp_error( $error ) ) {
			$message = $error->get_error_message();
			$data    = $error->get_error_data();
			if ( is_array( $data ) && ! empty( $data['phpmailer_exception_code'] ) ) {
				$message .= ' [code: ' . sanitize_text_field( (string) $data['phpmailer_exception_code'] ) . ']';
			}
		}

		if ( '' === $message && '' !== self::$last_phpmailer_error ) {
			$message = self::$last_phpmailer_error;
		}

		if ( '' === $message ) {
			$message = __( 'Unknown mail failure.', 'vms-elements-multiple-smtp-email-gateway' );
		}

		self::write_log( 'failed', $message, array() );

		$pending   = self::$pending;
		$failed_id = is_array( self::$current_account ) && ! empty( self::$current_account['id'] )
			? (int) self::$current_account['id']
			: 0;

		if ( self::should_failover( $failed_id ) && is_array( $pending ) ) {
			self::$tried_account_ids[] = $failed_id;
			$exclude                   = self::$tried_account_ids;
			$next                      = self::find_chain_fallback( $exclude );

			if ( $next ) {
				self::reset_pending();
				self::$in_failover = true;
				self::set_forced_account( (int) $next->id, false, 0 );

				wp_mail(
					isset( $pending['to'] ) ? $pending['to'] : '',
					isset( $pending['subject'] ) ? $pending['subject'] : '',
					isset( $pending['message'] ) ? $pending['message'] : '',
					isset( $pending['headers'] ) ? $pending['headers'] : '',
					isset( $pending['attachments'] ) ? $pending['attachments'] : array()
				);

				self::clear_forced_account();
				self::$in_failover       = false;
				self::$tried_account_ids = array();
				return;
			}
		}

		self::$tried_account_ids = array();
		self::reset_pending();
	}

	/**
	 * Whether failure failover should run.
	 *
	 * @param int $failed_id Failed account ID.
	 * @return bool
	 */
	private static function should_failover( $failed_id ) {
		if ( ! VMS_MSG_Settings::get( 'failure_failover', 1 ) ) {
			return false;
		}
		if ( self::$is_resend ) {
			return false;
		}
		// User-selected provider (test email) — do not auto-failover.
		if ( self::$forced_account_id && ! self::$in_failover ) {
			return false;
		}
		if ( count( self::$tried_account_ids ) >= 5 ) {
			return false;
		}
		if ( $failed_id < 1 ) {
			return false;
		}
		return true;
	}

	/**
	 * Persist a log row from pending mail context.
	 *
	 * @param string $status  sent|failed.
	 * @param string $error   Error message.
	 * @param array  $mail_data Optional succeeded payload.
	 * @return void
	 */
	private static function write_log( $status, $error = '', $mail_data = array() ) {
		$pending = self::$pending;
		if ( ! is_array( $pending ) && empty( $mail_data ) ) {
			return;
		}

		$to      = '';
		$subject = '';
		$body    = '';
		$headers = '';

		if ( is_array( $pending ) ) {
			$to      = isset( $pending['to'] ) ? $pending['to'] : '';
			$subject = isset( $pending['subject'] ) ? $pending['subject'] : '';
			$body    = isset( $pending['message'] ) ? $pending['message'] : '';
			$headers = isset( $pending['headers'] ) ? $pending['headers'] : '';
		}

		if ( ! empty( $mail_data ) ) {
			$to      = isset( $mail_data['to'] ) ? $mail_data['to'] : $to;
			$subject = isset( $mail_data['subject'] ) ? $mail_data['subject'] : $subject;
			$body    = isset( $mail_data['message'] ) ? $mail_data['message'] : $body;
			$headers = isset( $mail_data['headers'] ) ? $mail_data['headers'] : $headers;
		}

		$account_name = '';
		$account_id   = null;
		if ( is_array( self::$current_account ) ) {
			$account_name = isset( self::$current_account['name'] ) ? self::$current_account['name'] : '';
			$account_id   = ! empty( self::$current_account['id'] ) ? (int) self::$current_account['id'] : null;
		}

		VMS_MSG_Logger::insert(
			array(
				'to_email'          => $to,
				'subject'           => $subject,
				'message_body'      => $body,
				'headers'           => $headers,
				'status'            => $status,
				'error_message'     => $error,
				'used_smtp_account' => $account_name,
				'smtp_account_id'   => $account_id,
				'is_resend'         => self::$is_resend,
				'parent_log_id'     => self::$parent_log_id,
			)
		);
	}

	/**
	 * Reset per-send capture state (keeps forced flags until clear_forced_account).
	 *
	 * @return void
	 */
	private static function reset_pending() {
		self::$pending              = null;
		self::$current_account      = null;
		self::$last_phpmailer_error = '';
	}
}
