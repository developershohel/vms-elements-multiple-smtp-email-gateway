<?php
/**
 * Plugin Name:       VMS Elements Multi Mailer – Multiple SMTP, Email Gateway & Logs
 * Plugin URI:        https://vmselements.com/product/vms-elements-multi-mailer-multiple-smtp-email-gateway-logs
 * Description:       Route WordPress emails through multiple SMTP accounts with smart gateways and detailed email logs. Includes native integrations for Amazon SES, SendGrid, Mailgun, Postmark, Cloudflare, toSend, Gmail, and more.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Shohel Hossain
 * Author URI:        https://vmsuniverse.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vms-elements-multiple-smtp-email-gateway
 * Domain Path:       /languages
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

define( 'VMS_MSG_VERSION', '1.0.0' );
define( 'VMS_MSG_PLUGIN_FILE', __FILE__ );
define( 'VMS_MSG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VMS_MSG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VMS_MSG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'VMS_MSG_SLUG', 'vms-elements-multiple-smtp-email-gateway' );
define( 'VMS_MSG_DB_VERSION', '1.4.0' );

require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-encryption.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-capabilities.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-activator.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-settings.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-providers.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-accounts.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-logger.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-analytics.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-mailer.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-queue.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-resend.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-health.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-alerts.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-maintenance.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-conflicts.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-smoke-test.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-import-export.php';
require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-plugin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once VMS_MSG_PLUGIN_DIR . 'includes/class-vms-msg-cli.php';
}

register_activation_hook( __FILE__, array( 'VMS_MSG_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'VMS_MSG_Activator', 'deactivate' ) );

/**
 * Bootstrap the plugin.
 *
 * @return VMS_MSG_Plugin
 */
function vms_msg() {
	return VMS_MSG_Plugin::instance();
}

vms_msg();
