# Architecture Reference

Technical reference for developers working on EmpandaHub.

---

## Directory Layout

```
EmpandaHub/
├── config/
│   └── config.php              # Runtime config (not in git; created by installer)
├── core/                       # Framework classes
│   ├── Auth.php                # Session auth, role checks, org module cache
│   ├── Controller.php          # Base controller: render, renderLayout, redirect, abort
│   ├── Csrf.php                # CSRF token generation and verification
│   ├── Database.php            # PDO singleton
│   ├── Flash.php               # Session-backed flash messages
│   ├── Mailer.php              # SMTP wrapper (PHPMailer)
│   ├── Model.php               # Base model: query, execute, insert helpers
│   ├── Router.php              # Route registration, module grouping, dispatch
│   ├── AuditLog.php            # Append-only audit log writer
│   └── helpers.php             # Global helper functions (fmt_date, humanize_action, …)
├── modules/                    # Feature modules
│   ├── auth/
│   ├── crm/
│   ├── dashboard/
│   ├── data/
│   ├── donors/
│   ├── events/
│   ├── finance/
│   ├── grants/
│   ├── membership/
│   ├── settings/
│   └── volunteers/
│       ├── controllers/        # Controller + Model classes
│       └── views/              # PHP view templates
├── views/
│   └── layout/
│       ├── header.php          # <html> open, <head>, sets --brand CSS property
│       ├── nav.php             # Sidebar, topbar, opens .main-wrapper
│       └── footer.php          # Closes .main-wrapper, <script> tags
├── public/                     # Apache DocumentRoot
│   ├── index.php               # Bootstrap, route registration, dispatch
│   ├── assets/
│   │   ├── css/app.css         # Full design system (no preprocessor)
│   │   └── js/app.js           # Vanilla JS (sidebar, flash, live preview, …)
│   └── uploads/                # Org logos and uploaded files (not in git)
├── install/
│   ├── install.php             # One-page web installer
│   ├── schema.sql              # Full database schema
│   └── seed.sql                # Development seed data
├── cron/                       # Scheduled jobs (run via cron or Task Scheduler)
├── tests/playwright/           # End-to-end test suite
└── docker-compose.yml          # Development environment
```

---

## Request Lifecycle

```
Browser → Apache mod_rewrite → public/index.php
             │
             ├── Bootstrap: load config, core classes, module models
             ├── Auth::start() — resume session
             │
             └── Router::dispatch($uri, $method)
                    │
                    ├── Match route against registered patterns
                    ├── Module gate check (Auth::orgModules())
                    │     └── If module disabled → Flash + redirect to dashboard
                    │
                    ├── require_once ControllerFile.php
                    ├── new Controller()
                    └── $ctrl->action($params)
                              │
                              ├── Auth::require() / Auth::requireRole()
                              ├── Model queries (PDO, org_id scoped)
                              └── $this->renderLayout(view, data)
                                        │
                                        ├── loadOrgData() if not in $data
                                        ├── ob_start() → render view → ob_get_clean()
                                        ├── extract($data) into layout scope
                                        ├── require header.php
                                        ├── require nav.php
                                        ├── echo flash messages
                                        ├── echo $content
                                        └── require footer.php
```

---

## Module System

### Registration

Each module is a directory under `modules/` containing `controllers/` and `views/`. To register a new module:

**1. Add to the settings module list** (`modules/settings/controllers/SettingsController.php`):
```php
$allMods = ['crm', 'membership', 'donors', 'volunteers', 'events', 'grants', 'finance', 'email'];
//                                                                                          ^^^^^
```

**2. Wrap routes in `$router->module()`** (`public/index.php`):
```php
$router->module('email', function ($r) {
    $r->get('/email',          'EmailController', 'index');
    $r->get('/email/create',   'EmailController', 'create');
    $r->post('/email',         'EmailController', 'store');
});
```

**3. Register the controller class** (`core/Router.php` → `resolveController()`):
```php
'EmailController' => 'email',
```

That's everything. The settings toggle, nav visibility, route gating, and session caching are all automatic.

### How gating works

`Router::module(string $module, callable $callback)` stores the module key on each route registered inside the callback. On dispatch:

```php
if ($route['module'] !== '' && Auth::check()) {
    $modules = Auth::orgModules();               // reads $_SESSION['org_modules'] (cached)
    if (isset($modules[$module]) && !$modules[$module]) {
        Flash::warning('...');
        header('Location: ' . APP_URL . '/dashboard');
        exit;
    }
}
```

