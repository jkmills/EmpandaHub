# Changelog

All notable changes to EmpandaHub are documented in this file.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.2.6] — 2026-05-18

### Added
- **User ↔ member link**: `users` table gains a `contact_id` FK to `contacts`. Migration backfills the existing user by creating a contact record from their name/email. New users created via the "Promote Member to User" flow are automatically linked.
- **Promote Member to User**: Settings now shows a "Promote Member to User" form that lists active members who don't yet have a login. Selecting a member, role, and password creates a user account linked to their contact record. The existing "Add User" form remains for staff/admin accounts not requiring membership.
- **Users table: Member column**: the user list in Settings shows a "Member" column with a link to the contact record for linked users.
- **Document preview**: the document detail page now renders an inline preview for browser-native formats (PDF, PNG, JPEG, GIF, WebP, SVG) above the description. Non-previewable formats continue to download. Preview is served through the authenticated `/documents/:id/preview` endpoint.

### Changed
- Release ZIP no longer includes `softaculous/`, `EmpandaHub.goal.md`, `RELEASING.md`, `docs/architecture.md`, `storage/`, or `.claude/` — none are needed by end users.
- `cron/daily.php` now covers all nightly tasks (membership transitions, dues reminders, grant alerts, engagement scores); documentation updated accordingly.

### Removed
- `softaculous/` directory removed. Softaculous integration is not being pursued; the ZIP + web installer is the sole supported install path.

---

## [1.2.5] — 2026-05-18

### Fixed
- Data backup/restore is now forward- and backward-compatible across schema versions. `restoreTable()` fetches live column names via `SHOW COLUMNS` and strips any row keys not present in the current schema before inserting, so a backup taken on an older version can be cleanly restored to a newer install (and vice versa) without crashing on unknown or removed columns.
- Document Library tables (`doc_categories`, `documents`, `document_share_links`) are now included in data backups and restores. Previously they were absent from `MODULE_TABLES`.
- `FK_MAP` and `SELF_REF` updated for document tables: `documents.category_id → doc_categories`, `document_share_links.document_id → documents`, `doc_categories.parent_id` self-referential FK all handled correctly during restore.

---

## [1.2.4] — 2026-05-18

### Fixed
- `hasUpdate()` no longer makes a live GitHub API call on every page load. It now reads only from the local cache, eliminating the source of the update banner being slow or absent. The cache is refreshed by: visiting Settings → Updates (always fetches fresh), running the daily cron, or clicking "Check for updates now."
- `installedDbVersion()` now sorts applied migration versions using PHP `version_compare` rather than `ORDER BY applied_at DESC`, which could return the wrong version when two migrations ran within the same second.
- Daily cron now refreshes the update cache so the nav banner appears proactively after a new release, without any manual action.

### Changed
- Settings → Updates page always fetches a fresh release check from GitHub on load, so the latest version is always visible without needing to click "Check for updates now."

---

## [1.2.3] — 2026-05-18

### Fixed
- Schema repair: adds `engagement_score`/`engagement_score_at` columns to `contacts` and creates the five document library tables on any install where `v1.2.0` and `v1.2.2` migrations were silently skipped. The fixed migration runner (shipped in v1.2.2 but only active from memory on the next upgrade) now processes this migration correctly.

---

## [1.2.2] — 2026-05-18

### Fixed
- Migration runner silently skipped any SQL file whose first non-empty line was a comment (`--`), recorded it as applied, and never retried. All v1.2.0 schema changes (engagement score columns, document library tables) were never applied on existing installs.
- Added `v1.2.2.sql` repair migration that re-applies the v1.2.0 schema additions idempotently — the runner now swallows duplicate-column and table-already-exists errors, so it is a safe no-op if v1.2.0 ran correctly and a full repair if it was skipped.
- In-app upgrade redesigned as a two-step process: Step 1 creates the backup and presents a download link **before** any files are changed; Step 2 (a separate confirmation form) applies the file replacement and migrations. Previously the backup download link only appeared after files were already replaced, making it unreachable if the upgrade broke the site.

