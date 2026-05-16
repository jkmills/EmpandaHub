<?php
declare(strict_types=1);

class DashboardController extends Controller
{
    public function index(array $params): void
    {
        Auth::require();

        $orgId = Auth::orgId();
        $db    = Database::getInstance();

        $org = $db->prepare('SELECT * FROM organizations WHERE id = ?');
        $org->execute([$orgId]);
        $orgRow = $org->fetch();

        // Stats
        $memModel  = new MembershipModel();
        $donModel  = new DonorModel();
        $volModel  = new VolunteerModel();
        $grantModel = new GrantModel();

        $activeMembers  = $memModel->activeCount($orgId);
        $expiringMembers = $memModel->expiringCount($orgId, 30);
        $donMtd         = $donModel->mtd($orgId);
        $donYtd         = $donModel->ytd($orgId);
        $volMtd         = $volModel->mtdHours($orgId);
        $volPending     = $volModel->pendingCount($orgId);
        $upcomingEvents = (new EventModel())->upcoming($orgId, 3);
        $nextDeadlines  = $grantModel->nextDeadlines($orgId, 3);

        // Last 10 audit entries
        $auditStmt = $db->prepare('SELECT al.*, u.name AS user_name FROM audit_log al LEFT JOIN users u ON u.id = al.user_id WHERE al.org_id = ? ORDER BY al.created_at DESC LIMIT 10');
        $auditStmt->execute([$orgId]);
        $auditLog = $auditStmt->fetchAll();

        $data = [
            'pageTitle'       => 'Dashboard',
            'orgName'         => $orgRow['name']          ?? APP_NAME,
            'orgColor'        => $orgRow['primary_color'] ?? '#2563eb',
            'orgLogo'         => $orgRow['logo']           ?? '',
            'orgModules'      => json_decode($orgRow['config_json'] ?? '{}', true)['modules'] ?? [],
            'activeMembers'   => $activeMembers,
            'expiringMembers' => $expiringMembers,
            'donMtd'          => $donMtd,
            'donYtd'          => $donYtd,
            'volMtd'          => $volMtd,
            'volPending'      => $volPending,
            'upcomingEvents'  => $upcomingEvents,
            'nextDeadlines'   => $nextDeadlines,
            'auditLog'        => $auditLog,
        ];

        ob_start();
        $this->render('modules/dashboard/views/index.php', $data);
        $content = ob_get_clean();

        require ROOT . '/views/layout/header.php';
        require ROOT . '/views/layout/nav.php';
        echo '<div class="flash-messages">';
        foreach (Flash::get() as $msg) {
            echo '<div class="flash flash-' . htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
        echo '</div>';
        echo $content;
        require ROOT . '/views/layout/footer.php';
    }
}
