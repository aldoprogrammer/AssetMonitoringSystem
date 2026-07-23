---
name: eliminate-recurring-tasks
description: >-
  Converts recurring manual developer tasks (restart containers, re-run migrations,
  re-seed, re-install Passport, rebuild for code edits, re-start worker profiles)
  into automatic one-time or continuous behavior. Use when the user mentions
  recurring tasks, repeated docker compose restart/migrate/seed steps, hot reload,
  live overlay sync, or asks to make a workflow non-recurring.
---

# Eliminate Recurring Tasks

## Goal

Turn any repeated local ops step into something that happens automatically once (on start) or continuously (on file change). Do not document workarounds that require humans to re-run the same command after every edit.

## When To Apply

- README or docs tell developers to restart/recreate services after code or route changes
- Setup requires manual `migrate`, `db:seed`, `passport:install`, or `--profile workers` after every `up`
- Code changes require image rebuild even though only application overlay files changed
- User asks to eliminate recurring tasks or make a sample workflow non-recurring

## Workflow

1. Inventory recurring commands in README/scripts/CI comments.
2. For each item, choose one elimination pattern below and implement it in code (not only docs).
3. Delete or rewrite the recurring instructions so the happy path is one command.
4. Verify by running the happy path and proving the old recurring step is unnecessary.

## Elimination Patterns

### Code edits require restart/rebuild

- Mount service overlay at `/opt/service-overlay`.
- Entrypoint copies overlay into the app tree on start.
- Keep a 1s poll sync loop (Windows Docker bind mounts often miss inotify events).
- Clear `bootstrap/cache/config.php` and `bootstrap/cache/routes-*.php` on sync.
- Do not use `composer dump-autoload --optimize` in images meant for live overlay edits.
- Prefer `php artisan serve` (or any per-request PHP bootstrap) so synced files apply without process restart.

### Manual migrate / seed / Passport after every up

- Default `RUN_MIGRATIONS=true` when `APP_ENV=local`.
- For identity: default seed + Passport key/client setup when `PASSPORT_ENABLED=true` and `APP_ENV=local`.
- Keep flags overridable for production/CI.

### Workers behind a separate profile

- Remove the profile for local Compose so `docker compose up -d` starts APIs and workers together.
- Keep migrations in the entrypoint so workers do not race an empty schema.

### Docs that reintroduce recurrence

- Replace “restart after route changes”, “run these migrates once after up”, and “then start workers” with the single happy path.
- Keep rebuild instructions only for Dockerfile/scaffold/Composer/extension changes.

## Project Conventions (AssetMonitoringSystem)

- Scaffold: Laravel `^12.0` with `laravel/framework:^12.61.1` (Laravel 11 is blocked by Packagist advisories).
- Identity: `laravel/passport:^13.0` with Passport 13 UUID oauth migrations (`redirect_uris`, `grant_types`; no `oauth_personal_access_clients`).
- Live sync: `docker-compose.override.yml` mounts overlays at `/opt/service-overlay`; entrypoint poll-copies into the app tree.
- Auto bootstrap: local HTTP services migrate/seed/Passport on start; workers set `RUN_MIGRATIONS=false` to avoid Postgres race.
- Gateway: Docker DNS `resolver` + `rewrite` + variable `proxy_pass` so nginx does not crash when an upstream is briefly missing.
- Happy path:

```bash
docker compose build
docker compose up -d
```

## Verification Checklist

- [ ] `docker compose build` succeeds
- [ ] `docker compose up -d` alone brings APIs + workers + migrated schemas
- [ ] Login works without manual migrate/seed/passport commands
- [ ] Overlay route/app edit is visible without `docker compose restart`
- [ ] README no longer lists the eliminated recurring commands as required steps

## Anti-Patterns

- Adding “just remember to restart” notes instead of fixing reload
- Ignoring Composer security advisory build failures by disabling audit permanently when a secure major is available
- Mounting a partial `overlay/config` or `overlay/routes` directory over the full Laravel directories (use `/opt/service-overlay` copy-merge instead)
