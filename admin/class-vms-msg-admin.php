<?php
/**
 * Admin menu, forms, and AJAX handlers.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the plugin dashboard and handles privileged AJAX actions.
 */
class VMS_MSG_Admin {

	/**
	 * Hook admin features.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		add_action( 'admin_post_vms_msg_save_account', array( __CLASS__, 'handle_save_account' ) );
		add_action( 'admin_post_vms_msg_delete_account', array( __CLASS__, 'handle_delete_account' ) );
		add_action( 'admin_post_vms_msg_set_default', array( __CLASS__, 'handle_set_default' ) );
		add_action( 'admin_post_vms_msg_delete_log', array( __CLASS__, 'handle_delete_log' ) );
		add_action( 'admin_post_vms_msg_save_settings', array( __CLASS__, 'handle_save_settings' ) );
		add_action( 'admin_post_vms_msg_export_logs', array( __CLASS__, 'handle_export_logs' ) );
		add_action( 'admin_post_vms_msg_prune_logs', array( __CLASS__, 'handle_prune_logs' ) );
		add_action( 'admin_post_vms_msg_smoke_test', array( __CLASS__, 'handle_smoke_test' ) );
		add_action( 'admin_post_vms_msg_clear_debug_log', array( __CLASS__, 'handle_clear_debug_log' ) );
		add_action( 'admin_post_vms_msg_bulk_logs', array( __CLASS__, 'handle_bulk_logs' ) );
		add_action( 'admin_post_vms_msg_export_accounts', array( __CLASS__, 'handle_export_accounts' ) );
		add_action( 'admin_post_vms_msg_import_accounts', array( __CLASS__, 'handle_import_accounts' ) );
		add_action( 'admin_post_vms_msg_process_queue', array( __CLASS__, 'handle_process_queue' ) );

		add_action( 'wp_ajax_vms_msg_test_email', array( __CLASS__, 'ajax_test_email' ) );
		add_action( 'wp_ajax_vms_msg_resend_email', array( __CLASS__, 'ajax_resend_email' ) );
		add_action( 'wp_ajax_vms_msg_health_check', array( __CLASS__, 'ajax_health_check' ) );
	}

	/**
	 * Register top-level admin menu using the plugin slug.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'VMS Multi Mailer', 'vms-elements-multiple-smtp-email-gateway' ),
			__( 'VMS Multi Mailer', 'vms-elements-multiple-smtp-email-gateway' ),
			VMS_MSG_Capabilities::CAP,
			VMS_MSG_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-email-alt2',
			58
		);
	}

	/**
	 * Enqueue admin CSS/JS on our screen only.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'toplevel_page_' . VMS_MSG_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'vms-msg-admin',
			VMS_MSG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			VMS_MSG_VERSION
		);

		wp_enqueue_script(
			'vms-msg-admin',
			VMS_MSG_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			VMS_MSG_VERSION,
			true
		);

		$accounts = VMS_MSG_Accounts::get_all( false );
		$list     = array();
		foreach ( $accounts as $account ) {
			$list[] = array(
				'id'   => (int) $account->id,
				'name' => (string) $account->account_name,
			);
		}

		wp_localize_script(
			'vms-msg-admin',
			'vmsMsgAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'vms_msg_admin' ),
				'accounts'  => $list,
				'providers' => VMS_MSG_Providers::js_config(),
				'i18n'      => array(
					'confirmDelete' => __( 'Delete this SMTP account?', 'vms-elements-multiple-smtp-email-gateway' ),
					'confirmLog'    => __( 'Delete this log entry?', 'vms-elements-multiple-smtp-email-gateway' ),
					'selectAccount' => __( 'Select an SMTP provider', 'vms-elements-multiple-smtp-email-gateway' ),
					'resendTitle'   => __( 'Resend email', 'vms-elements-multiple-smtp-email-gateway' ),
					'resending'     => __( 'Sending…', 'vms-elements-multiple-smtp-email-gateway' ),
					'success'       => __( 'Success', 'vms-elements-multiple-smtp-email-gateway' ),
					'error'         => __( 'Error', 'vms-elements-multiple-smtp-email-gateway' ),
					'close'         => __( 'Close', 'vms-elements-multiple-smtp-email-gateway' ),
					'confirmResend' => __( 'Resend now', 'vms-elements-multiple-smtp-email-gateway' ),
					'testPrompt'    => __( 'Send test email to:', 'vms-elements-multiple-smtp-email-gateway' ),
					'checking'      => __( 'Checking…', 'vms-elements-multiple-smtp-email-gateway' ),
					'healthOk'      => __( 'Healthy', 'vms-elements-multiple-smtp-email-gateway' ),
					'healthFail'    => __( 'Failed', 'vms-elements-multiple-smtp-email-gateway' ),
				),
			)
		);
	}

	/**
	 * Render tabbed admin page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( VMS_MSG_Capabilities::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $tab, array( 'dashboard', 'accounts', 'logs', 'settings' ), true ) ) {
			$tab = 'dashboard';
		}

		$base_url = admin_url( 'admin.php?page=' . VMS_MSG_SLUG );

		include VMS_MSG_PLUGIN_DIR . 'admin/views/page-header.php';

		if ( 'logs' === $tab ) {
			include VMS_MSG_PLUGIN_DIR . 'admin/views/logs.php';
		} elseif ( 'settings' === $tab ) {
			include VMS_MSG_PLUGIN_DIR . 'admin/views/settings.php';
		} elseif ( 'accounts' === $tab ) {
			include VMS_MSG_PLUGIN_DIR . 'admin/views/accounts.php';
		} else {
			include VMS_MSG_PLUGIN_DIR . 'admin/views/dashboard.php';
		}

		include VMS_MSG_PLUGIN_DIR . 'admin/views/page-footer.php';
	}

	/**
	 * Capability + nonce guard for admin-post handlers.
	 *
	 * @param string $nonce_action Nonce action.
	 * @param string $nonce_field  Nonce field name.
	 * @return void
	 */
	private static function assert_admin_post( $nonce_action, $nonce_field = '_wpnonce' ) {
		if ( ! current_user_can( VMS_MSG_Capabilities::CAP ) ) {
			wp_die( esc_html__( 'Forbidden.', 'vms-elements-multiple-smtp-email-gateway' ), 403 );
		}

		check_admin_referer( $nonce_action, $nonce_field );
	}

