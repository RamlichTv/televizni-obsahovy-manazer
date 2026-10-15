<p align="center">
  <a href="https://systom.cz" target="_blank">
    <img src="TOM_logotext.png" width="600" alt="TOM Logo">
  </a>
</p>

# TOM - Televizní Obsahový Manažer

Centralized CMS for managing content shown on kiosk-mode screens/TVs.

## Overview

TOM is a Laravel 12 application for:

- Managing media content (files and text) with lifecycle states
- Building reusable modules with sanitized HTML templates and optional CSS/JS
- Composing profile page layouts (grid-based) and assigning them to screens
- Rendering screen output through a dedicated endpoint (`/screen/{screen_uuid}`)
- Managing users, roles, and permissions
- Auditing operations through a structured event log

The system is designed for internal digital signage workflows with role-based access control and operational logging.

## Core Features

- Authentication by username/password (not email login)
- ACL with roles + direct permissions
- Content lifecycle: `Unpublished -> Published -> Archived -> Deleted`
- File validation by content type rules (extensions and max size)
- Tags and content type management from UI
- Profile editor with grid pages, row/column sizing, module placement, overlap/bounds checks
- Module editor with:
  - File-backed HTML/CSS/JS revisions
  - Draft preview rendering
  - Template sanitization
  - Data providers (`content_objects` by default)
- User dashboard widget layout editor (drag/drop + resize)
- Screen rendering with:
  - Profile playback settings (auto-rotate, loop, transition)
  - Optional auto-refresh
  - IP allow/block lists
- Event logging with severity and related model links
- Optional automatic Tailwind rebuild queue for module template class scanning

## Tech Stack

- Backend: PHP 8.2+, Laravel 12
- Frontend: Blade, Alpine.js, Tailwind CSS v4, Vite
- Code editor: CodeMirror 6 (module template/code editing)
- DB (default): SQLite
- Queue (default): Database queue driver
- Testing: PHPUnit 11

## Requirements

- PHP `^8.2`
- Composer `^2.2`
- Node.js `^20.19.0 || >=22.12.0`
- npm `>=8.0.0`
- SQLite (default) or another supported Laravel DB

## Quick Start

1. Install dependencies

```bash
composer install
npm install
```

2. Create environment file and app key

```bash
cp .env.example .env
php artisan key:generate
```

3. Prepare database (default SQLite)

```bash
touch database/database.sqlite
php artisan migrate
```

4. Seed baseline data (roles, permissions, statuses, default admin, etc.)

```bash
php artisan db:seed
```

5. Expose public storage

```bash
php artisan storage:link
```

6. Start development services

```bash
composer run dev
```

`composer run dev` starts:

- Laravel dev server
- Queue listener
- Log tail (`pail`)
- Vite dev server

The app will be available at `http://localhost:8000` (default Laravel serve port).

## One-Command Setup

You can also run:

```bash
composer run setup
```

This installs dependencies, creates `.env`, generates key, migrates DB, installs npm packages, and builds assets.

Note: `setup` does not run seeders. Run `php artisan db:seed` if you need baseline records and default admin.

## Default Seeded Admin Account

When `php artisan db:seed` is executed, `DefaultUserSeeder` creates:

- Username: `admin`
- Email: `vanicek@spsjc.cz`
- Password: `Aa123456`

Change this password immediately in non-local environments.

## Main Domains

### Content

- CRUD for content objects with status transitions
- File uploads stored under `storage/app/public/users/{username}/content`
- Tag attachment/detachment
- Content type constraints:
  - Allowed extension list
  - Max file size
  - File vs text type behavior

### Modules

- Catalog of reusable UI modules assigned to categories
- Template assets are stored as files on the `public` disk
- Revisioned file paths:
  - `users/{owner}/modules/{category_slug}/{module_uuid}/v{revision}.html`
  - `.../v{revision}.css`
  - `.../v{revision}.js`
- HTML templates are sanitized before persistence
- Module JS editing is restricted to administrators
- Module rendering supports data providers (default: content objects)

### Profiles and Pages

- Profile has multiple pages
- Each page has a grid (`row_count`, `column_count`) and track sizes
- Modules are placed with row/column start + span
- Server-side validation prevents overlap and out-of-bounds placements
- Placement-level `config_overrides` JSON is supported

### Screens

- Screens can be assigned to profiles
- Public render route:
  - `GET /screen/{screen_uuid}`
- Supports:
  - Interactive controls (prev/next page)
  - Auto-rotation and transitions from profile settings
  - Optional auto-refresh interval
  - IP whitelist/blacklist access control

### Users, Roles, Permissions

- Role-based + direct permission model
- `Administrator` role has full access
- Hidden/system role controls are restricted for non-admins
- Safeguards:
  - Cannot delete own account
  - Cannot delete last administrator
  - Cannot delete `Administrator` role

### Events and Audit Trail

- Central event logging via `App\Support\Events\EventLogger`
- Tracks operation type, severity, actor, and related models
- Includes auth events (login, logout, failed login)

