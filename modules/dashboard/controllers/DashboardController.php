<?php
declare(strict_types=1);

class DashboardController extends Controller
{
    public function index(array $params): void
    {
        Auth::require();

        $orgId = Auth::orgId();
        $db    = Database::getInstance();

        $memModel   = new MembershipModel();
        $donModel   = new DonorModel();
        $volModel   = new VolunteerModel();
        $grantModel = new GrantModel();

        $activeMembers   = $memModel->activeCount($orgId);
        $expiringMembers = $memModel->expiringCount($orgId, 30);
        $donMtd          = $donModel->mtd($orgId);
        $donYtd          = $donModel->ytd($orgId);
        $volMtd          = $volModel->mtdHours($orgId);
        $volPending      = $volModel->pendingCount($orgId);
        $upcomingEvents  = (new EventModel())->upcoming($orgId, 3);
        $nextDeadlines   = $grantModel->nextDeadlines($orgId, 3);

        $auditStmt = $db->prepare(
            'SELECT al.*, u.name AS user_name
             FROM audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.org_id = ?
             ORDER BY al.created_at DESC
             LIMIT 10'
        );
        $auditStmt->execute([$orgId]);
        $auditLog = $auditStmt->fetchAll();

        $this->renderLayout('modules/dashboard/views/index.php', [
            'pageTitle'       => 'Dashboard',
            'activeMembers'   => $activeMembers,
            'expiringMembers' => $expiringMembers,
            'donMtd'          => $donMtd,
            'donYtd'          => $donYtd,
            'volMtd'          => $volMtd,
            'volPending'      => $volPending,
            'upcomingEvents'  => $upcomingEvents,
            'nextDeadlines'   => $nextDeadlines,
            'auditLog'        => $auditLog,
        ]);
    }
}