`Auth::orgModules()` reads from `$_SESSION['org_modules']` after the first request, avoiding a DB call on every page load. `Auth::clearOrgCache()` is called in `SettingsController::updateOrg()` so changes take effect immediately for the saving user's session.

### Routes outside the module gate

Some routes must remain accessible even when a module is disabled — e.g., public share links for the planned Document Library. Register these outside any `$router->module()` call:

```php
// Public document download — works even when Documents module is disabled
$router->get('/docs/:token', 'DocumentController', 'publicDownload');

$router->module('documents', function ($r) {
    $r->get('/documents',  'DocumentController', 'index');
    // ...
});
```

---

## Database Conventions

### Multi-tenancy

Every table has an `org_id` column. All queries must include `WHERE org_id = ?` using `Auth::orgId()`. The base Model class does not enforce this automatically — it is the controller's responsibility.

```php
// Good
$this->model->query('SELECT * FROM contacts WHERE org_id = ? AND id = ?', [$orgId, $id]);

// Bad — would leak data across orgs
$this->model->query('SELECT * FROM contacts WHERE id = ?', [$id]);
```

### Foreign keys

All FKs reference the primary key of the parent table. Cross-module FKs (e.g., `donations.contact_id → contacts.id`) are defined in the schema and enforced by InnoDB. `SET FOREIGN_KEY_CHECKS = 0` is used only during the wipe operation in `DataModel`.

### Timestamps

Every table has `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` and `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`.

### Soft deletes

There are no soft deletes. Deletion is hard. Contacts that should be retained use the `merged_into_id` self-reference instead of deletion.

---

## Auth & Sessions

`Auth::start()` is called once in `public/index.php` before routing. It configures the session cookie as `HttpOnly`, `SameSite=Lax`, and (when `APP_ENV=production`) `Secure`.

Session structure:
```php
$_SESSION = [
    'user_id'     => int,
    'org_id'      => int,
    'role'        => string,      // 'super_admin'|'admin'|'staff'|'volunteer'|'readonly'
    'name'        => string,
    'org_modules' => array,       // cached from config_json; cleared on settings save
];
```

Role hierarchy (most → least privileged): `super_admin → admin → staff → volunteer → readonly`

`Auth::isAtLeast('staff')` returns `true` for staff, admin, and super_admin.

---

## CSRF Protection

Every mutating form includes `<?= Csrf::field() ?>` which renders a hidden `_token` input. Controllers that handle POST requests call `$this->requirePost()` which internally calls `Csrf::verify()`. Verification checks `$_POST['_token']` against `$_SESSION['csrf_token']`. Failure returns a 403.

---

## Design System

The full CSS design system lives in `public/assets/css/app.css`. No preprocessor or build step.

### Brand theming

The org's brand color is set as an inline CSS custom property on `<html>`:

```html
<html style="--brand: #2563eb">
```

All brand-colored elements use `var(--brand)`. Derived tokens are computed via `color-mix()`:

```css
:root {
    --brand-50:  color-mix(in srgb, var(--brand)  8%, #fff);
    --brand-100: color-mix(in srgb, var(--brand) 15%, #fff);
    --brand-600: color-mix(in srgb, var(--brand) 90%, #000);
    --brand-700: color-mix(in srgb, var(--brand) 78%, #000);
}
```

Because these reference `var(--brand)`, they automatically update when the inline style on `<html>` changes — including the live preview in Settings.

### Sidebar variants

The sidebar supports three style variants applied as a class on `<nav class="sidebar">`:

| Class | Appearance |
|-------|-----------|
| *(none)* | Dark — `--gray-900` background, brand-tinted header section |
| `sidebar--light` | White background, brand color for active states |
| `sidebar--brand` | Brand color background throughout |

The active variant is stored in `organizations.config_json.sidebar_style` and applied in `views/layout/nav.php`.

---

## Audit Log

`AuditLog::record(string $action, string $entity, int $entityId)` writes to the `audit_log` table. Called by controllers after any mutating operation. The Recent Activity widget on the dashboard reads the last 20 entries for the org.

Action string convention: `module.verb` — e.g., `crm.contact_create`, `membership.dues_recorded`, `settings.org_update`.

---

## Adding a Page to an Existing Module

1. Add a route in `public/index.php` inside the relevant `$router->module()` block
2. Add the action method to the controller
3. Add the view file to `modules/{module}/views/`
4. If the action needs model data, add the query method to the model

No registration, no factory, no DI container — just files.
