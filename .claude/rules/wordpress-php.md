# WordPress PHP (VMS Multi Mailer)

- Follow WordPress Coding Standards.
- Prefix all public symbols with `vms_msg_` or `VMS_MSG_`.
- Text domain: `vms-elements-multiple-smtp-email-gateway`.
- Sanitize all input; escape all output; verify nonces and capabilities on privileged actions.
- Do not hardcode mail provider credentials or API keys.
- Prefer modular files under `includes/`, `admin/`, and `assets/` instead of growing the main plugin file.
