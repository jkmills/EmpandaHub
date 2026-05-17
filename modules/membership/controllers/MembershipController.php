<?php
declare(strict_types=1);

class MembershipController extends Controller
{
    private MembershipModel $model;
    private TierModel $tiers;

    public function __construct()
    {
        $this->model = new MembershipModel();
        $this->tiers = new TierModel();
    }

    private function layout(string $view, array $data = []): void
    {
        $this->renderLayout($view, $data);
    }

    // ---- Memberships ----

    public function index(array $params): void
    {
        Auth::require();
        $memberships = $this->model->allWithDetails(Auth::orgId());
        $this->layout('modules/membership/views/index.php', ['pageTitle' => 'Memberships', 'memberships' => $memberships]);
    }

    public function create(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $tiers    = $this->tiers->allWithParent(Auth::orgId());
        $contacts = (new CrmModel())->allActive(Auth::orgId());
        $this->layout('modules/membership/views/form.php', [
            'pageTitle' => 'New Membership', 'membership' => [], 'tiers' => $tiers, 'contacts' => $contacts, 'errors' => [],
        ]);
    }

    public function store(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();

        [$data, $errors] = $this->validateMembership();
        if ($errors) {
            $this->layout('modules/membership/views/form.php', [
                'pageTitle' => 'New Membership', 'membership' => $_POST,
                'tiers' => $this->tiers->allWithParent(Auth::orgId()),
                'contacts' => (new CrmModel())->allActive(Auth::orgId()),
                'errors' => $errors,
            ]);
            return;
        }

        $tier = $this->tiers->find((int)$data['tier_id'], Auth::orgId());
        $data['org_id']   = Auth::orgId();
        $data['end_date'] = $this->model->computeEndDate($data['start_date'], $tier['billing_cycle'] ?? 'annual');
        $data['status']   = $tier['billing_cycle'] === 'lifetime' ? 'lifetime' : 'active';

        $id = $this->model->insert($data);
        $this->model->recordHistory($id, Auth::orgId(), (int)$data['contact_id'], 'created', null, $data['status']);
        AuditLog::record('membership.create', 'membership', $id);
        Flash::success('Membership created.');
        $this->redirect('/membership/' . $id);
    }

    public function show(array $params): void
    {
        Auth::require();
        $id = (int)$params['id'];
        $orgId = Auth::orgId();
        $membership = $this->model->findDetail($id, $orgId);
        if (!$membership) $this->abort(404);
        $dues    = $this->model->getDues($id, $orgId);
        $history = $this->model->getHistory((int)$membership['contact_id'], $orgId);
        $this->layout('modules/membership/views/show.php', [
            'pageTitle'  => 'Membership',
            'membership' => $membership,
            'dues'       => $dues,
            'history'    => $history,
        ]);
    }

    public function edit(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $membership = $this->model->find((int)$params['id'], Auth::orgId());
        if (!$membership) $this->abort(404);
        $this->layout('modules/membership/views/form.php', [
            'pageTitle' => 'Edit Membership', 'membership' => $membership,
            'tiers' => $this->tiers->allWithParent(Auth::orgId()),
            'contacts' => (new CrmModel())->allActive(Auth::orgId()),
            'errors' => [],
        ]);
    }

    public function update(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $id = (int)$params['id'];
        if (!$this->model->find($id, Auth::orgId())) $this->abort(404);

        [$data, $errors] = $this->validateMembership();
        if ($errors) {
            $this->layout('modules/membership/views/form.php', [
                'pageTitle' => 'Edit Membership', 'membership' => $_POST,
                'tiers' => $this->tiers->allWithParent(Auth::orgId()),
                'contacts' => (new CrmModel())->allActive(Auth::orgId()),
                'errors' => $errors,
            ]);
            return;
        }

        $this->model->update($id, Auth::orgId(), $data);
        AuditLog::record('membership.update', 'membership', $id);
        Flash::success('Membership updated.');
        $this->redirect('/membership/' . $id);
    }

