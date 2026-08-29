<?php
/**
 * Admin page header + tabs.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

$notice_type = isset( $_GET['vms_msg_type'] ) ? sanitize_key( wp_unslash( $_GET['vms_msg_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$notice_msg  = isset( $_GET['vms_msg_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['vms_msg_msg'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap vms-msg-wrap">
	<h1><?php echo esc_html__( 'VMS Elements Multi Mailer', 'vms-elements-multiple-smtp-email-gateway' ); ?></h1>
	<p class="vms-msg-subtitle">
		<?php echo esc_html__( 'Multiple SMTP accounts, sender-based routing, email logs, and provider-aware resend.', 'vms-elements-multiple-smtp-email-gateway' ); ?>
	</p>

	<?php if ( '' !== $notice_msg ) : ?>
		<div class="notice notice-<?php echo esc_attr( in_array( $notice_type, array( 'success', 'error', 'warning', 'info' ), true ) ? $notice_type : 'info' ); ?> is-dismissible">
			<p><?php echo esc_html( $notice_msg ); ?></p>
		</div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper vms-msg-tabs">
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'dashboard', $base_url ) ); ?>" class="nav-tab <?php echo 'dashboard' === $tab ? 'nav-tab-active' : ''; ?>">
			<?php echo esc_html__( 'Dashboard', 'vms-elements-multiple-smtp-email-gateway' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'accounts', $base_url ) ); ?>" class="nav-tab <?php echo 'accounts' === $tab ? 'nav-tab-active' : ''; ?>">
			<?php echo esc_html__( 'SMTP Accounts', 'vms-elements-multiple-smtp-email-gateway' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'logs', $base_url ) ); ?>" class="nav-tab <?php echo 'logs' === $tab ? 'nav-tab-active' : ''; ?>">
			<?php echo esc_html__( 'Email Logs', 'vms-elements-multiple-smtp-email-gateway' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'settings', $base_url ) ); ?>" class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>">
			<?php echo esc_html__( 'Settings', 'vms-elements-multiple-smtp-email-gateway' ); ?>
		</a>
	</nav>

	<div class="vms-msg-panel">
