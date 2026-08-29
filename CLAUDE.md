@AGENTS.md

## Claude Code

- Treat `AGENTS.md` as the shared source of truth for this repo (Cursor and other agents use it too).
- Keep project-shared instructions in `AGENTS.md`, `.claude/rules/`, and `.cursor/rules/` so `git push` / `git pull` syncs them across devices.
- Put machine-only notes in `CLAUDE.local.md` (gitignored) — never commit secrets, local URLs, or personal tokens.
- Prefer WordPress-safe patterns: capability checks, nonces, sanitization, escaping, and prefixed identifiers (`vms_msg_` / `VMS_MSG_`).
- When adding SMTP / API gateway code, never hardcode credentials; use options APIs and document required settings.
