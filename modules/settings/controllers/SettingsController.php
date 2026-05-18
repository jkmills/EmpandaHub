<?php
declare(strict_types=1);

class SettingsController extends Controller
{
    private function layout(string $view, array $data = []): void
    {
        $this->renderLayout($view, $data);
    }

    public function index(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $db    = Database::getInstance();
        $orgId = Auth::orgId();

        $users = $db->prepare('SELECT * FROM users WHERE org_id = ? ORDER BY name');
        $users->execute([$orgId]);

        // Active members without a user account (available to promote)
        $promotable = $db->prepare(
            'SELECT c.id, c.first_name, c.last_name, c.email, t.name AS tier_name
             FROM contacts c
             JOIN memberships m ON m.contact_id = c.id AND m.org_id = c.org_id
             JOIN membership_tiers t ON t.id = m.tier_id
             WHERE c.org_id = ?
               AND m.status IN ("active","grace","lifetime")
               AND c.email IS NOT NULL AND c.email != ""
               AND NOT EXISTS (SELECT 1 FROM users u WHERE u.org_id = c.org_id AND u.contact_id = c.id)
             ORDER BY c.last_name, c.first_name'
        );
        $promotable->execute([$orgId]);

        $this->layout('modules/settings/views/index.php', [
            'pageTitle'  => 'Settings',
            'users'      => $users->fetchAll(),
            'promotable' => $promotable->fetchAll(),
        ]);
    }

    public function promoteToUser(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        $db    = Database::getInstance();
        $orgId = Auth::orgId();

        $contactId = (int)($_POST['contact_id'] ?? 0);
        $role      = $_POST['role']     ?? 'readonly';
        $pass      = $_POST['password'] ?? '';

        if (!$contactId) { Flash::error('Please select a member.'); $this->redirect('/settings'); }
        if (strlen($pass) < 8) { Flash::error('Password must be 8+ characters.'); $this->redirect('/settings'); }

        // Verify contact is an active member of this org and has an email
        $contact = $db->prepare(
            'SELECT c.first_name, c.last_name, c.email
             FROM contacts c
             JOIN memberships m ON m.contact_id = c.id AND m.org_id = c.org_id
             WHERE c.id = ? AND c.org_id = ?
               AND m.status IN ("active","grace","lifetime")
               AND c.email IS NOT NULL AND c.email != ""
             LIMIT 1'
        );
        $contact->execute([$contactId, $orgId]);
        $member = $contact->fetch();

        if (!$member) { Flash::error('Member not found or does not have an active membership.'); $this->redirect('/settings'); }

        $name = trim($member['first_name'] . ' ' . $member['last_name']);
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        try {
            $db->prepare(
                'INSERT INTO users (org_id, contact_id, name, email, password, role) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$orgId, $contactId, $name, $member['email'], $hash, $role]);
            AuditLog::record('user.create', 'user', (int)$db->lastInsertId());
            Flash::success(htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ' has been given system access.');
        } catch (PDOException $e) {
            Flash::error('That email already has a user account.');
        }
        $this->redirect('/settings');
    }

    public function updateOrg(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        $orgId = Auth::orgId();
        $db    = Database::getInstance();

        $name      = trim($_POST['name']      ?? '');
        $color     = trim($_POST['primary_color'] ?? '#2563eb');
        $timezone  = trim($_POST['timezone']  ?? 'America/New_York');
        $fyStart   = (int)($_POST['fiscal_year_start'] ?? 1);

        if (!$name) { Flash::error('Name required.'); $this->redirect('/settings'); }

        // Handle logo upload
        $logo = null;
        if (!empty($_FILES['logo']['tmp_name'])) {
            $ext   = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allow = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            if (!in_array($ext, $allow)) { Flash::error('Invalid logo file type.'); $this->redirect('/settings'); }
            if (!is_writable(UPLOAD_DIR)) {
                Flash::error('Upload failed: uploads directory is not writable. Check folder permissions on public/uploads/.');
                $this->redirect('/settings');
            }
            $filename = 'logo_' . $orgId . '.' . $ext;
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $filename)) {
                Flash::error('Upload failed: could not save logo file. Check folder permissions on public/uploads/.');
                $this->redirect('/settings');
            }
            $logo = $filename;
        }

