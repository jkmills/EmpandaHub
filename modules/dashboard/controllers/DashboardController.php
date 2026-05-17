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

        // At-risk donors: gave in the last 2 years but not in the last 12 months
        $modules   = Auth::orgModules();
        $atRisk    = [];
        if (!isset($modules['donors']) || $modules['donors']) {
            $arStmt = $db->prepare(
                "SELECT c.id, c.first_name, c.last_name, c.email,
                        c.engagement_score, MAX(d.donated_on) AS last_gift
                 FROM contacts c
                 JOIN donations d ON d.contact_id = c.id AND d.org_id = c.org_id
                 WHERE c.org_id = ?
                   AND c.merged_into_id IS NULL
                   AND d.donated_on >= DATE_SUB(NOW(), INTERVAL 2 YEAR)
                 GROUP BY c.id
                 HAVING last_gift < DATE_SUB(NOW(), INTERVAL 12 MONTH)
                 ORDER BY c.engagement_score ASC
                 LIMIT 10"
            );
            $arStmt->execute([$orgId]);
            $atRisk = $arStmt->fetchAll();
        }

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
            'atRisk'          => $atRisk,
        ]);
    }
}