## Module Template Language

Templates use a lightweight renderer:

- Escaped variable output:
  - `{{ module.name }}`
  - `{{ config.title }}`
- List loops:
  - `{{#each items}} ... {{/each}}`
- Named sections (truthy value or list):
  - `{{#payload}} ... {{/payload}}`

Context keys available during rendering:

- `module`
- `config`
- `payload`
- `items`
- `meta`
- `this` (inside loops/sections)

### Example

```html
<section>
  <h3>{{ config.title }}</h3>
  {{#each items}}
    <article>
      <p>{{ name }}</p>
      <small>{{ type.name }}</small>
    </article>
  {{/each}}
</section>
```

## Tailwind Build Automation for Module Templates

Module templates are file-backed, so dynamic classes are dumped into:

- `resources/tailwind/module-templates.html`

This file is scanned by Tailwind (`resources/css/app.css` includes it via `@source`).

Optional auto-build behavior is controlled by env vars:

| Variable | Default | Description |
| --- | --- | --- |
| `MODULE_TAILWIND_BUILD_ENABLED` | `false` | Enables queueing Tailwind rebuilds after module changes |
| `MODULE_TAILWIND_BUILD_COMMAND` | `npm run build` | Command executed by queued job |
| `MODULE_TAILWIND_BUILD_QUEUE` | `module-builds` | Queue name used by build job |
| `MODULE_TAILWIND_BUILD_DELAY_SECONDS` | `15` | Delay before job dispatch |
| `MODULE_TAILWIND_BUILD_TIMEOUT_SECONDS` | `600` | Build job timeout |

If enabled, make sure:

- Queue worker is running
- Worker environment has Node.js/npm available

Example worker command:

```bash
php artisan queue:work --queue=module-builds,default
```

## Scheduler

Scheduled every minute (`routes/console.php`):

- `content:publish` (auto-publish scheduled content)
- `content:archive` (auto-archive expired content)

In development:

```bash
php artisan schedule:work
```

In production, use a cron entry calling `php artisan schedule:run` every minute.

## Background Processes (Production)

Use two separate background mechanisms:

- `cron` for Laravel scheduler (`schedule:run`)
- A daemon (recommended: `systemd`) for queue workers (`queue:work`)

### 1. Scheduler via Crontab

Edit crontab for your deploy user (`www-data`, `forge`, etc.):

```bash
crontab -e
```

Add:

```cron
* * * * * cd /path/to/televizni-obsahovy-manazer && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Replace:

- `/path/to/televizni-obsahovy-manazer` with your real project path
- `/usr/bin/php` with your PHP binary path (`which php`)

### 2. Queue Worker Daemon via systemd

Create `/etc/systemd/system/tom-queue.service`:

#### ❗**NOTE:**

``The user and group should have permissions to node_modules and use the right Node.js version``

```ini
[Unit]
Description=TOM Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/path/to/televizni-obsahovy-manazer
ExecStart=/usr/bin/php /path/to/televizni-obsahovy-manazer/artisan queue:work --queue=module-builds,default --sleep=1 --tries=1 --timeout=600 --max-time=3600
ExecReload=/usr/bin/php /path/to/televizni-obsahovy-manazer/artisan queue:restart
StandardOutput=append:/path/to/televizni-obsahovy-manazer/storage/logs/queue.log
StandardError=append:/path/to/televizni-obsahovy-manazer/storage/logs/queue.log
KillSignal=SIGTERM
TimeoutStopSec=30

[Install]
WantedBy=multi-user.target
```

Then enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable tom-queue.service
sudo systemctl start tom-queue.service
```

Useful operations:

```bash
# Check status
sudo systemctl status tom-queue.service

# Follow logs
journalctl -u tom-queue.service -f

# Graceful worker reload after deploy
sudo systemctl reload tom-queue.service
```

### Deploy Note

After each deployment, run:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan queue:restart
```

This ensures workers pick up fresh code safely.

## Configuration Notes

- Default DB: SQLite (`database/database.sqlite`)
- Default queue: `database`
- Login field: `username`
- Health endpoint: `GET /up`

## Useful Commands

```bash
# Run tests
php artisan test

# Show routes
php artisan route:list --except-vendor

# Tail logs
php artisan pail

# Build frontend assets
npm run build

# Run Vite dev server only
npm run dev
```

## Project Structure

```text
app/
  Http/
    Controllers/Tom/...
    Requests/Tom/...
  Models/
  Policies/
  Support/
    Events/
    Modules/
      DataProviders/
      TailwindBuild/
      Template/
database/
  migrations/
  seeders/
resources/
  views/
  js/
  css/
  tailwind/
routes/
  web.php
  auth.php
  console.php
```

## Security Notes

- Change seeded default admin credentials immediately.
- Disable debug in production (`APP_DEBUG=false`).
- Restrict write permissions on storage and environment files.
- Run queue workers and scheduler under process supervision in production.
