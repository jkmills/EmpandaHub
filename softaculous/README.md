# EmpandaHub — Softaculous Integration

This directory contains everything needed to integrate EmpandaHub with Softaculous, the auto-installer used by most cPanel/WHM and Plesk hosting environments.

---

## Contents

| File | Purpose |
|------|---------|
| `softaculous.xml` | Package metadata for submission or custom script registration |
| `install_adapter.php` | CLI installer called by Softaculous during automated installs |
| `README.md` | This file |

---

## Three paths to Softaculous installation

| Who you are | Path |
|-------------|------|
| Hosting provider running WHM + Softaculous | [Path A — Custom WHM script](#path-a--custom-whm-script-for-hosting-providers) |
| Developer submitting to the public Softaculous library | [Path B — Public library submission](#path-b--public-softaculous-library-submission) |
| End user on shared hosting | [Path C — End-user install experience](#path-c--end-user-install-experience) |

---

## Path A — Custom WHM script (for hosting providers)

This is the fastest way to make EmpandaHub available to your cPanel customers without waiting for public Softaculous library approval.

### Prerequisites

- WHM access with root or reseller privileges
- Softaculous installed and licensed on the server
- EmpandaHub release ZIP (`empandahub-vX.Y.Z.zip`) from [GitHub Releases](https://github.com/jkmills/EmpandaHub/releases)

### Step 1 — Prepare the package directory

SSH into the server and create the Softaculous custom scripts directory if it doesn't exist:

```bash
mkdir -p /usr/local/cpanel/whm/docroot/cgi/softaculous/scripts/empandahub
cd /usr/local/cpanel/whm/docroot/cgi/softaculous/scripts/empandahub
```

### Step 2 — Upload the required files

Upload these files into the `empandahub/` directory:

```
empandahub/
  softaculous.xml          ← from this repo's softaculous/ directory
  empandahub-v1.0.0.zip   ← release ZIP from GitHub Releases
  icon.png                 ← app icon (512×512, optional but recommended)
```

You can `wget` the release ZIP directly:

```bash
wget https://github.com/jkmills/EmpandaHub/releases/latest/download/empandahub-v1.0.0.zip
cp /path/to/repo/softaculous/softaculous.xml .
```

### Step 3 — Update softaculous.xml with the package path

Edit `softaculous.xml` and confirm the `<download>` URL points to the ZIP you uploaded, or update it to a publicly accessible URL:

```xml
<download>https://github.com/jkmills/EmpandaHub/releases/latest/download/empandahub-v1.0.0.zip</download>
<version>1.0.0</version>
```

Keep `<version>` in sync with the ZIP filename on every release.

### Step 4 — Register in Softaculous (WHM)

1. Log into **WHM → Plugins → Softaculous Apps Installer**
2. Navigate to **Scripts → Add/Manage Scripts** (or the equivalent in your Softaculous version)
3. Click **Add New Script**, point it to the `empandahub/` directory
4. Softaculous will read `softaculous.xml` and register the script

Customers will then see EmpandaHub listed under **Non Profit** in their cPanel → Softaculous panel.

### Step 5 — Set correct directory permissions

Ensure the PHP process can write to the app's config directory during install:

```bash
chmod 755 /usr/local/cpanel/whm/docroot/cgi/softaculous/scripts/empandahub/
```

### Keeping the custom script updated

When a new EmpandaHub release is available:

1. Download the new ZIP to the `empandahub/` script directory
2. Update `<version>` and `<download>` in `softaculous.xml`
3. Softaculous picks up the new version automatically on its next sync; existing installs see an "Update available" notice in cPanel

---

## Path B — Public Softaculous library submission

This makes EmpandaHub available to **all** Softaculous-powered hosting providers globally.

### Requirements

- EmpandaHub must have a publicly accessible homepage and demo (GitHub works)
- A stable release ZIP hosted at a permanent URL (GitHub Releases provides this)
- The `softaculous.xml` in this directory already satisfies the metadata requirements

### Submission process

1. Create a Softaculous developer account at **https://www.softaculous.com/member/register/**

2. Fill out the submission form at **https://www.softaculous.com/apps/submit/**  
   Use the values from `softaculous.xml` for each field:

   | Submission field | Value from softaculous.xml |
   |------------------|---------------------------|
   | Script Name | `EmpandaHub` |
   | Category | `Non Profit` |
   | Script Type | `PHP` |
   | DB Type | `MySQL` |
   | Min PHP | `8.1` |
   | Download URL | GitHub latest release URL |
   | Homepage | `https://github.com/jkmills/EmpandaHub` |
   | Support URL | `https://github.com/jkmills/EmpandaHub/issues` |

3. Attach the `softaculous.xml` file and a 512×512 app icon

4. Softaculous reviews submissions manually (typically 1–4 weeks)

5. Once approved, EmpandaHub appears in the **Non Profit** category across all Softaculous-powered hosts

### Maintaining library listing after approval

After each release:
- Update `<version>` and `<release_date>` in `softaculous.xml`
- Notify Softaculous via the developer portal that a new version is available
- They pull the updated ZIP from the `<download>` URL automatically once synced

---

## Path C — End-user install experience

Once EmpandaHub is registered (via Path A or Path B), this is what a hosting customer sees.

### cPanel install walkthrough

1. **Log into cPanel** → scroll to the **Softaculous Apps Installer** section → click it

2. In Softaculous, search for **"EmpandaHub"** or browse to **Non Profit**

3. Click **EmpandaHub** → click **Install Now**

4. Fill in the installation form:

   | Field | Notes |
   |-------|-------|
   | **Choose Protocol** | Select `https://` if you have SSL |
   | **Choose Domain** | Select your domain |
   | **In Directory** | Leave blank to install at root; enter a folder name (e.g., `hub`) to install at `yourdomain.com/hub` |
   | **Database Name** | Auto-generated; you can customise |
   | **Database User** | Auto-generated |
   | **Organization Name** | Your nonprofit or association name |
   | **Admin Name** | Full name of the initial administrator |
   | **Admin Email** | Login email for the admin account |
   | **Admin Password** | Must be 8+ characters |

5. Click **Install** — Softaculous will:
   - Extract the EmpandaHub files to your chosen directory
   - Create the MySQL database and user
   - Run `softaculous/install_adapter.php` to build the schema, seed the database, and write `config/config.php`
   - Display the login URL and credentials on the completion screen

6. **Bookmark the login URL** shown on the success screen. It will be `https://yourdomain.com/` or `https://yourdomain.com/<directory>/`

### Post-install steps (important)

After Softaculous completes installation, log in as admin and do the following:

**Security**
- Go to **File Manager** in cPanel and delete the `install/` directory (or at minimum `install/install.php` and `install/upgrade.php`)
- Ensure `config/config.php` has permissions `640` (readable only by the web server user)

**Email / SMTP**
- Go to **Settings → Organization** and fill in your SMTP details so the app can send emails (receipts, notifications)

**Branding**
- Upload your organization logo and set your primary brand color in **Settings → Organization**

**Modules**
- Enable or disable feature modules (CRM, Membership, Donors, etc.) under **Settings → Organization → Modules**

---

## How install_adapter.php works

When Softaculous triggers an install, it calls `install_adapter.php` via CLI with all the connection details as arguments:

```bash
php softaculous/install_adapter.php \
  --db-host=localhost \
  --db-name=cpuser_empanda \
  --db-user=cpuser_empanda \
  --db-pass=generatedpassword \
  --org-name="River Valley Food Bank" \
  --admin-name="Jane Smith" \
  --admin-email=jane@rvfb.org \
  --admin-pass=SecurePass123 \
  --app-url=https://rvfb.org
```

The adapter:
1. Validates all required arguments
2. Creates the database if it doesn't exist
3. Runs `install/schema.sql` to build all 26 tables (including the `migrations` tracking table)
4. Seeds the `migrations` table with the current version as a baseline
5. Creates the organization record and super_admin user
6. Writes `config/config.php` with all runtime constants
7. Exits 0 on success, 1 on failure (Softaculous reads the exit code)

The adapter is hardened to CLI-only — it returns HTTP 403 if accessed via a browser.

### Testing the adapter locally

```bash
# From the EmpandaHub root directory
php softaculous/install_adapter.php \
  --db-host=127.0.0.1 \
  --db-name=empanda_test \
  --db-user=root \
  --db-pass="" \
  --org-name="Test Nonprofit" \
  --admin-name="Admin User" \
  --admin-email=admin@example.com \
  --admin-pass=TestPass123 \
  --app-url=http://localhost:8080
```

Expected output:
```
EmpandaHub installed successfully.
Login: http://localhost:8080
Admin: admin@example.com
```

---

## Softaculous upgrades

When Softaculous detects a new EmpandaHub version (via the updated `<version>` in `softaculous.xml`), the user sees an **Update** button in their cPanel Softaculous panel.

Clicking **Update** causes Softaculous to:
1. Back up the current installation (files + database)
2. Extract the new release ZIP over the existing files  
3. **Not** overwrite `config/config.php` or `public/uploads/` (protected paths)

After Softaculous finishes the file update, the user **must** apply database migrations:
- Log in to the app → **Settings → Updates → Run Migrations**
- Or via CLI: `php install/migrate.php`
- Or via the web runner: `https://yourdomain.com/install/upgrade.php` (then delete it)

For future releases, the release workflow (`.github/workflows/release.yml`) automatically publishes the correct ZIP. Update `softaculous.xml` version fields as part of each release.

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|-------------|-----|
| White screen after install | `config/config.php` not written | Check directory permissions; run `chmod 755 config/` then retry |
| "Already installed" error | `config/config.php` already exists | Delete it and re-run the adapter |
| DB connection failed | Wrong credentials or MySQL not accessible | Verify host is `localhost` (not `127.0.0.1`) on shared hosting |
| Blank page on all routes | `mod_rewrite` not enabled | Enable `AllowOverride All` in Apache config |
| 500 error | PHP version mismatch | Ensure PHP 8.1+ is selected in cPanel → MultiPHP Manager |
| Uploads not saving | `public/uploads/` not writable | `chmod 755 public/uploads/` |