---

## [1.2.1] — 2026-05-18

### Fixed
- Fatal error on every page load caused by `DocumentModel::queryOne()` redeclaring the base `Model::queryOne()` as `private` (PHP requires the override be `protected` or weaker). Removed the duplicate — the inherited method is identical.

---

## [1.2.0] — 2026-05-17

### Added
- **Donor Engagement Score**: contacts are automatically scored 0–100 based on donation recency, frequency, cumulative giving, event attendance, volunteer hours, and email engagement. Scores refresh daily via cron and on contact view when stale (>24 h). Badge displayed on contact card; sortable column in CRM list; at-risk donor widget on Dashboard shows lapsed donors ordered by score.
- **Document Library**: file storage with role-based visibility levels (All Staff / Staff Only / Admin Only / Super Admin Only), category organization, version history, cryptographic public share links with optional expiry and access logging. Files stored outside webroot (`storage/documents/`); executable file types blocked at upload.

### Changed
- CRM contacts list now supports sorting by engagement score (ascending or descending).

---

## [1.1.2] — 2026-05-17

### Added
- In-app upgrader now automatically creates a full data backup before replacing any files; backup is available to download from the upgrade results page.

### Changed
- Updates page: manual upgrade steps collapsed into a fallback `<details>` section; pre-upgrade checklist replaced with a description of what happens automatically.

---

## [1.1.1] — 2026-05-17

### Fixed
- Updates page had no permanent link; super admins can now always reach it via the Settings page header button.

---

## [1.1.0] — 2026-05-17

### Added
- In-app one-click upgrade: Settings → Updates now downloads the release ZIP from GitHub, replaces application files, and runs database migrations without leaving the admin panel. Includes pre-flight checks (ZipArchive extension, directory writability, stale lock detection).

### Fixed
- Logo upload silently failing when `public/uploads/` was not writable; now shows a clear error message.

---

## [1.0.0] — 2025-05-17

### Added
- Multi-tenant nonprofit management platform
- CRM module: contact management, deduplication, board position tracking
- Membership module: tiered memberships, dues tracking, renewal and expiry workflows
- Donors module: campaigns, LYBUNT/SYBUNT reports, donation receipts (PDF)
- Volunteers module: roster, hour logging, shift management, approval workflows
- Events module: event creation, registration, and check-in
- Grants module: funder database, grant pipeline, deadline tracking, progress reports
- Finance module: unified ledger across all financial modules, CSV export
- Data module: JSON backup/restore, selective data wipe
- Settings: organization branding (logo, color, timezone), module visibility toggles, user management
- Role-based access control: super_admin, admin, staff, volunteer, readonly
- Web-based installer (`install/install.php`)
- Docker development environment
- Audit log for all significant actions
- CSRF protection on all forms
- PDF receipt generation via TCPDF

[Unreleased]: https://github.com/jkmills/EmpandaHub/compare/v1.2.6...HEAD
[1.2.6]: https://github.com/jkmills/EmpandaHub/compare/v1.2.5...v1.2.6
[1.2.5]: https://github.com/jkmills/EmpandaHub/compare/v1.2.4...v1.2.5
[1.2.4]: https://github.com/jkmills/EmpandaHub/compare/v1.2.3...v1.2.4
[1.2.3]: https://github.com/jkmills/EmpandaHub/compare/v1.2.2...v1.2.3
[1.2.2]: https://github.com/jkmills/EmpandaHub/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/jkmills/EmpandaHub/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/jkmills/EmpandaHub/compare/v1.1.2...v1.2.0
[1.1.2]: https://github.com/jkmills/EmpandaHub/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/jkmills/EmpandaHub/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/jkmills/EmpandaHub/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/jkmills/EmpandaHub/releases/tag/v1.0.0
