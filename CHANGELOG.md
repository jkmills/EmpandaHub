# Changelog

All notable changes to EmpandaHub are documented in this file.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

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

[Unreleased]: https://github.com/jkmills/EmpandaHub/compare/v1.1.2...HEAD
[1.1.2]: https://github.com/jkmills/EmpandaHub/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/jkmills/EmpandaHub/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/jkmills/EmpandaHub/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/jkmills/EmpandaHub/releases/tag/v1.0.0
