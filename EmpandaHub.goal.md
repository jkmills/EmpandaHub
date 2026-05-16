Build a self-hosted nonprofit management platform in PHP 8.1 + MySQL 8 on Apache shared hosting (CrocWeb). No framework, no Node build step, no Composer beyond PHPMailer and a PDF lib. PDO only with prepared statements everywhere. Vanilla JS — progressive enhancement, no front-end framework. All requests route through public/index.php via mod_rewrite.

Architecture: multi-tenant with an organizations table as root. Every data row carries org_id. Five roles: super_admin, admin, staff, volunteer, readonly — enforced per controller action, not just per route. Sessions store user_id, org_id, role, name. CSRF token on every POST. htmlspecialchars on all output. Passwords via password_hash(PASSWORD_BCRYPT).

Core classes to build first (core/): Database.php (PDO singleton), Auth.php (login/logout/session/role checks), Router.php (pattern → controller), Controller.php (render/redirect/abort), Model.php (find/findAll/insert/update/delete), Csrf.php, Mailer.php (PHPMailer wrapper), Flash.php (one-time session messages).

Modules to build (modules/): auth, dashboard, crm, membership, donors, volunteers, events, grants, finance, settings.

Database tables (all InnoDB utf8mb4, all include created_at + updated_at): organizations, users, contacts, contact_tags, contact_notes, membership_tiers (supports parent_tier_id hierarchy), memberships, dues_payments, campaigns, donations, volunteers, volunteer_shifts, volunteer_hours, events, event_registrations, funders, grants, grant_reports, transactions (unified ledger — auto-populated by every money-movement model method), audit_log.

Module requirements:
- CRM: contacts CRUD, tags, notes timeline, CSV import with column mapping, export, duplicate merge.
- Membership: multi-tier CRUD with parent_tier_id hierarchy, membership CRUD, auto end_date from billing_cycle, grace period logic, one-click renewal, dues payment records, email receipts, overdue report.
- Donors: campaign CRUD with progress bar, donations CRUD, recurring flag, anonymous flag, PDF/print receipt, batch fiscal-year receipt, LYBUNT/SYBUNT reports.
- Volunteers: roster with skills/availability, hours log with staff approval queue, shift management, shift completion auto-creates hours records.
- Events: CRUD, registration with waitlist at capacity, mobile check-in view, cancellation with refund note, attendance report.
- Grants: funder CRUD, grant pipeline view grouped by status (prospect/drafting/submitted/awarded/declined), report due dates sub-table, deadline dashboard color-coded by urgency.
- Finance: revenue by source, donor income, dues income, event revenue, grant income, expense summary, fund balance, fiscal-year summary — all from transactions table, all date-range filtered, all CSV exportable.
- Dashboard: stat cards for active members + expiring, donations MTD + YTD, next 3 events, volunteer hours MTD + pending approvals, next 3 grant deadlines, last 10 audit entries.
- Settings: org profile (name, logo, primary_color, timezone, fiscal_year_start), user management (invite/role/deactivate), per-module visibility toggle stored in org config_json.

White-label: primary_color injected as --brand CSS variable on <html>. Org name and logo replace all app branding. Module visibility hides nav items per org. New org deployment = copy codebase + new config + run installer. No code edits required.

Development/Testing Build: We should build and host locally with docker compose for testing, but our plan will be to migrate to the shared hosting for the production build.

Installer (install/install.php): check PHP extensions → test DB → run schema.sql → create org → create super_admin → write config.php → show success + delete-me warning. Block if config.php already exists.

Cron (cron/daily.php): dues reminders 14 days before due, membership expiry notices 30 days out, grace/expired status transitions, grant deadline alerts 14 days out, grant report alerts 14 days out, recurring donation record generation (create record only — do not charge).

Build in this order — do not skip ahead: (1) file structure + core classes + .htaccess, (2) auth flow + installer, (3) CRM, (4) membership + dues, (5) donors, (6) transactions auto-insert + dashboard, (7) volunteers, (8) events, (9) grants, (10) cron, (11) finance reports, (12) audit log, (13) settings + white-label, (14) mobile responsive pass.

Done when: installer creates a clean install from zero, all modules work against a seeded org with 50 contacts/3 tiers/20 donations/5 events/10 volunteers/3 grants, role restrictions are enforced, all forms validate server-side with field-level errors, and no SQL errors appear in the error log during a full walkthrough. Start with step 1 only.
