<?php
/**
 * Detect conflicting SMTP plugins.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Warns when another mailer may also hook phpmailer_init.
 */
class VMS_MSG_Conflicts {

	/**
	 * Known SMTP / mailer plugins (plugin file => label).
	 *
	 * @return array<string, string>
	 */
	public static function known_plugins() {
		return array(
			'fluent-smtp/fluent-smtp.php'                 => 'FluentSMTP',
			'wp-mail-smtp/wp_mail_smtp.php'               => 'WP Mail SMTP',
			'easy-wp-smtp/easy-wp-smtp.php'               => 'Easy WP SMTP',
			'post-smtp/postman-smtp.php'                  => 'Post SMTP',
			'gmail-smtp/main.php'                         => 'Gmail SMTP',
			'smtp-mailer/main.php'                        => 'SMTP Mailer',
			'wp-smtp/wp-smtp.php'                         => 'WP SMTP',
			'postman-smtp/postman-smtp.php'               => 'Postman SMTP',
			'wp-mail-bank/wp-mail-bank.php'               => 'Mail Bank',
			'site-mailer/site-mailer.php'                 => 'Site Mailer',
		);
	}

	/**
	 * List active conflicting plugin labels.
	 *
	 * @return array<int, string>
	 */
	public static function get_active_conflicts() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$found = array();
		foreach ( self::known_plugins() as $file => $label ) {
			if ( is_plugin_active( $file ) ) {
				$found[] = $label;
			}
		}
		return $found;
	}

	/**
	 * Register admin notice on our screens.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'render_notice' ) );
	}

	/**
	 * Print conflict notice.
	 *
	 * @return void
	 */
	public static function render_notice() {
		if ( ! current_user_can( VMS_MSG_Capabilities::CAP ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( VMS_MSG_SLUG !== $page ) {
			return;
		}

		$conflicts = self::get_active_conflicts();
		if ( empty( $conflicts ) ) {
			return;
		}

		$list = implode( ', ', array_map( 'esc_html', $conflicts ) );
		echo '<div class="notice notice-warning"><p>';
		printf(
			/* translators: %s: comma-separated plugin names */
			esc_html__( 'VMS Multi Mailer detected other active mail plugins (%s). Only one plugin should control PHPMailer/SMTP. Disable the others to avoid conflicts.', 'vms-elements-multiple-smtp-email-gateway' ),
			$list
		);
		echo '</p></div>';
	}
}
