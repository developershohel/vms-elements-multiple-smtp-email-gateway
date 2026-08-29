<?php
/**
 * Uninstall cleanup.
 *
 * @package VMS_MSG
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once dirname( __FILE__ ) . '/includes/class-vms-msg-capabilities.php';

global $wpdb;

$accounts = $wpdb->prefix . 'vms_msg_smtp_accounts';
$logs     = $wpdb->prefix . 'vms_msg_email_logs';
$queue    = $wpdb->prefix . 'vms_msg_mail_queue';

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$accounts}" );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$logs}" );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$queue}" );

delete_option( 'vms_msg_db_version' );
delete_option( 'vms_msg_settings' );
delete_option( 'vms_msg_smtp_debug_log' );

VMS_MSG_Capabilities::uninstall();

foreach ( array( 'vms_msg_daily_maintenance', 'vms_msg_process_queue', 'vms_msg_hourly_alerts' ) as $hook ) {
	$timestamp = wp_next_scheduled( $hook );
	while ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
		$timestamp = wp_next_scheduled( $hook );
	}
}