	/**
	 * Redirect helper with notice query args.
	 *
	 * @param string $tab     Tab slug.
	 * @param string $status  notice status.
	 * @param string $message Notice message.
	 * @param array  $extra   Extra query args.
	 * @return void
	 */
	private static function redirect_notice( $tab, $status, $message, array $extra = array() ) {
		$args = array_merge(
			array(
				'page'         => VMS_MSG_SLUG,
				'tab'          => $tab,
				'vms_msg_msg'  => rawurlencode( $message ),
				'vms_msg_type' => $status,
			),
			$extra
		);

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Save account (create/update).
	 *
	 * @return void
	 */
	public static function handle_save_account() {
		self::assert_admin_post( 'vms_msg_save_account' );

		$id = isset( $_POST['account_id'] ) ? absint( wp_unslash( $_POST['account_id'] ) ) : 0;

		$data = array(
			'account_name'    => isset( $_POST['account_name'] ) ? wp_unslash( $_POST['account_name'] ) : '',
			'provider'        => isset( $_POST['provider'] ) ? wp_unslash( $_POST['provider'] ) : 'other',
			'provider_meta'   => array(
				'ses_region'     => isset( $_POST['ses_region'] ) ? wp_unslash( $_POST['ses_region'] ) : 'us-east-1',
				'mailgun_region' => isset( $_POST['mailgun_region'] ) ? wp_unslash( $_POST['mailgun_region'] ) : 'us',
				'zoho_region'    => isset( $_POST['zoho_region'] ) ? wp_unslash( $_POST['zoho_region'] ) : 'us',
				'mailtrap_mode'  => isset( $_POST['mailtrap_mode'] ) ? wp_unslash( $_POST['mailtrap_mode'] ) : 'live',
			),
			'sender_email'    => isset( $_POST['sender_email'] ) ? wp_unslash( $_POST['sender_email'] ) : '',
			'smtp_host'       => isset( $_POST['smtp_host'] ) ? wp_unslash( $_POST['smtp_host'] ) : '',
			'smtp_port'       => isset( $_POST['smtp_port'] ) ? wp_unslash( $_POST['smtp_port'] ) : 587,
			'smtp_encryption' => isset( $_POST['smtp_encryption'] ) ? wp_unslash( $_POST['smtp_encryption'] ) : 'tls',
			'smtp_username'   => isset( $_POST['smtp_username'] ) ? wp_unslash( $_POST['smtp_username'] ) : '',
			'smtp_password'   => isset( $_POST['smtp_password'] ) ? (string) wp_unslash( $_POST['smtp_password'] ) : '',
			'is_default'         => ! empty( $_POST['is_default'] ),
			'force_from'         => ! empty( $_POST['force_from'] ),
			'daily_limit'        => isset( $_POST['daily_limit'] ) ? wp_unslash( $_POST['daily_limit'] ) : 0,
			'fallback_priority'  => isset( $_POST['fallback_priority'] ) ? wp_unslash( $_POST['fallback_priority'] ) : 0,
		);

		$result = VMS_MSG_Accounts::save( $data, $id );

		if ( is_wp_error( $result ) ) {
			self::redirect_notice( 'accounts', 'error', $result->get_error_message(), array( 'edit' => $id ) );
		}

		self::redirect_notice(
			'accounts',
			'success',
			$id > 0
				? __( 'SMTP account updated.', 'vms-elements-multiple-smtp-email-gateway' )
				: __( 'SMTP account created.', 'vms-elements-multiple-smtp-email-gateway' )
		);
	}

	/**
	 * Delete account.
	 *
	 * @return void
	 */
	public static function handle_delete_account() {
		self::assert_admin_post( 'vms_msg_delete_account' );

		$id     = isset( $_GET['account_id'] ) ? absint( wp_unslash( $_GET['account_id'] ) ) : 0;
		$result = VMS_MSG_Accounts::delete( $id );

		if ( is_wp_error( $result ) ) {
			self::redirect_notice( 'accounts', 'error', $result->get_error_message() );
		}

		self::redirect_notice( 'accounts', 'success', __( 'SMTP account deleted.', 'vms-elements-multiple-smtp-email-gateway' ) );
	}

	/**
	 * Set global default account.
	 *
	 * @return void
	 */
	public static function handle_set_default() {
		self::assert_admin_post( 'vms_msg_set_default' );

		$id     = isset( $_GET['account_id'] ) ? absint( wp_unslash( $_GET['account_id'] ) ) : 0;
		$result = VMS_MSG_Accounts::set_default( $id );

		if ( is_wp_error( $result ) ) {
			self::redirect_notice( 'accounts', 'error', $result->get_error_message() );
		}

		self::redirect_notice( 'accounts', 'success', __( 'Default SMTP account updated.', 'vms-elements-multiple-smtp-email-gateway' ) );
	}

	/**
	 * Delete a log row.
	 *
	 * @return void
	 */
	public static function handle_delete_log() {
		self::assert_admin_post( 'vms_msg_delete_log' );

		$id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : 0;
		if ( $id < 1 || ! VMS_MSG_Logger::delete( $id ) ) {
			self::redirect_notice( 'logs', 'error', __( 'Could not delete log entry.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		self::redirect_notice( 'logs', 'success', __( 'Log entry deleted.', 'vms-elements-multiple-smtp-email-gateway' ) );
	}

	/**
	 * AJAX: send test email via selected account.
	 *
	 * @return void
	 */
	public static function ajax_test_email() {
		self::assert_ajax();

		$account_id = isset( $_POST['account_id'] ) ? absint( wp_unslash( $_POST['account_id'] ) ) : 0;
		$to_email   = isset( $_POST['to_email'] ) ? sanitize_email( wp_unslash( $_POST['to_email'] ) ) : '';

		$result = VMS_MSG_Resend::send_test( $account_id, $to_email );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Test email sent. Check the inbox and Email Logs.', 'vms-elements-multiple-smtp-email-gateway' ),
			)
		);
	}

	/**
	 * Save global settings.
	 *
	 * @return void
	 */
	public static function handle_save_settings() {
		self::assert_admin_post( 'vms_msg_save_settings' );

		VMS_MSG_Settings::save(
			array(
				'log_retention_days'      => isset( $_POST['log_retention_days'] ) ? wp_unslash( $_POST['log_retention_days'] ) : 30,
				'log_body_mode'           => isset( $_POST['log_body_mode'] ) ? wp_unslash( $_POST['log_body_mode'] ) : 'full',
				'log_body_max_chars'      => isset( $_POST['log_body_max_chars'] ) ? wp_unslash( $_POST['log_body_max_chars'] ) : 5000,
				'log_redact_body'         => ! empty( $_POST['log_redact_body'] ),
				'smtp_debug'              => ! empty( $_POST['smtp_debug'] ),
				'failure_failover'        => ! empty( $_POST['failure_failover'] ),
				'queue_enabled'           => ! empty( $_POST['queue_enabled'] ),
				'queue_batch_size'        => isset( $_POST['queue_batch_size'] ) ? wp_unslash( $_POST['queue_batch_size'] ) : 10,
				'alert_email'             => isset( $_POST['alert_email'] ) ? wp_unslash( $_POST['alert_email'] ) : '',
				'alert_on_health_fail'    => ! empty( $_POST['alert_on_health_fail'] ),
				'alert_on_failure_spike'  => ! empty( $_POST['alert_on_failure_spike'] ),
				'failure_spike_threshold' => isset( $_POST['failure_spike_threshold'] ) ? wp_unslash( $_POST['failure_spike_threshold'] ) : 10,
			)
		);

		self::redirect_notice( 'settings', 'success', __( 'Settings saved.', 'vms-elements-multiple-smtp-email-gateway' ) );
	}

	/**
	 * Bulk log actions.
	 *
	 * @return void
	 */
	public static function handle_bulk_logs() {
		self::assert_admin_post( 'vms_msg_bulk_logs' );

		$action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
		if ( 'delete' === $action ) {
			$ids     = isset( $_POST['log_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['log_ids'] ) ) : array();
			$deleted = VMS_MSG_Logger::delete_many( $ids );
			self::redirect_notice(
				'logs',
				'success',
				sprintf(
					/* translators: %d: count */
					__( 'Deleted %d log entries.', 'vms-elements-multiple-smtp-email-gateway' ),
					(int) $deleted
				)
			);
		}

		if ( 'delete_failed' === $action ) {
			$deleted = VMS_MSG_Logger::delete_failed(
				array(
					'search'     => isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '',
					'account_id' => isset( $_POST['account_id'] ) ? absint( wp_unslash( $_POST['account_id'] ) ) : 0,
					'date_from'  => isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '',
					'date_to'    => isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '',
				)
			);
			self::redirect_notice(
				'logs',
				'success',
				sprintf(
					/* translators: %d: count */
					__( 'Deleted %d failed log entries.', 'vms-elements-multiple-smtp-email-gateway' ),
					(int) $deleted
				)
			);
		}

		self::redirect_notice( 'logs', 'error', __( 'Select a bulk action.', 'vms-elements-multiple-smtp-email-gateway' ) );
	}

	/**
	 * Export accounts JSON.
	 *
	 * @return void
	 */
	public static function handle_export_accounts() {
		self::assert_admin_post( 'vms_msg_export_accounts' );
		VMS_MSG_Import_Export::download_export();
	}

	/**
	 * Import accounts JSON.
	 *
	 * @return void
	 */
	public static function handle_import_accounts() {
		self::assert_admin_post( 'vms_msg_import_accounts' );

		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			self::redirect_notice( 'accounts', 'error', __( 'Please choose a JSON file to import.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw  = file_get_contents( $_FILES['import_file']['tmp_name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$data = json_decode( (string) $raw, true );
		if ( ! is_array( $data ) ) {
			self::redirect_notice( 'accounts', 'error', __( 'Invalid JSON import file.', 'vms-elements-multiple-smtp-email-gateway' ) );
		}

		$result  = VMS_MSG_Import_Export::import_data( $data );
		$message = sprintf(
			/* translators: 1: imported, 2: skipped */
			__( 'Import finished: %1$d imported, %2$d skipped. Re-enter SMTP passwords on imported accounts.', 'vms-elements-multiple-smtp-email-gateway' ),
			(int) $result['imported'],
			(int) $result['skipped']
		);
		if ( ! empty( $result['errors'] ) ) {
			$message .= ' ' . implode( ' ', array_slice( $result['errors'], 0, 3 ) );
		}

		self::redirect_notice( 'accounts', $result['imported'] > 0 ? 'success' : 'warning', $message );
	}

	/**
	 * Manually process mail queue.
	 *
	 * @return void
	 */
	public static function handle_process_queue() {
		self::assert_admin_post( 'vms_msg_process_queue' );
		$done = VMS_MSG_Queue::process_batch();
		self::redirect_notice(
			'settings',
			'success',
			sprintf(
				/* translators: %d: processed count */
				__( 'Processed %d queued email(s).', 'vms-elements-multiple-smtp-email-gateway' ),
				(int) $done
			)
		);
	}

	/**
	 * Run smoke tests from Settings.
	 *
	 * @return void
	 */
	public static function handle_smoke_test() {
		self::assert_admin_post( 'vms_msg_smoke_test' );

		$send = ! empty( $_POST['send_test'] );
		$to   = isset( $_POST['to_email'] ) ? sanitize_email( wp_unslash( $_POST['to_email'] ) ) : '';
		$out  = VMS_MSG_Smoke_Test::run( $send, $to );

		set_transient(
			'vms_msg_smoke_result_' . get_current_user_id(),
			$out,
			MINUTE_IN_SECONDS * 10
		);

		self::redirect_notice(
			'settings',
			$out['ok'] ? 'success' : 'error',
			$out['ok']
				? __( 'Smoke test passed. See details below.', 'vms-elements-multiple-smtp-email-gateway' )
				: __( 'Smoke test reported failures. See details below.', 'vms-elements-multiple-smtp-email-gateway' )
		);
	}

	/**
	 * Clear SMTP debug log.
	 *
	 * @return void
	 */
	public static function handle_clear_debug_log() {
		self::assert_admin_post( 'vms_msg_clear_debug_log' );
		VMS_MSG_Settings::clear_debug_log();
		self::redirect_notice( 'settings', 'success', __( 'SMTP debug log cleared.', 'vms-elements-multiple-smtp-email-gateway' ) );
	}

	/**
	 * Export logs as CSV.
	 *
	 * @return void
	 */
	public static function handle_export_logs() {
		self::assert_admin_post( 'vms_msg_export_logs' );

		VMS_MSG_Logger::export_csv(
			array(
				'status'     => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
				'search'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
				'account_id' => isset( $_GET['account_id'] ) ? absint( wp_unslash( $_GET['account_id'] ) ) : 0,
				'date_from'  => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
				'date_to'    => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
			)
		);
	}

	/**
	 * Manually run log pruning.
	 *
	 * @return void
	 */
	public static function handle_prune_logs() {
		self::assert_admin_post( 'vms_msg_prune_logs' );

		$deleted = VMS_MSG_Maintenance::prune_logs();
		self::redirect_notice(
			'settings',
			'success',
			sprintf(
				/* translators: %d: deleted row count */
				__( 'Pruned %d old log entries.', 'vms-elements-multiple-smtp-email-gateway' ),
				(int) $deleted
			)
		);
	}

	/**
	 * AJAX: SMTP health check for one account.
	 *
	 * @return void
	 */
	public static function ajax_health_check() {
		self::assert_ajax();

		$account_id = isset( $_POST['account_id'] ) ? absint( wp_unslash( $_POST['account_id'] ) ) : 0;
		$result     = VMS_MSG_Health::check_account( $account_id );

		$account = VMS_MSG_Accounts::get( $account_id, false );
		$payload = array(
			'status'     => $account && ! empty( $account->health_status ) ? $account->health_status : 'unknown',
			'message'    => $account && isset( $account->health_message ) ? $account->health_message : '',
			'checked_at' => $account && ! empty( $account->health_checked_at ) ? $account->health_checked_at : '',
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array_merge(
					$payload,
					array( 'message' => $result->get_error_message() )
				),
				400
			);
		}

		wp_send_json_success( $payload );
	}

	/**
	 * AJAX: resend a logged email through a chosen SMTP provider.
	 *
	 * @return void
	 */
	public static function ajax_resend_email() {
		self::assert_ajax();

		$log_id     = isset( $_POST['log_id'] ) ? absint( wp_unslash( $_POST['log_id'] ) ) : 0;
		$account_id = isset( $_POST['account_id'] ) ? absint( wp_unslash( $_POST['account_id'] ) ) : 0;

		$result = VMS_MSG_Resend::resend( $log_id, $account_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Email resent successfully. A new log entry was created.', 'vms-elements-multiple-smtp-email-gateway' ),
			)
		);
	}

	/**
	 * Shared AJAX auth check.
	 *
	 * @return void
	 */
	private static function assert_ajax() {
		if ( ! current_user_can( VMS_MSG_Capabilities::CAP ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'vms-elements-multiple-smtp-email-gateway' ) ), 403 );
		}

		check_ajax_referer( 'vms_msg_admin', 'nonce' );
	}
}
