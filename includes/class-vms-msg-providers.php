<?php
/**
 * Built-in PHPMailer SMTP provider presets.
 *
 * Uses original VMS icon marks only — no third-party brand logos or images.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provider catalog and field helpers for PHPMailer SMTP gateways.
 */
class VMS_MSG_Providers {

	/**
	 * Registered provider IDs (popular SMTP-capable mailers + Other).
	 *
	 * @return array<string, array>
	 */
	public static function all() {
		$providers = array(
			// —— Transactional / ESP ——
			'amazon_ses'    => self::def( 'amazon_ses', __( 'Amazon SES', 'vms-elements-multiple-smtp-email-gateway' ), 'ses', 'AS', '', 587, 'tls', __( 'SES SMTP username from IAM', 'vms-elements-multiple-smtp-email-gateway' ), __( 'SES SMTP password from IAM', 'vms-elements-multiple-smtp-email-gateway' ), '', false, 'ses', __( 'Mapped host from SES region (port 587 TLS). Enter IAM SMTP user + password.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'sendgrid'      => self::def( 'sendgrid', __( 'SendGrid', 'vms-elements-multiple-smtp-email-gateway' ), 'sg', 'SG', 'smtp.sendgrid.net', 587, 'tls', __( 'Always “apikey”', 'vms-elements-multiple-smtp-email-gateway' ), __( 'SendGrid API key', 'vms-elements-multiple-smtp-email-gateway' ), 'apikey', false, '', __( 'Mapped: smtp.sendgrid.net:587 TLS. Username is fixed; enter API key only.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'mailgun'       => self::def( 'mailgun', __( 'Mailgun', 'vms-elements-multiple-smtp-email-gateway' ), 'mg', 'MG', 'smtp.mailgun.org', 587, 'tls', __( 'Mailgun SMTP login for your domain', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailgun SMTP password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, 'mailgun', __( 'Mapped Mailgun host by region (port 587 TLS). Enter SMTP user + password.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'postmark'      => self::def( 'postmark', __( 'Postmark', 'vms-elements-multiple-smtp-email-gateway' ), 'pm', 'PM', 'smtp.postmarkapp.com', 587, 'tls', __( 'Server API token (same as password)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Server API token', 'vms-elements-multiple-smtp-email-gateway' ), '', true, '', __( 'Mapped: smtp.postmarkapp.com:587 TLS. Enter Server API token only.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'cloudflare'    => self::def( 'cloudflare', __( 'Cloudflare Email', 'vms-elements-multiple-smtp-email-gateway' ), 'cf', 'CF', 'smtp.mx.cloudflare.net', 465, 'ssl', __( 'Always “api_token”', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Cloudflare API token (Email Sending: Edit)', 'vms-elements-multiple-smtp-email-gateway' ), 'api_token', false, '', __( 'Mapped: smtp.mx.cloudflare.net:465 SSL. Username is fixed; enter API token only.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'tosend'        => self::def( 'tosend', __( 'toSend', 'vms-elements-multiple-smtp-email-gateway' ), 'ts', 'TS', 'smtp.tosend.com', 465, 'ssl', __( 'Always “tosend”', 'vms-elements-multiple-smtp-email-gateway' ), __( 'toSend API key (tsend_…)', 'vms-elements-multiple-smtp-email-gateway' ), 'tosend', false, '', __( 'Mapped: smtp.tosend.com:465 SSL. Username is fixed; enter API key only.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'brevo'         => self::def( 'brevo', __( 'Brevo (Sendinblue)', 'vms-elements-multiple-smtp-email-gateway' ), 'bv', 'BV', 'smtp-relay.brevo.com', 587, 'tls', __( 'Brevo login email (auto-filled from sender when possible)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Brevo SMTP key', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp-relay.brevo.com:587 TLS. Enter your SMTP key as password.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'mailjet'       => self::def( 'mailjet', __( 'Mailjet', 'vms-elements-multiple-smtp-email-gateway' ), 'mj', 'MJ', 'in-v3.mailjet.com', 587, 'tls', __( 'Mailjet API key (username)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailjet secret key (password)', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: in-v3.mailjet.com:587 TLS. Enter API key + secret key.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'sparkpost'     => self::def( 'sparkpost', __( 'SparkPost', 'vms-elements-multiple-smtp-email-gateway' ), 'sp', 'SP', 'smtp.sparkpostmail.com', 587, 'tls', __( 'Always “SMTP_Injection”', 'vms-elements-multiple-smtp-email-gateway' ), __( 'SparkPost API key', 'vms-elements-multiple-smtp-email-gateway' ), 'SMTP_Injection', false, '', __( 'Mapped: smtp.sparkpostmail.com:587 TLS. Username is fixed; enter API key only.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'mailersend'    => self::def( 'mailersend', __( 'MailerSend', 'vms-elements-multiple-smtp-email-gateway' ), 'ms', 'MS', 'smtp.mailersend.net', 587, 'tls', __( 'MailerSend SMTP username', 'vms-elements-multiple-smtp-email-gateway' ), __( 'MailerSend SMTP password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.mailersend.net:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'resend'        => self::def( 'resend', __( 'Resend', 'vms-elements-multiple-smtp-email-gateway' ), 'rs', 'RS', 'smtp.resend.com', 465, 'ssl', __( 'Always “resend”', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Resend API key', 'vms-elements-multiple-smtp-email-gateway' ), 'resend', false, '', __( 'Mapped: smtp.resend.com:465 SSL. Username is fixed; enter API key only.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'smtp2go'       => self::def( 'smtp2go', __( 'SMTP2GO', 'vms-elements-multiple-smtp-email-gateway' ), 's2', 'S2', 'mail.smtp2go.com', 587, 'tls', __( 'SMTP2GO username', 'vms-elements-multiple-smtp-email-gateway' ), __( 'SMTP2GO password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: mail.smtp2go.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'elastic_email' => self::def( 'elastic_email', __( 'Elastic Email', 'vms-elements-multiple-smtp-email-gateway' ), 'ee', 'EE', 'smtp.elasticemail.com', 587, 'tls', __( 'Account email (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Elastic Email password / API', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.elasticemail.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'mandrill'      => self::def( 'mandrill', __( 'Mandrill (Mailchimp)', 'vms-elements-multiple-smtp-email-gateway' ), 'md', 'MD', 'smtp.mandrillapp.com', 587, 'tls', __( 'Any username (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mandrill API key', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.mandrillapp.com:587 TLS. Password is your Mandrill API key.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'socketlabs'    => self::def( 'socketlabs', __( 'SocketLabs', 'vms-elements-multiple-smtp-email-gateway' ), 'sl', 'SL', 'smtp.socketlabs.com', 587, 'tls', __( 'SocketLabs server ID / username', 'vms-elements-multiple-smtp-email-gateway' ), __( 'SocketLabs SMTP password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.socketlabs.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'pepipost'      => self::def( 'pepipost', __( 'Pepipost / Netcore', 'vms-elements-multiple-smtp-email-gateway' ), 'pp', 'PP', 'smtp.pepipost.com', 587, 'tls', __( 'Pepipost / Netcore SMTP username', 'vms-elements-multiple-smtp-email-gateway' ), __( 'SMTP password / API key', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.pepipost.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'mailtrap'      => self::def( 'mailtrap', __( 'Mailtrap', 'vms-elements-multiple-smtp-email-gateway' ), 'mt', 'MT', 'live.smtp.mailtrap.io', 587, 'tls', __( 'Mailtrap SMTP username', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailtrap SMTP password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, 'mailtrap', __( 'Mapped Mailtrap host (live or sandbox). Enter SMTP user + password.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'mailerlite'    => self::def( 'mailerlite', __( 'MailerLite', 'vms-elements-multiple-smtp-email-gateway' ), 'ml', 'ML', 'smtp.mailerlite.com', 587, 'tls', __( 'MailerLite SMTP username', 'vms-elements-multiple-smtp-email-gateway' ), __( 'MailerLite SMTP password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.mailerlite.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'getresponse'   => self::def( 'getresponse', __( 'GetResponse', 'vms-elements-multiple-smtp-email-gateway' ), 'gr', 'GR', 'smtp.getresponse.com', 587, 'tls', __( 'GetResponse SMTP username', 'vms-elements-multiple-smtp-email-gateway' ), __( 'GetResponse SMTP password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.getresponse.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
			'sendinblue'    => self::def( 'sendinblue', __( 'Sendinblue (legacy)', 'vms-elements-multiple-smtp-email-gateway' ), 'sb', 'SB', 'smtp-relay.sendinblue.com', 587, 'tls', __( 'Account email (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'SMTP key', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp-relay.sendinblue.com:587 TLS. Prefer Brevo for new setups.', 'vms-elements-multiple-smtp-email-gateway' ), true ),

			// —— Mailbox / workspace (username defaults to sender email) ——
			'gmail'         => self::def( 'gmail', __( 'Gmail / Google Workspace', 'vms-elements-multiple-smtp-email-gateway' ), 'gm', 'GM', 'smtp.gmail.com', 587, 'tls', __( 'Usually your Gmail address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'App password (not your normal login)', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.gmail.com:587 TLS. Enter password only if username matches sender.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'microsoft365'  => self::def( 'microsoft365', __( 'Microsoft 365 / Outlook', 'vms-elements-multiple-smtp-email-gateway' ), 'm365', 'M365', 'smtp.office365.com', 587, 'tls', __( 'Usually your Microsoft 365 address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Account password or app password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.office365.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'outlook'       => self::def( 'outlook', __( 'Outlook.com / Hotmail / Live', 'vms-elements-multiple-smtp-email-gateway' ), 'ol', 'OL', 'smtp-mail.outlook.com', 587, 'tls', __( 'Usually your Outlook.com address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Account password or app password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp-mail.outlook.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'yahoo'         => self::def( 'yahoo', __( 'Yahoo Mail', 'vms-elements-multiple-smtp-email-gateway' ), 'yh', 'YH', 'smtp.mail.yahoo.com', 465, 'ssl', __( 'Usually your Yahoo address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Yahoo app password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.mail.yahoo.com:465 SSL.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'zoho'          => self::def( 'zoho', __( 'Zoho Mail', 'vms-elements-multiple-smtp-email-gateway' ), 'zh', 'ZH', 'smtp.zoho.com', 587, 'tls', __( 'Usually your Zoho address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Zoho password / app password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, 'zoho', __( 'Mapped Zoho regional SMTP host. Username follows sender email.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'fastmail'      => self::def( 'fastmail', __( 'Fastmail', 'vms-elements-multiple-smtp-email-gateway' ), 'fm', 'FM', 'smtp.fastmail.com', 465, 'ssl', __( 'Usually your Fastmail address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Fastmail app password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.fastmail.com:465 SSL.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'yandex'        => self::def( 'yandex', __( 'Yandex Mail', 'vms-elements-multiple-smtp-email-gateway' ), 'ya', 'YA', 'smtp.yandex.com', 465, 'ssl', __( 'Usually your Yandex address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Yandex password / app password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.yandex.com:465 SSL.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'icloud'        => self::def( 'icloud', __( 'iCloud Mail', 'vms-elements-multiple-smtp-email-gateway' ), 'ic', 'IC', 'smtp.mail.me.com', 587, 'tls', __( 'Usually your iCloud address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'iCloud app-specific password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.mail.me.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'aol'           => self::def( 'aol', __( 'AOL Mail', 'vms-elements-multiple-smtp-email-gateway' ), 'aol', 'AOL', 'smtp.aol.com', 465, 'ssl', __( 'Usually your AOL address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'AOL app password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.aol.com:465 SSL.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'gmx'           => self::def( 'gmx', __( 'GMX Mail', 'vms-elements-multiple-smtp-email-gateway' ), 'gmx', 'GMX', 'mail.gmx.com', 587, 'tls', __( 'Usually your GMX address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'GMX password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: mail.gmx.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), true ),

			// —— Hosting / business email ——
			'hostinger'     => self::def( 'hostinger', __( 'Hostinger Email', 'vms-elements-multiple-smtp-email-gateway' ), 'ho', 'HO', 'smtp.hostinger.com', 465, 'ssl', __( 'Mailbox address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailbox password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.hostinger.com:465 SSL.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'namecheap'     => self::def( 'namecheap', __( 'Namecheap Private Email', 'vms-elements-multiple-smtp-email-gateway' ), 'nc', 'NC', 'mail.privateemail.com', 465, 'ssl', __( 'Mailbox address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailbox password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: mail.privateemail.com:465 SSL.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'rackspace'     => self::def( 'rackspace', __( 'Rackspace Email', 'vms-elements-multiple-smtp-email-gateway' ), 'rk', 'RK', 'secure.emailsrvr.com', 465, 'ssl', __( 'Mailbox address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailbox password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: secure.emailsrvr.com:465 SSL.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'dreamhost'     => self::def( 'dreamhost', __( 'DreamHost', 'vms-elements-multiple-smtp-email-gateway' ), 'dh', 'DH', 'smtp.dreamhost.com', 587, 'tls', __( 'Mailbox address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailbox password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.dreamhost.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'ionos'         => self::def( 'ionos', __( 'IONOS', 'vms-elements-multiple-smtp-email-gateway' ), 'io', 'IO', 'smtp.ionos.com', 587, 'tls', __( 'Mailbox address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailbox password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.ionos.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'godaddy'       => self::def( 'godaddy', __( 'GoDaddy', 'vms-elements-multiple-smtp-email-gateway' ), 'gd', 'GD', 'smtpout.secureserver.net', 465, 'ssl', __( 'Mailbox address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailbox password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtpout.secureserver.net:465 SSL.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'titan'         => self::def( 'titan', __( 'Titan Email', 'vms-elements-multiple-smtp-email-gateway' ), 'ti', 'TI', 'smtp.titan.email', 465, 'ssl', __( 'Mailbox address (auto-filled from sender)', 'vms-elements-multiple-smtp-email-gateway' ), __( 'Mailbox password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.titan.email:465 SSL.', 'vms-elements-multiple-smtp-email-gateway' ), true ),
			'improvmx'      => self::def( 'improvmx', __( 'ImprovMX', 'vms-elements-multiple-smtp-email-gateway' ), 'im', 'IM', 'smtp.improvmx.com', 587, 'tls', __( 'ImprovMX SMTP username', 'vms-elements-multiple-smtp-email-gateway' ), __( 'ImprovMX SMTP password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Mapped: smtp.improvmx.com:587 TLS.', 'vms-elements-multiple-smtp-email-gateway' ), false ),

			// —— Catch-all (manual host/port/encryption) ——
			'other'         => self::def( 'other', __( 'Other SMTP', 'vms-elements-multiple-smtp-email-gateway' ), 'ot', 'SMTP', '', 587, 'tls', __( 'SMTP username', 'vms-elements-multiple-smtp-email-gateway' ), __( 'SMTP password', 'vms-elements-multiple-smtp-email-gateway' ), '', false, '', __( 'Enter host, port, encryption, username, and password manually.', 'vms-elements-multiple-smtp-email-gateway' ), false ),
		);

		return $providers;
	}

	/**
	 * Build a provider definition array.
	 *
	 * @param string $id                   Provider ID.
	 * @param string $label                Label.
	 * @param string $icon                 Icon slug.
	 * @param string $mark                 Short mark text.
	 * @param string $host                 SMTP host.
	 * @param int    $port                 Port.
	 * @param string $encryption           tls|ssl|none.
	 * @param string $username_hint        Username help.
	 * @param string $password_hint        Password help.
	 * @param string $username_lock        Locked username or empty.
	 * @param bool   $mirror_pass          Mirror password into username.
	 * @param string $region_type          Region UI type: ses|mailgun|zoho|mailtrap|''.
	 * @param string $help                 Help text.
	 * @param bool   $username_from_sender Auto-fill username from sender email.
	 * @return array
	 */
	private static function def( $id, $label, $icon, $mark, $host, $port, $encryption, $username_hint, $password_hint, $username_lock, $mirror_pass, $region_type, $help, $username_from_sender = false ) {
		return array(
			'id'                    => $id,
			'label'                 => $label,
			'icon'                  => $icon,
			'mark'                  => $mark,
			'host'                  => $host,
			'port'                  => (int) $port,
			'encryption'            => $encryption,
			'username_hint'         => $username_hint,
			'password_hint'         => $password_hint,
			'username_lock'         => $username_lock,
			'mirror_pass'           => (bool) $mirror_pass,
			'needs_region'          => '' !== $region_type,
			'region_type'           => $region_type,
			'help'                  => $help,
			'username_from_sender'  => (bool) $username_from_sender,
			'maps_connection'       => ( 'other' !== $id ),
		);
	}

	/**
	 * Count of built-in presets (including Other SMTP).
	 *
	 * @return int
	 */
	public static function count() {
		return count( self::all() );
	}

	/**
	 * Amazon SES regions commonly used for SMTP.
	 *
	 * @return array<string, string>
	 */
	public static function ses_regions() {
		return array(
			'us-east-1'      => 'US East (N. Virginia)',
			'us-east-2'      => 'US East (Ohio)',
			'us-west-1'      => 'US West (N. California)',
			'us-west-2'      => 'US West (Oregon)',
			'eu-west-1'      => 'Europe (Ireland)',
			'eu-west-2'      => 'Europe (London)',
			'eu-west-3'      => 'Europe (Paris)',
			'eu-central-1'   => 'Europe (Frankfurt)',
			'eu-north-1'     => 'Europe (Stockholm)',
			'ap-south-1'     => 'Asia Pacific (Mumbai)',
			'ap-northeast-1' => 'Asia Pacific (Tokyo)',
			'ap-northeast-2' => 'Asia Pacific (Seoul)',
			'ap-southeast-1' => 'Asia Pacific (Singapore)',
			'ap-southeast-2' => 'Asia Pacific (Sydney)',
			'ca-central-1'   => 'Canada (Central)',
			'sa-east-1'      => 'South America (São Paulo)',
		);
	}

	/**
	 * Mailgun region options.
	 *
	 * @return array<string, string>
	 */
	public static function mailgun_regions() {
		return array(
			'us' => __( 'United States (smtp.mailgun.org)', 'vms-elements-multiple-smtp-email-gateway' ),
			'eu' => __( 'Europe (smtp.eu.mailgun.org)', 'vms-elements-multiple-smtp-email-gateway' ),
		);
	}

	/**
	 * Zoho Mail regional SMTP hosts.
	 *
	 * @return array<string, string>
	 */
	public static function zoho_regions() {
		return array(
			'us' => __( 'United States (smtp.zoho.com)', 'vms-elements-multiple-smtp-email-gateway' ),
			'eu' => __( 'Europe (smtp.zoho.eu)', 'vms-elements-multiple-smtp-email-gateway' ),
			'in' => __( 'India (smtp.zoho.in)', 'vms-elements-multiple-smtp-email-gateway' ),
			'au' => __( 'Australia (smtp.zoho.com.au)', 'vms-elements-multiple-smtp-email-gateway' ),
		);
	}

	/**
	 * Mailtrap environment options.
	 *
	 * @return array<string, string>
	 */
	public static function mailtrap_modes() {
		return array(
			'live'    => __( 'Sending / Live (live.smtp.mailtrap.io)', 'vms-elements-multiple-smtp-email-gateway' ),
			'sandbox' => __( 'Email Testing / Sandbox (sandbox.smtp.mailtrap.io)', 'vms-elements-multiple-smtp-email-gateway' ),
		);
	}

	/**
	 * Get one provider definition.
	 *
	 * @param string $id Provider ID.
	 * @return array|null
	 */
	public static function get( $id ) {
		$all = self::all();
		$id  = sanitize_key( $id );
		return isset( $all[ $id ] ) ? $all[ $id ] : null;
	}

	/**
	 * Sanitize provider ID with fallback.
	 *
	 * @param string $id Raw ID.
	 * @return string
	 */
	public static function sanitize_id( $id ) {
		$id = sanitize_key( $id );
		return self::get( $id ) ? $id : 'other';
	}

	/**
	 * Resolve host/port/encryption (and locked username) from provider + meta.
	 *
	 * @param string $provider_id Provider ID.
	 * @param array  $meta        Extra meta.
	 * @return array{host:string,port:int,encryption:string,username_lock:string,mirror_pass:bool}
	 */
	public static function resolve_connection( $provider_id, array $meta = array() ) {
		$provider = self::get( $provider_id );
		if ( ! $provider ) {
			$provider = self::get( 'other' );
		}

		$host       = (string) $provider['host'];
		$port       = (int) $provider['port'];
		$encryption = (string) $provider['encryption'];

		if ( 'amazon_ses' === $provider['id'] ) {
			$region = isset( $meta['ses_region'] ) ? sanitize_key( $meta['ses_region'] ) : 'us-east-1';
			if ( ! array_key_exists( $region, self::ses_regions() ) ) {
				$region = 'us-east-1';
			}
			$host = 'email-smtp.' . $region . '.amazonaws.com';
		}

		if ( 'mailgun' === $provider['id'] ) {
			$mg   = isset( $meta['mailgun_region'] ) ? sanitize_key( $meta['mailgun_region'] ) : 'us';
			$host = ( 'eu' === $mg ) ? 'smtp.eu.mailgun.org' : 'smtp.mailgun.org';
		}

		if ( 'zoho' === $provider['id'] ) {
			$zh = isset( $meta['zoho_region'] ) ? sanitize_key( $meta['zoho_region'] ) : 'us';
			$map = array(
				'us' => 'smtp.zoho.com',
				'eu' => 'smtp.zoho.eu',
				'in' => 'smtp.zoho.in',
				'au' => 'smtp.zoho.com.au',
			);
			$host = isset( $map[ $zh ] ) ? $map[ $zh ] : 'smtp.zoho.com';
		}

		if ( 'mailtrap' === $provider['id'] ) {
			$mode = isset( $meta['mailtrap_mode'] ) ? sanitize_key( $meta['mailtrap_mode'] ) : 'live';
			$host = ( 'sandbox' === $mode ) ? 'sandbox.smtp.mailtrap.io' : 'live.smtp.mailtrap.io';
		}

		return array(
			'host'                 => $host,
			'port'                 => $port,
			'encryption'           => $encryption,
			'username_lock'        => isset( $provider['username_lock'] ) ? (string) $provider['username_lock'] : '',
			'mirror_pass'          => ! empty( $provider['mirror_pass'] ),
			'username_from_sender' => ! empty( $provider['username_from_sender'] ),
		);
	}

	/**
	 * Decode provider_meta JSON from DB.
	 *
	 * @param mixed $raw Raw DB value.
	 * @return array
	 */
	public static function decode_meta( $raw ) {
		if ( is_array( $raw ) ) {
			return $raw;
		}
		if ( ! is_string( $raw ) || '' === $raw ) {
			return array();
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Encode provider meta for storage.
	 *
	 * @param array $meta Meta array.
	 * @return string
	 */
	public static function encode_meta( array $meta ) {
		$clean = array();
		if ( ! empty( $meta['ses_region'] ) ) {
			$region = sanitize_key( $meta['ses_region'] );
			if ( array_key_exists( $region, self::ses_regions() ) ) {
				$clean['ses_region'] = $region;
			}
		}
		if ( ! empty( $meta['mailgun_region'] ) ) {
			$mg = sanitize_key( $meta['mailgun_region'] );
			if ( in_array( $mg, array( 'us', 'eu' ), true ) ) {
				$clean['mailgun_region'] = $mg;
			}
		}
		if ( ! empty( $meta['zoho_region'] ) ) {
			$zh = sanitize_key( $meta['zoho_region'] );
			if ( array_key_exists( $zh, self::zoho_regions() ) ) {
				$clean['zoho_region'] = $zh;
			}
		}
		if ( ! empty( $meta['mailtrap_mode'] ) ) {
			$mode = sanitize_key( $meta['mailtrap_mode'] );
			if ( array_key_exists( $mode, self::mailtrap_modes() ) ) {
				$clean['mailtrap_mode'] = $mode;
			}
		}
		return wp_json_encode( $clean );
	}

	/**
	 * Public config for admin JS (no secrets).
	 *
	 * @return array
	 */
	public static function js_config() {
		$out = array();
		foreach ( self::all() as $id => $provider ) {
			$out[ $id ] = array(
				'id'                  => $id,
				'label'               => $provider['label'],
				'icon'                => $provider['icon'],
				'mark'                => $provider['mark'],
				'host'                => $provider['host'],
				'port'                => $provider['port'],
				'encryption'          => $provider['encryption'],
				'usernameLock'        => $provider['username_lock'],
				'mirrorPass'          => ! empty( $provider['mirror_pass'] ),
				'usernameFromSender'  => ! empty( $provider['username_from_sender'] ),
				'mapsConnection'      => ! empty( $provider['maps_connection'] ),
				'needsRegion'         => ! empty( $provider['needs_region'] ),
				'regionType'          => isset( $provider['region_type'] ) ? $provider['region_type'] : '',
				'usernameHint'        => $provider['username_hint'],
				'passwordHint'        => $provider['password_hint'],
				'help'                => $provider['help'],
			);
		}
		return array(
			'providers'      => $out,
			'count'          => count( $out ),
			'sesRegions'     => self::ses_regions(),
			'mailgunRegions' => self::mailgun_regions(),
			'zohoRegions'    => self::zoho_regions(),
			'mailtrapModes'  => self::mailtrap_modes(),
			'sesHostTemplate'=> 'email-smtp.{region}.amazonaws.com',
			'mailgunHosts'   => array(
				'us' => 'smtp.mailgun.org',
				'eu' => 'smtp.eu.mailgun.org',
			),
			'zohoHosts'      => array(
				'us' => 'smtp.zoho.com',
				'eu' => 'smtp.zoho.eu',
				'in' => 'smtp.zoho.in',
				'au' => 'smtp.zoho.com.au',
			),
			'mailtrapHosts'  => array(
				'live'    => 'live.smtp.mailtrap.io',
				'sandbox' => 'sandbox.smtp.mailtrap.io',
			),
		);
	}
}
