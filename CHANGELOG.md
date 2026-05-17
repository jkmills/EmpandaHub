# Changelog

All notable changes to EmpandaHub are documented in this file.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

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

[Unreleased]: https://github.com/jkmills/EmpandaHub/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/jkmills/EmpandaHub/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/jkmills/EmpandaHub/releases/tag/v1.0.0
