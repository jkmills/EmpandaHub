# EmpandaHub

All-in-one nonprofit management platform — CRM, membership, donors, volunteers, events, grants, and more. Every module is independently toggleable from Settings so organizations only see what they use.

Built with PHP 8.1, MySQL 8, and Apache. No framework dependencies. Runs on any LAMP stack or in Docker.

---

## Modules

| Module | Key | Description |
|--------|-----|-------------|
| CRM | `crm` | Contacts, tags, notes, board positions, 360° profiles |
| Membership | `membership` | Tiers, dues, renewals, expiry tracking, history |
| Donors | `donors` | Donations, campaigns, LYBUNT/SYBUNT reports, receipts |
| Volunteers | `volunteers` | Roster, hour logging, shift management, approvals |
| Events | `events` | Event pages, registration, check-in |
| Grants | `grants` | Pipeline by status, funders, reports, deadlines |
| Finance | `finance` | Unified transaction ledger across all financial modules |
| Data | `data` | JSON backup, selective wipe, restore with FK remapping |

All modules can be enabled or disabled at any time from **Settings → Module Visibility**. Disabled modules hide their nav link and gate all routes at the router level — no controller code runs.

---

## Quick Start (Docker)

**Requirements:** Docker Desktop or Docker Engine + Compose

```bash
git clone https://github.com/jkmills/EmpandaHub.git
cd EmpandaHub
docker compose up -d
```

Then open **http://localhost:8080/install/install.php** and complete the one-page installer.

The installer creates `config/config.php`, runs the schema, and creates your first super admin account. Once done, the installer is automatically blocked from re-running.

> **Seed data for development**
> ```bash
> docker exec -i empandahub_db mysql -uroot -psecret empandahub < install/seed.sql
> ```
> Seeds 50 contacts, memberships, donations, volunteers, events, and grants.
> Login: `admin@example.com` / `password`

**Services after `docker compose up`:**

| Service | URL |
|---------|-----|
| Application | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| MySQL | localhost:3306 |

---

## User Roles

| Role | Access |
|------|--------|
| `super_admin` | Full access including data wipe and role management |
| `admin` | Full access except data wipe |
| `staff` | Read/write on all enabled modules |
| `volunteer` | Read-only on most records; can log own hours |
| `readonly` | Read-only across all modules |

Roles are assigned per-user in **Settings → Users**. Only `super_admin` can change another user's role to `super_admin`.

---

## Configuration

The application is configured via `config/config.php` (created by the installer). Key constants:

| Constant | Description |
|----------|-------------|
| `DB_HOST` | MySQL hostname |
| `DB_NAME` | Database name |
| `DB_USER` / `DB_PASS` | Database credentials |
| `APP_URL` | Full base URL with no trailing slash (e.g. `https://app.yourorg.org`) |
| `APP_ENV` | `development` or `production` |
| `SESSION_LIFETIME` | Session timeout in seconds (default 7200) |
| `UPLOAD_DIR` | Absolute path to the file upload directory |
| `UPLOAD_URL` | Public URL prefix for uploaded files |
| `MAIL_HOST` / `MAIL_USER` / `MAIL_PASS` | SMTP credentials for transactional email |
| `MAIL_PORT` | SMTP port (default 587) |
| `MAIL_FROM` / `MAIL_FROM_NAME` | From address for outbound mail |

> **Never commit `config/config.php` to version control.** It is listed in `.gitignore`.

---

## Running Tests

Tests use [Playwright](https://playwright.dev/). The application must be running on `http://localhost:8080` with seed data loaded.

```bash
npm install
npx playwright test
```

70 tests cover auth, all modules, security (CSRF, XSS, role enforcement), and the settings system. Tests run serially (`workers: 1`) because the settings module-visibility test mutates shared database state.

---

## Deployment

See **[docs/deployment.md](docs/deployment.md)** for full instructions covering:

- Production VPS (Ubuntu + Apache + MySQL + PHP 8.1)
- Docker in production with Nginx reverse proxy and SSL
- Shared/managed hosting (cPanel)
- File permissions, cron jobs, and environment hardening

---

## Architecture

See **[docs/architecture.md](docs/architecture.md)** for the technical reference:

- Directory layout
- Request lifecycle (Router → Controller → Model → View)
- Module system and how to add a new module
- Database conventions (`org_id` multi-tenancy, FK patterns)
- Session, CSRF, and auth design

---

## Tech Stack

- **PHP 8.1** — no framework; custom Router, Controller, Model, Auth, Csrf, Flash, AuditLog classes
- **MySQL 8** — InnoDB, utf8mb4, strict mode
- **Apache** — mod_rewrite routes all requests through `public/index.php`
- **Vanilla JS** — no build step; single `public/assets/js/app.js`
- **CSS custom properties** — design token system; `--brand` set per-org on the `<html>` element
- **Docker Compose** — development environment (PHP/Apache + MySQL + phpMyAdmin)
- **Playwright** — end-to-end test suite

---

## Roadmap

Feature gaps identified from competitive analysis of Bloomerang, NeonCRM, Little Green Light, Virtuous, DonorPerfect, Raiser's Edge NXT, Wild Apricot, Bonterra Apricot, MemberClicks, and Salesforce Nonprofit:

**Phase 1** — [#1 Email Marketing](../../issues/1) · [#2 SMS](../../issues/2) · [#3 Engagement Score](../../issues/3)

**Phase 2** — [#4 Peer-to-Peer Fundraising](../../issues/4) · [#5 Tribute Gifts](../../issues/5) · [#6 Matching Gifts](../../issues/6) · [#7 Auctions](../../issues/7)

**Phase 3** — [#8 Member Self-Service Portal](../../issues/8) · [#9 Planned Giving](../../issues/9) · [#10 Wealth Screening](../../issues/10)

**Phase 4** — [#11 Case Management](../../issues/11) · [#12 Impact Reporting](../../issues/12) · [#13 Learning Management](../../issues/13)

**Other** — [#14 Document Library (RBAC + public links)](../../issues/14)

---

## License

Proprietary. All rights reserved.
