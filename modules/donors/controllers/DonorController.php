<?php
declare(strict_types=1);

class DonorController extends Controller
{
    private DonorModel $model;

    public function __construct() { $this->model = new DonorModel(); }

    private function layout(string $view, array $data = []): void
    {
        $this->renderLayout($view, $data);
    }

    // ---- Campaigns ----
    public function campaigns(array $p): void
    {
        Auth::require();
        $campaigns = $this->model->allCampaigns(Auth::orgId());
        $this->layout('modules/donors/views/campaigns.php', ['pageTitle' => 'Campaigns', 'campaigns' => $campaigns]);
    }

    public function campaignCreate(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->layout('modules/donors/views/campaign_form.php', ['pageTitle' => 'New Campaign', 'campaign' => [], 'errors' => []]);
    }

    public function campaignStore(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        [$data, $errors] = $this->validateCampaign();
        if ($errors) { $this->layout('modules/donors/views/campaign_form.php', ['pageTitle' => 'New Campaign', 'campaign' => $_POST, 'errors' => $errors]); return; }
        $data['org_id'] = Auth::orgId();
        $id = $this->model->insertCampaign($data);
        AuditLog::record('campaign.create', 'campaign', $id);
        Flash::success('Campaign created.');
        $this->redirect('/donors/campaigns');
    }

    public function campaignEdit(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $c = $this->model->findCampaign((int)$p['id'], Auth::orgId());
        if (!$c) $this->abort(404);
        $this->layout('modules/donors/views/campaign_form.php', ['pageTitle' => 'Edit Campaign', 'campaign' => $c, 'errors' => []]);
    }

    public function campaignUpdate(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        [$data, $errors] = $this->validateCampaign();
        if ($errors) { $this->layout('modules/donors/views/campaign_form.php', ['pageTitle' => 'Edit Campaign', 'campaign' => $_POST, 'errors' => $errors]); return; }
        $this->model->updateCampaign((int)$p['id'], Auth::orgId(), $data);
        Flash::success('Campaign updated.');
        $this->redirect('/donors/campaigns');
    }

    // ---- Donations ----
    public function index(array $p): void
    {
        Auth::require();
        $donations = $this->model->allWithContact(Auth::orgId());
        $this->layout('modules/donors/views/index.php', ['pageTitle' => 'Donations', 'donations' => $donations]);
    }

    public function create(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $contacts  = (new CrmModel())->allActive(Auth::orgId());
        $campaigns = $this->model->allCampaigns(Auth::orgId());
        $this->layout('modules/donors/views/form.php', ['pageTitle' => 'New Donation', 'donation' => [], 'contacts' => $contacts, 'campaigns' => $campaigns, 'errors' => []]);
    }

    public function store(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        [$data, $errors] = $this->validateDonation();
        if ($errors) {
            $this->layout('modules/donors/views/form.php', ['pageTitle' => 'New Donation', 'donation' => $_POST,
                'contacts' => (new CrmModel())->allActive(Auth::orgId()), 'campaigns' => $this->model->allCampaigns(Auth::orgId()), 'errors' => $errors]);
            return;
        }
        $data['org_id'] = Auth::orgId();
        $id = $this->model->recordDonation($data, Auth::orgId());
        AuditLog::record('donation.create', 'donation', $id);
        Flash::success('Donation recorded.');
        $this->redirect('/donors/' . $id);
    }

    public function show(array $p): void
    {
        Auth::require();
        $d = $this->model->findDetail((int)$p['id'], Auth::orgId());
        if (!$d) $this->abort(404);
        $this->layout('modules/donors/views/show.php', ['pageTitle' => 'Donation', 'donation' => $d]);
    }

    public function edit(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $d = $this->model->findDetail((int)$p['id'], Auth::orgId());
        if (!$d) $this->abort(404);
        $contacts  = (new CrmModel())->allActive(Auth::orgId());
        $campaigns = $this->model->allCampaigns(Auth::orgId());
        $this->layout('modules/donors/views/form.php', ['pageTitle' => 'Edit Donation', 'donation' => $d, 'contacts' => $contacts, 'campaigns' => $campaigns, 'errors' => []]);
    }

    public function update(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        [$data, $errors] = $this->validateDonation();
        if ($errors) {
            $this->layout('modules/donors/views/form.php', ['pageTitle' => 'Edit Donation', 'donation' => $_POST,
                'contacts' => (new CrmModel())->allActive(Auth::orgId()), 'campaigns' => $this->model->allCampaigns(Auth::orgId()), 'errors' => $errors]);
            return;
        }
        $this->model->update((int)$p['id'], Auth::orgId(), $data);
        Flash::success('Donation updated.');
        $this->redirect('/donors/' . $p['id']);
    }

    public function lybunt(array $p): void
    {
        Auth::require();
        $list = $this->model->lybunt(Auth::orgId());
        $this->layout('modules/donors/views/lybunt.php', ['pageTitle' => 'LYBUNT Report', 'list' => $list]);
    }

    public function sybunt(array $p): void
    {
        Auth::require();
        $list = $this->model->sybunt(Auth::orgId());
        $this->layout('modules/donors/views/sybunt.php', ['pageTitle' => 'SYBUNT Report', 'list' => $list]);
    }

    public function batchReceipt(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $fy   = (int)($_GET['fy'] ?? date('Y'));
        $list = $this->model->batchFiscalYear(Auth::orgId(), $fy);
        $this->layout('modules/donors/views/batch_receipt.php', ['pageTitle' => 'Batch Receipts FY' . $fy, 'list' => $list, 'fy' => $fy]);
    }

    private function validateCampaign(): array
    {
        $data = ['name' => trim($_POST['name'] ?? ''), 'description' => trim($_POST['description'] ?? ''), 'goal_amount' => $_POST['goal_amount'] ?: null, 'start_date' => $_POST['start_date'] ?: null, 'end_date' => $_POST['end_date'] ?: null, 'is_active' => (int)($_POST['is_active'] ?? 1)];
        $errors = [];
        if (!$data['name']) $errors['name'] = 'Name required.';
        return [$data, $errors];
    }

    private function validateDonation(): array
    {
        $data   = [
            'contact_id'     => $_POST['contact_id'] ?: null,
            'campaign_id'    => $_POST['campaign_id'] ?: null,
            'amount'         => (float)($_POST['amount'] ?? 0),
            'donated_on'     => $_POST['donated_on'] ?? date('Y-m-d'),
            'is_recurring'   => (int)($_POST['is_recurring'] ?? 0),
            'recur_interval' => $_POST['recur_interval'] ?: null,
            'is_anonymous'   => (int)($_POST['is_anonymous'] ?? 0),
            'method'         => trim($_POST['method'] ?? ''),
            'note'           => trim($_POST['note'] ?? ''),
        ];
        $errors = [];
        if ($data['amount'] <= 0) $errors['amount'] = 'Amount must be > 0.';
        if (!$data['donated_on']) $errors['donated_on'] = 'Date required.';
        return [$data, $errors];
    }
}
