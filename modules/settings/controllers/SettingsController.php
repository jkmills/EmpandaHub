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

        $sets  = 'name=?, primary_color=?, timezone=?, fiscal_year_start=?, config_json=?, updated_at=NOW()';
        $vals  = [$name, $color, $timezone, $fyStart, json_encode($config)];
        if ($logo) { $sets .= ', logo=?'; $vals[] = $logo; }
        $vals[] = $orgId;

        $db->prepare("UPDATE organizations SET $sets WHERE id=?")->execute($vals);
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
}
