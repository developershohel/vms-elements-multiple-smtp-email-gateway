=== VMS Elements Multi Mailer – Multiple SMTP, Email Gateway & Logs ===
Contributors: vmsuniverse, developershohel
Donate link: https://vmselements.com/product/vms-elements-multi-mailer-multiple-smtp-email-gateway-logs
Tags: smtp, email, phpmailer, mail log, sendgrid, amazon ses, mailgun, postmark
Requires at least: 5.9
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Route WordPress email through multiple PHPMailer SMTP gateways with sender-based routing, logs, resend, health checks, and retention.

Product page: https://vmselements.com/product/vms-elements-multi-mailer-multiple-smtp-email-gateway-logs
Author / WordPress.org: https://vmsuniverse.com

== Description ==

VMS Elements Multi Mailer lets you configure many SMTP providers and route outgoing WordPress mail by the **From** (sender) address.

= Highlights =

* 39 PHPMailer SMTP gateway presets (Amazon SES, SendGrid, Mailgun, Postmark, Cloudflare Email, toSend, Brevo, Gmail, Microsoft 365, and more)
* Original VMS gateway icons only — no third-party logos
* Encrypted SMTP passwords (OpenSSL + WordPress salts)
* Email activity logs with failure reasons
* Resend any log entry through a chosen provider
* Force From email per account
* Daily send limits with ordered fallback chain + global default
* Automatic failure failover across the fallback chain
* Dashboard analytics (sent/failed, usage vs limits, top failures)
* Background mail queue (WP-Cron / Action Scheduler)
* Health + failure-spike email alerts
* Account JSON import/export (passwords not exported)
* Bulk log actions and date/account filters
* Custom capability `manage_vms_msg` for staff access
* Connection health checks and built-in smoke tests
* Log retention (WP-Cron) and CSV export
* Privacy controls for logged message bodies (truncate / omit / redact)
* Temporary SMTP debug capture
* WP-CLI: `wp vms-msg test|prune|health`
* Conflict notice when other SMTP plugins are active
* Translation-ready (`languages/*.pot`, bn_BD starter)

= Top gateway setup tips =

1. **SendGrid** — Host mapped to `smtp.sendgrid.net`. Username is `apikey`. Password = API key.
2. **Amazon SES** — Pick region. Use SES SMTP credentials (not AWS access keys).
3. **Mailgun** — Pick US/EU region. Use domain SMTP login + password.
4. **Postmark** — Enter Server API token only (used as username and password).
5. **Gmail** — Use an App Password. Sender email should match the Gmail address.
6. **Cloudflare Email** — Username `api_token`, password = API token with Email Sending permission.

= Quick start =

1. Activate the plugin.
2. Open **VMS Multi Mailer → SMTP Accounts**.
3. Choose a gateway, enter sender + credentials, save.
4. Set a **Global default** account.
5. Click **Check health**, then **Test email**.
6. Review **Email Logs**.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **VMS Elements Multi Mailer** through the Plugins screen.
3. Configure accounts under **VMS Multi Mailer**.

== Frequently Asked Questions ==

= Do I need a third-party SDK? =

No. Delivery uses WordPress PHPMailer over SMTP.

= What happens when a daily limit is reached? =

Accounts with a **Fallback priority** (1, 2, …) are tried in order, then the global default (if still under limit). If none is available, the send fails and is logged.

= Can I hide message bodies in logs for privacy? =

Yes. Under **Settings** choose truncate/omit body, or enable GDPR-style body redaction. Redacted logs cannot restore the original body on resend.

= Are passwords stored in plain text? =

No. Passwords/API keys are encrypted with OpenSSL using WordPress authentication salts.

= Is there WP-CLI support? =

Yes: `wp vms-msg test`, `wp vms-msg prune`, and `wp vms-msg health`.

== Screenshots ==

1. SMTP Accounts — gateway picker with mapped connection details
2. Configured accounts list with health, usage, and fallback priority
3. Email Logs — sent/failed activity with resend
4. Settings — retention, privacy, smoke test, and SMTP debug

== Changelog ==

= 1.0.0 =
* Initial release
* Multiple SMTP accounts with sender-email (From) routing via PHPMailer
* 39 gateway presets (Amazon SES, SendGrid, Mailgun, Postmark, Cloudflare Email, toSend, Brevo, Gmail, Microsoft 365, and more)
* Mapped host / port / encryption per gateway (credentials-focused setup)
* Encrypted SMTP passwords (OpenSSL + WordPress salts)
* Email activity logs with Sent / Failed status and error messages
* Resend logged emails with provider override
* Test email per account
* Force From email option per account
* Daily send limits with ordered fallback priority chain + global default
* Failure failover: retry next fallback account after SMTP send failure
* Dashboard analytics (7/30 day sent/failed, usage, top failures)
* Optional background mail queue
* Health-failure and failure-spike email alerts
* Account JSON import/export (no passwords)
* Bulk log delete + account/date filters
* Capability `manage_vms_msg` (granted to Administrators)
* SMTP connection health checks and Settings smoke test
* Log body privacy: full / truncate / omit / redact
* Temporary SMTP debug mode with captured conversation log
* Conflict notice for other active SMTP plugins
* WP-CLI commands: test, prune, health
* Log retention (WP-Cron) with manual prune
* Email log CSV export
* Translation template in /languages
* Settings tab with quick-start checklist
* Admin UI under VMS Multi Mailer (dashboard, accounts, logs, settings)

== Upgrade Notice ==

= 1.0.0 =
Initial release of VMS Elements Multi Mailer.
