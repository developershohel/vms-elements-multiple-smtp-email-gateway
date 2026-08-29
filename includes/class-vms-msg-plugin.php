<?php
/**
 * Main plugin orchestrator.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Boots admin UI and mail hooks.
 */
class VMS_MSG_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var VMS_MSG_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return VMS_MSG_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin features.
	 *
	 * @return void
	 */
	public function init() {
		load_plugin_textdomain(
			'vms-elements-multiple-smtp-email-gateway',
			false,
			dirname( VMS_MSG_PLUGIN_BASENAME ) . '/languages'
		);

		VMS_MSG_Activator::maybe_upgrade();
		VMS_MSG_Maintenance::init();
		VMS_MSG_Maintenance::maybe_schedule();
		VMS_MSG_Queue::init();
		VMS_MSG_Mailer::init();
		VMS_MSG_Conflicts::init();

		if ( is_admin() ) {
			require_once VMS_MSG_PLUGIN_DIR . 'admin/class-vms-msg-admin.php';
			VMS_MSG_Admin::init();
		}
	}
}
