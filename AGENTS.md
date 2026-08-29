# VMS Elements Multi Mailer

WordPress plugin: **VMS Elements Multi Mailer – Multiple SMTP, Email Gateway & Logs**

| Field | Value |
| --- | --- |
| Slug / text domain | `vms-elements-multiple-smtp-email-gateway` |
| Main file | `vms-elements-multiple-smtp-email-gateway.php` |
| Version | `1.0.0` |
| Author | Shohel Hossain |
| Product URI | https://vmselements.com/product/vms-elements-multi-mailer-multiple-smtp-email-gateway-logs |
| Author / WP contributor | https://vmsuniverse.com (`vmsuniverse`, `developershohel`) |

## Purpose

Route WordPress emails through multiple SMTP accounts with smart gateways and detailed email logs. Native integrations: Amazon SES, SendGrid, Mailgun, Postmark, Cloudflare, toSend, Gmail, and more.

## Stack

- PHP (WordPress plugin APIs)
- WordPress hooks: `phpmailer_init`, `wp_mail`, admin settings, AJAX/REST as needed
- No Node build step unless one is added later

## Coding conventions

- Follow WordPress Coding Standards (WPCS) for PHP
- Prefix functions, classes, options, hooks, and nonces with `vms_msg_` or `VMS_MSG_`
- Escape output (`esc_html`, `esc_attr`, `esc_url`) and sanitize input (`sanitize_text_field`, `absint`, etc.)
- Use nonces and capability checks on all admin / AJAX actions
- Never commit SMTP credentials, API keys, or secrets; store them in WordPress options (encrypted when practical)
- Keep the main plugin file as bootstrap; put logic in `includes/`, admin UI in `admin/`, assets in `assets/`

## Layout

```text
vms-elements-multiple-smtp-email-gateway.php
includes/          # encryption, accounts, logger, mailer, queue, analytics, alerts, resend, health, maintenance, settings, conflicts, smoke-test, import-export, capabilities, CLI, activator
admin/             # menu, AJAX, views (dashboard + accounts + logs + settings)
assets/css|js      # admin UI
assets/screenshot-*.png
languages/         # .pot + bn_BD starter .po
bin/make-pot.php   # regenerate POT
uninstall.php      # drops custom tables on uninstall
```

## Behavior

- `phpmailer_init`: route by From → matched SMTP account, else global default; forced account ID for resend/test/failover
- Failure failover: on send failure retry ordered `fallback_priority` chain then global default (setting: `failure_failover`)
- Limit failover: same chain when daily limit reached
- Optional mail queue (`queue_enabled`) via WP-Cron / Action Scheduler
- Dashboard analytics; health + failure-spike email alerts
- Account JSON import/export (passwords omitted)
- Bulk log actions; filters by status/account/date/search
- Capability `manage_vms_msg` (admins get it on activate/upgrade)
- PHPMailer SMTP gateways: 39 presets; mapped host/port/encryption; original VMS CSS marks only
- Force From; SMTP health checks; smoke test; SMTP debug
- Log retention via daily cron; CSV export; Settings privacy body policy
- Conflict admin notice when other known SMTP plugins are active
- WP-CLI: `wp vms-msg test|prune|health`
- Passwords: OpenSSL AES-256-CBC using WP salts
- Tables: `{prefix}vms_msg_smtp_accounts`, `{prefix}vms_msg_email_logs`, `{prefix}vms_msg_mail_queue` (DB version currently 1.4.0)
- Admin: `manage_vms_msg` + nonces on all admin-post/AJAX actions
- Public plugin version stays `1.0.0` until launch; bump `VMS_MSG_DB_VERSION` for schema only

## Agent workflow

- Prefer small, focused changes; match existing patterns once files exist
- Do not edit LICENSE or unrelated files
- Do not commit unless the user asks
- After `git pull` on another machine, these files (`AGENTS.md`, `CLAUDE.md`, `.claude/`, `.cursor/`) restore shared agent context
