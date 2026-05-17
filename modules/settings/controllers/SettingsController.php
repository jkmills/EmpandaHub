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
        $users = Database::getInstance()->prepare('SELECT * FROM users WHERE org_id = ? ORDER BY name');
        $users->execute([Auth::orgId()]);
        $this->layout('modules/settings/views/index.php', ['pageTitle' => 'Settings', 'users' => $users->fetchAll()]);
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
            $filename = 'logo_' . $orgId . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $filename);
            $logo = $filename;
        }

        // Module visibility
        $modules   = [];
        $allMods   = ['crm', 'membership', 'donors', 'volunteers', 'events', 'grants', 'finance'];
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
