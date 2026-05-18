# Releasing EmpandaHub

## Semantic Versioning

EmpandaHub uses [Semantic Versioning 2.0.0](https://semver.org/):

- **MAJOR** (`X.0.0`) — breaking changes or major feature redesigns
- **MINOR** (`1.X.0`) — new backward-compatible features
- **PATCH** (`1.0.X`) — bug fixes only

The canonical version is stored in the `VERSION` file at the repo root.

---

## Release Checklist

### 1. Write migrations (if schema changed)

Create `install/migrations/vX.Y.Z.sql` containing only the SQL that changes the schema since the prior release. Comment-only files are valid for releases with no schema changes (the installer skips them).

```sql
-- vX.Y.Z
ALTER TABLE contacts ADD COLUMN linkedin_url VARCHAR(255) DEFAULT NULL AFTER zip;
```

### 2. Update the version

```bash
echo "X.Y.Z" > VERSION
```

### 3. Update CHANGELOG.md

Move the `[Unreleased]` items into a new dated section:

```markdown
## [X.Y.Z] — YYYY-MM-DD

### Added
- ...

### Fixed
- ...
```

Update the comparison links at the bottom of CHANGELOG.md.

### 4. Commit and tag

```bash
git add VERSION CHANGELOG.md install/migrations/
git commit -m "Release vX.Y.Z"
git tag -a vX.Y.Z -m "Release vX.Y.Z"
git push origin main --tags
```

### 5. GitHub Actions builds the release

Pushing a `vX.Y.Z` tag triggers `.github/workflows/release.yml`, which:
- Verifies the tag matches the `VERSION` file
- Installs Composer dependencies (no-dev)
- Builds `empandahub-vX.Y.Z.zip` (excluding dev files, config, uploads)
- Extracts the relevant CHANGELOG.md section as release notes
- Creates a GitHub Release with the ZIP attached

---

## User Upgrade Flow

### Manual upgrade (shared hosting / ZIP) — preferred

1. **Back up the database** first: `mysqldump -u USER -p DBNAME > backup.sql`
2. Download `empandahub-vX.Y.Z.zip` from the GitHub Releases page.
3. Upload and extract the ZIP over the existing installation. `config/config.php` and `public/uploads/` are not in the ZIP and will not be touched.
4. Apply database migrations via one of:
   - **Web runner:** visit `https://yoursite.com/install/upgrade.php`, authenticate as super admin, run migrations, then **delete the file**.
   - **In-app:** Settings → Updates → Run Migrations
   - **CLI (VPS/SSH):** `php install/migrate.php`

### In-app one-click upgrade

Super admins see an "Update available" banner in the sidebar whenever a newer GitHub release exists (checked once per 24 hours via the GitHub API). The Settings → Updates page offers:

- **Upgrade Now** — downloads the release ZIP from GitHub, replaces application files (preserving `config/config.php` and `public/uploads/`), and runs pending migrations automatically. Requires ZipArchive, writable app directories, and outbound HTTPS to GitHub.
- **Run Migrations** — applies pending DB migrations without touching files (for manual file upgrades).

Pre-flight checks run before the upgrade begins and surface any blockers (missing extension, unwritable directories, stale lock file).