        // Module visibility
        $modules   = [];
        $allMods   = ['crm', 'membership', 'donors', 'volunteers', 'events', 'grants', 'finance', 'documents'];
        foreach ($allMods as $mod) $modules[$mod] = isset($_POST['mod_' . $mod]);

        $existing  = $db->prepare('SELECT config_json FROM organizations WHERE id = ?');
        $existing->execute([$orgId]);
        $config = json_decode($existing->fetchColumn() ?? '{}', true) ?: [];
        $config['modules'] = $modules;
        $sidebarStyle = in_array($_POST['sidebar_style'] ?? '', ['dark', 'light', 'brand'])
            ? $_POST['sidebar_style'] : 'dark';
        $config['sidebar_style'] = $sidebarStyle;

        $sets  = 'name=?, primary_color=?, timezone=?, fiscal_year_start=?, config_json=?, updated_at=NOW()';
        $vals  = [$name, $color, $timezone, $fyStart, json_encode($config)];
        if ($logo) { $sets .= ', logo=?'; $vals[] = $logo; }
        $vals[] = $orgId;

        $db->prepare("UPDATE organizations SET $sets WHERE id=?")->execute($vals);
        Auth::clearOrgCache();
        AuditLog::record('settings.org_update', 'organization', $orgId);
        Flash::success('Organization settings saved.');
        $this->redirect('/settings');
    }

    public function inviteUser(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        $db    = Database::getInstance();
        $orgId = Auth::orgId();

        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role']  ?? 'staff';
        $pass  = $_POST['password'] ?? '';

        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) { Flash::error('Name and valid email required.'); $this->redirect('/settings'); }
        if (strlen($pass) < 8) { Flash::error('Password must be 8+ characters.'); $this->redirect('/settings'); }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        try {
            $db->prepare('INSERT INTO users (org_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)')->execute([$orgId, $name, $email, $hash, $role]);
            AuditLog::record('user.create', 'user', (int)$db->lastInsertId());
            Flash::success('User created.');
        } catch (PDOException $e) {
            Flash::error('Email already exists.');
        }
        $this->redirect('/settings');
    }

    public function deactivateUser(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        $id = (int)$p['id'];
        if ($id === Auth::user()['id']) { Flash::error('Cannot deactivate yourself.'); $this->redirect('/settings'); }
        Database::getInstance()->prepare('UPDATE users SET is_active=0 WHERE id=? AND org_id=?')->execute([$id, Auth::orgId()]);
        AuditLog::record('user.deactivate', 'user', $id);
        Flash::success('User deactivated.');
        $this->redirect('/settings');
    }

    public function updateRole(array $p): void
    {
        Auth::requireRole('super_admin');
        $this->requirePost();
        $id   = (int)$p['id'];
        $role = $_POST['role'] ?? 'staff';
        Database::getInstance()->prepare('UPDATE users SET role=? WHERE id=? AND org_id=?')->execute([$role, $id, Auth::orgId()]);
        Flash::success('Role updated.');
        $this->redirect('/settings');
    }

    public function updates(array $p): void
    {
        Auth::requireRole('super_admin');
        $release  = Updater::latestRelease(forceRefresh: true); // always fresh on this page
        $current  = Updater::currentVersion();
        $dbVer    = Updater::installedDbVersion();
        $pending  = Updater::pendingMigrations();
        $hasUpdate = $release && version_compare($release['version'] ?? '0', $current, '>');

        $this->layout('modules/settings/views/updates.php', [
            'pageTitle'        => 'Updates',
            'release'          => $release,
            'currentVersion'   => $current,
            'dbVersion'        => $dbVer,
            'pending'          => $pending,
            'hasUpdate'        => $hasUpdate,
            'migrationResults' => [],
            'upgradeResults'   => [],
        ]);
    }

    public function checkUpdate(array $p): void
    {
        Auth::requireRole('super_admin');
        $this->requirePost();
        Updater::latestRelease(forceRefresh: true);
        Flash::success('Update check complete.');
        $this->redirect('/settings/updates');
    }

    public function runMigrations(array $p): void
    {
        Auth::requireRole('super_admin');
        $this->requirePost();

        $db = Database::getInstance();
        Updater::ensureMigrationsTable($db);

        $results  = Updater::runPendingMigrations();
        $release  = Updater::latestRelease();
        $current  = Updater::currentVersion();
        $dbVer    = Updater::installedDbVersion();
        $pending  = Updater::pendingMigrations();
        $hasUpdate = $release && version_compare($release['version'] ?? '0', $current, '>');

        $anyError = (bool)array_filter($results, fn($r) => $r['status'] === 'error');
        if (!$anyError && $results) {
            AuditLog::record('settings.migrations_run', 'organization', Auth::orgId());
        }

        $this->renderLayout('modules/settings/views/updates.php', [
            'pageTitle'        => 'Updates',
            'release'          => $release,
            'currentVersion'   => $current,
            'dbVersion'        => $dbVer,
            'pending'          => $pending,
            'hasUpdate'        => $hasUpdate,
            'migrationResults' => $results,
            'upgradeResults'   => [],
        ]);
    }

    public function upgradePerform(array $p): void
    {
        Auth::requireRole('super_admin');
        $this->requirePost();

        $release = Updater::latestRelease();
        $current = Updater::currentVersion();

        if (!$release || !version_compare($release['version'] ?? '0', $current, '>')) {
            Flash::error('No update available.');
            $this->redirect('/settings/updates');
            return;
        }

        if (!($release['download_url'] ?? null)) {
            Flash::error('No download URL available for this release. Download and apply manually.');
            $this->redirect('/settings/updates');
            return;
        }

        $preflight = Updater::preflightCheck();
        if ($preflight) {
            $this->renderLayout('modules/settings/views/updates.php', [
                'pageTitle'        => 'Updates',
                'release'          => $release,
                'currentVersion'   => $current,
                'dbVersion'        => Updater::installedDbVersion(),
                'pending'          => Updater::pendingMigrations(),
                'hasUpdate'        => true,
                'migrationResults' => [],
                'upgradeResults'   => [],
                'preflightErrors'  => $preflight,
            ]);
            return;
        }

        // Phase 1: create backup only — let user download before any files change
        $backupResult = $this->createPreUpgradeBackup();

        $this->renderLayout('modules/settings/views/updates.php', [
            'pageTitle'        => 'Updates',
            'release'          => $release,
            'currentVersion'   => $current,
            'dbVersion'        => Updater::installedDbVersion(),
            'pending'          => Updater::pendingMigrations(),
            'hasUpdate'        => true,
            'migrationResults' => [],
            'upgradeResults'   => [],
            'backupReady'      => true,
            'backupFile'       => $backupResult['file'],
            'backupEntry'      => $backupResult['entry'],
        ]);
    }

    public function upgradeApply(array $p): void
    {
        Auth::requireRole('super_admin');
        $this->requirePost();

        $current    = Updater::currentVersion();
        $release    = Updater::latestRelease();
        $backupFile = basename($_POST['backup_file'] ?? '');

        if (!$release || !version_compare($release['version'] ?? '0', $current, '>')) {
            Flash::error('No update available.');
            $this->redirect('/settings/updates');
            return;
        }

        $downloadUrl = $release['download_url'] ?? null;
        if (!$downloadUrl) {
            Flash::error('No download URL available.');
            $this->redirect('/settings/updates');
            return;
        }

        $upgradeResults = Updater::performUpgrade($downloadUrl, $release['version']);

        $anyError = (bool)array_filter($upgradeResults, fn($r) => $r['status'] === 'error');
        if (!$anyError) {
            AuditLog::record('settings.upgrade', 'organization', Auth::orgId(), "from v{$current} to v{$release['version']}");
        }

        $newVersion = Updater::currentVersion();
        $release2   = Updater::latestRelease();
        $hasUpdate2 = $release2 && version_compare($release2['version'] ?? '0', $newVersion, '>');

        $this->renderLayout('modules/settings/views/updates.php', [
            'pageTitle'        => 'Updates',
            'release'          => $release2,
            'currentVersion'   => $newVersion,
            'dbVersion'        => Updater::installedDbVersion(),
            'pending'          => Updater::pendingMigrations(),
            'hasUpdate'        => $hasUpdate2,
            'migrationResults' => [],
            'upgradeResults'   => $upgradeResults,
            'backupFile'       => $backupFile ?: null,
        ]);
    }

    private function createPreUpgradeBackup(): array
    {
        $backupDir = ROOT . '/config/backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        try {
            $orgId   = Auth::orgId();
            $db      = Database::getInstance();
            $stmt    = $db->prepare('SELECT name FROM organizations WHERE id = ?');
            $stmt->execute([$orgId]);
            $orgName = $stmt->fetchColumn() ?: 'Unknown';

            $modules = array_keys(DataModel::MODULE_TABLES);
            $model   = new DataModel();
            $data    = $model->backup($orgId, $modules);

            $payload = [
                'meta' => [
                    'app'         => 'EmpandaHub',
                    'version'     => Updater::currentVersion(),
                    'org_name'    => $orgName,
                    'exported_at' => date('c'),
                    'modules'     => $modules,
                    'note'        => 'Automatic pre-upgrade backup',
                ],
                'data' => $data,
            ];

            $filename = 'pre-upgrade-' . date('Y-m-d-His') . '.json';
            $path     = $backupDir . '/' . $filename;

            if (@file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
                throw new \RuntimeException('Could not write backup file to config/backups/.');
            }

            AuditLog::record('data.backup', 'org', $orgId, 'pre-upgrade automatic backup');

            return [
                'file'  => $filename,
                'entry' => ['status' => 'ok', 'step' => 'backup', 'message' => 'Pre-upgrade backup saved.', 'file' => $filename],
            ];
        } catch (\Throwable $e) {
            return [
                'file'  => null,
                'entry' => ['status' => 'warn', 'step' => 'backup', 'message' => 'Backup skipped: ' . $e->getMessage()],
            ];
        }
    }

    public function downloadUpgradeBackup(array $p): void
    {
        Auth::requireRole('super_admin');

        $file = basename($_GET['file'] ?? '');
        if (!preg_match('/^pre-upgrade-[\d-]+\.json$/', $file)) {
            http_response_code(400);
            exit('Invalid filename.');
        }

        $path = ROOT . '/config/backups/' . $file;
        if (!file_exists($path)) {
            http_response_code(404);
            exit('Backup file not found.');
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function clearUpgradeLock(array $p): void
    {
        Auth::requireRole('super_admin');
        $this->requirePost();
        Updater::clearUpgradeLock();
        Flash::success('Upgrade lock cleared.');
        $this->redirect('/settings/updates');
    }

    public function importForm(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->layout('modules/settings/views/import.php', ['pageTitle' => 'Import / Restore Backup']);
    }

    public function importRestore(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();

        // Validate file upload
        if (empty($_FILES['backup']['tmp_name']) || $_FILES['backup']['error'] !== UPLOAD_ERR_OK) {
            Flash::error('Please upload a valid backup file.');
            $this->redirect('/settings/import');
            return;
        }

        $raw = file_get_contents($_FILES['backup']['tmp_name']);
        $data = json_decode($raw, true);

        if (!is_array($data) || !isset($data['organization'])) {
            Flash::error('Invalid backup file — expected EmpandaHub JSON export.');
            $this->redirect('/settings/import');
            return;
        }

        $db    = Database::getInstance();
        $orgId = Auth::orgId();
        $org   = $data['organization'];
        $log   = [];
        $newUsers = [];

        // Restore org profile
        if (isset($_POST['restore_profile'])) {
            $sets  = [];
            $vals  = [];
            $fields = [
                'name'              => 'name',
                'primary_color'     => 'primary_color',
                'timezone'          => 'timezone',
                'fiscal_year_start' => 'fiscal_year_start',
            ];
            foreach ($fields as $jsonKey => $col) {
                if (isset($org[$jsonKey])) {
                    $sets[] = "$col = ?";
                    $vals[] = $org[$jsonKey];
                }
            }
            if ($sets) {
                $vals[] = $orgId;
                $db->prepare('UPDATE organizations SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
                $log[] = 'Organization profile restored.';
            }
        }

        // Restore module visibility
        if (isset($_POST['restore_modules']) && isset($org['modules'])) {
            $existing = $db->prepare('SELECT config_json FROM organizations WHERE id = ?');
            $existing->execute([$orgId]);
            $config = json_decode($existing->fetchColumn() ?? '{}', true) ?: [];
            $config['modules'] = $org['modules'];
            $db->prepare('UPDATE organizations SET config_json = ? WHERE id = ?')
               ->execute([json_encode($config), $orgId]);
            $log[] = 'Module visibility restored.';
        }

        // Restore users (new accounts only — skip existing emails)
        if (isset($_POST['restore_users']) && !empty($data['users'])) {
            $created = 0;
            foreach ($data['users'] as $u) {
                $email = trim($u['email'] ?? '');
                $name  = trim($u['name']  ?? '');
                $role  = $u['role'] ?? 'staff';
                if (!$email || !$name) continue;

                // Skip if email already exists in this org
                $exists = $db->prepare('SELECT id FROM users WHERE email = ? AND org_id = ?');
                $exists->execute([$email, $orgId]);
                if ($exists->fetchColumn()) continue;

                $tempPass = bin2hex(random_bytes(8)); // 16-char hex temp password
                $hash     = password_hash($tempPass, PASSWORD_BCRYPT);
                try {
                    $db->prepare('INSERT INTO users (org_id, name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?)')
                       ->execute([$orgId, $name, $email, $hash, $role, (int)($u['is_active'] ?? 1)]);
                    $newUsers[] = ['name' => $name, 'email' => $email, 'role' => $role, 'temp_password' => $tempPass];
                    $created++;
                } catch (PDOException) {
                    // duplicate on another org — skip silently
                }
            }
            if ($created) $log[] = "$created user(s) created from backup.";
            else $log[] = 'No new users to import (all emails already exist).';
        }

        AuditLog::record('settings.import', 'organization', $orgId);

        // Render results page directly (avoids losing $newUsers across redirect)
        $this->renderLayout('modules/settings/views/import_result.php', [
            'pageTitle' => 'Restore Complete',
            'log'       => $log,
            'newUsers'  => $newUsers,
        ]);
    }

    public function export(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $db    = Database::getInstance();
        $orgId = Auth::orgId();

        $org = $db->prepare('SELECT name, primary_color, timezone, fiscal_year_start, config_json, logo, created_at FROM organizations WHERE id = ?');
        $org->execute([$orgId]);
        $orgRow = $org->fetch() ?: [];

        $users = $db->prepare('SELECT name, email, role, is_active, created_at FROM users WHERE org_id = ? ORDER BY name');
        $users->execute([$orgId]);

        $payload = [
            'exported_at'  => date('c'),
            'exported_by'  => Auth::user()['email'] ?? '',
            'organization' => [
                'name'              => $orgRow['name']              ?? '',
                'primary_color'     => $orgRow['primary_color']     ?? '#2563eb',
                'timezone'          => $orgRow['timezone']          ?? 'America/New_York',
                'fiscal_year_start' => (int)($orgRow['fiscal_year_start'] ?? 1),
                'modules'           => json_decode($orgRow['config_json'] ?? '{}', true)['modules'] ?? [],
                'logo'              => $orgRow['logo']              ?? null,
                'created_at'        => $orgRow['created_at']        ?? null,
            ],
            'users' => $users->fetchAll(PDO::FETCH_ASSOC),
        ];

        $filename = 'empanda-settings-' . date('Y-m-d') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