    public function addDues(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $id = (int)$params['id'];
        $m  = $this->model->findDetail($id, Auth::orgId());
        if (!$m) $this->abort(404);

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) { Flash::error('Amount must be > 0.'); $this->redirect('/membership/' . $id); return; }

        // Default due_date to the membership's current end_date (what period they're paying for)
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : ($m['end_date'] ?? null);

        $this->model->addDues([
            'org_id'        => Auth::orgId(),
            'membership_id' => $id,
            'contact_id'    => $m['contact_id'],
            'due_date'      => $dueDate,
            'amount'        => $amount,
            'paid_on'       => $_POST['paid_on'] ?? date('Y-m-d'),
            'method'        => $_POST['method'] ?? '',
            'reference'     => $_POST['reference'] ?? '',
            'note'          => $_POST['note']   ?? '',
        ]);

        AuditLog::record('dues.payment', 'membership', $id, "amount=$amount");
        Flash::success('Payment recorded and membership renewed.');
        $this->redirect('/membership/' . $id);
    }

    public function editDues(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $membershipId = (int)$params['id'];
        $dueId        = (int)$params['dueId'];
        $membership   = $this->model->findDetail($membershipId, Auth::orgId());
        if (!$membership) $this->abort(404);
        $due = $this->model->findDues($dueId, Auth::orgId());
        if (!$due || (int)$due['membership_id'] !== $membershipId) $this->abort(404);

        $this->layout('modules/membership/views/dues_form.php', [
            'pageTitle'  => 'Edit Payment',
            'membership' => $membership,
            'due'        => $due,
        ]);
    }

    public function updateDues(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $membershipId = (int)$params['id'];
        $dueId        = (int)$params['dueId'];
        if (!$this->model->findDetail($membershipId, Auth::orgId())) $this->abort(404);
        $due = $this->model->findDues($dueId, Auth::orgId());
        if (!$due || (int)$due['membership_id'] !== $membershipId) $this->abort(404);

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) { Flash::error('Amount must be > 0.'); $this->redirect('/membership/' . $membershipId . '/dues/' . $dueId . '/edit'); return; }

        $this->model->updateDues($dueId, Auth::orgId(), [
            'due_date'  => $_POST['due_date']  ?: null,
            'paid_on'   => $_POST['paid_on']   ?? date('Y-m-d'),
            'amount'    => $amount,
            'method'    => $_POST['method']    ?? '',
            'reference' => $_POST['reference'] ?? '',
            'note'      => $_POST['note']      ?? '',
        ]);

        AuditLog::record('dues.update', 'dues_payment', $dueId, "amount=$amount");
        Flash::success('Payment updated.');
        $this->redirect('/membership/' . $membershipId);
    }

    public function duesExport(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $from = $_GET['from'] ?? date('Y-01-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $payments = $this->model->allDuesPayments(Auth::orgId(), $from, $to);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="dues-payments-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Member', 'Tier', 'Billing', 'Paid On', 'Due Date', 'Amount', 'Method', 'Reference', 'Note']);
        foreach ($payments as $p) {
            fputcsv($out, [
                $p['first_name'] . ' ' . $p['last_name'],
                $p['tier_name'], $p['billing_cycle'],
                $p['paid_on'], $p['due_date'] ?? '',
                number_format((float)$p['amount'], 2),
                $p['method'] ?? '', $p['reference'] ?? '', $p['note'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function duesIndex(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $from = $_GET['from'] ?? date('Y-01-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $payments = $this->model->allDuesPayments(Auth::orgId(), $from, $to);
        $this->layout('modules/membership/views/dues_index.php', [
            'pageTitle' => 'Dues Management',
            'payments'  => $payments,
            'from'      => $from,
            'to'        => $to,
        ]);
    }

    public function expiring(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $list = $this->model->expiringReport(Auth::orgId(), 30);
        $this->layout('modules/membership/views/expiring.php', ['pageTitle' => 'Expiring Memberships', 'list' => $list]);
    }

    public function overdue(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $list = $this->model->overdueReport(Auth::orgId());
        $this->layout('modules/membership/views/overdue.php', ['pageTitle' => 'Overdue Memberships', 'list' => $list]);
    }

    // ---- Tiers ----

    public function tierIndex(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $tiers = $this->tiers->allWithParent(Auth::orgId());
        $this->layout('modules/membership/views/tiers.php', ['pageTitle' => 'Membership Tiers', 'tiers' => $tiers]);
    }

    public function tierCreate(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $tiers = $this->tiers->allWithParent(Auth::orgId());
        $this->layout('modules/membership/views/tier_form.php', ['pageTitle' => 'New Tier', 'tier' => [], 'tiers' => $tiers, 'errors' => []]);
    }

    public function tierStore(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        [$data, $errors] = $this->validateTier();
        if ($errors) {
            $this->layout('modules/membership/views/tier_form.php', [
                'pageTitle' => 'New Tier', 'tier' => $_POST,
                'tiers' => $this->tiers->allWithParent(Auth::orgId()), 'errors' => $errors,
            ]);
            return;
        }
        $data['org_id'] = Auth::orgId();
        $id = $this->tiers->insert($data);
        AuditLog::record('tier.create', 'membership_tier', $id);
        Flash::success('Tier created.');
        $this->redirect('/membership/tiers');
    }

    public function tierEdit(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $tier  = $this->tiers->find((int)$params['id'], Auth::orgId());
        if (!$tier) $this->abort(404);
        $tiers = $this->tiers->allWithParent(Auth::orgId());
        $this->layout('modules/membership/views/tier_form.php', ['pageTitle' => 'Edit Tier', 'tier' => $tier, 'tiers' => $tiers, 'errors' => []]);
    }

    public function tierUpdate(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        $id = (int)$params['id'];
        [$data, $errors] = $this->validateTier();
        if ($errors) {
            $this->layout('modules/membership/views/tier_form.php', [
                'pageTitle' => 'Edit Tier', 'tier' => $_POST,
                'tiers' => $this->tiers->allWithParent(Auth::orgId()), 'errors' => $errors,
            ]);
            return;
        }
        $this->tiers->update($id, Auth::orgId(), $data);
        AuditLog::record('tier.update', 'membership_tier', $id);
        Flash::success('Tier updated.');
        $this->redirect('/membership/tiers');
    }

    private function validateMembership(): array
    {
        $data   = [];
        $errors = [];
        $data['contact_id'] = (int)($_POST['contact_id'] ?? 0);
        $data['tier_id']    = (int)($_POST['tier_id']    ?? 0);
        $data['start_date'] = $_POST['start_date'] ?? '';
        $data['status']     = $_POST['status']     ?? 'active';
        $data['notes']      = $_POST['notes']       ?? '';
        if (!$data['contact_id']) $errors['contact_id'] = 'Contact required.';
        if (!$data['tier_id'])    $errors['tier_id']    = 'Tier required.';
        if (!$data['start_date']) $errors['start_date'] = 'Start date required.';
        return [$data, $errors];
    }

    private function validateTier(): array
    {
        $data   = [];
        $errors = [];
        $data['name']             = trim($_POST['name'] ?? '');
        $data['description']      = trim($_POST['description'] ?? '');
        $data['billing_cycle']    = $_POST['billing_cycle']    ?? 'annual';
        $data['amount']           = (float)($_POST['amount']   ?? 0);
        $data['grace_period_days'] = (int)($_POST['grace_period_days'] ?? 30);
        $data['parent_tier_id']   = $_POST['parent_tier_id'] ? (int)$_POST['parent_tier_id'] : null;
        $data['is_active']        = 1;
        $data['sort_order']       = (int)($_POST['sort_order'] ?? 0);
        if (!$data['name']) $errors['name'] = 'Name required.';
        return [$data, $errors];
    }
}
