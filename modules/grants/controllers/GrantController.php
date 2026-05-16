<?php
declare(strict_types=1);

class GrantController extends Controller
{
    private GrantModel $model;

    public function __construct() { $this->model = new GrantModel(); }

    private function layout(string $view, array $data = []): void
    {
        $this->renderLayout($view, $data);
    }

    public function index(array $p): void
    {
        Auth::require();
        $pipeline = $this->model->pipeline(Auth::orgId());
        $grouped  = [];
        foreach ($pipeline as $g) $grouped[$g['status']][] = $g;
        $deadlines = $this->model->upcomingDeadlines(Auth::orgId(), 30);
        $this->layout('modules/grants/views/index.php', ['pageTitle' => 'Grants', 'grouped' => $grouped, 'deadlines' => $deadlines]);
    }

    public function create(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $funders = $this->model->allFunders(Auth::orgId());
        $this->layout('modules/grants/views/form.php', ['pageTitle' => 'New Grant', 'grant' => [], 'funders' => $funders, 'errors' => []]);
    }

    public function store(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        [$data, $errors] = $this->validateGrant();
        if ($errors) { $this->layout('modules/grants/views/form.php', ['pageTitle' => 'New Grant', 'grant' => $_POST, 'funders' => $this->model->allFunders(Auth::orgId()), 'errors' => $errors]); return; }
        $data['org_id'] = Auth::orgId();
        $id = $this->model->insert($data);
        AuditLog::record('grant.create', 'grant', $id);
        Flash::success('Grant created.');
        $this->redirect('/grants/' . $id);
    }

    public function show(array $p): void
    {
        Auth::require();
        $grant   = $this->model->findDetail((int)$p['id'], Auth::orgId());
        if (!$grant) $this->abort(404);
        $reports = $this->model->reports((int)$p['id'], Auth::orgId());
        $this->layout('modules/grants/views/show.php', ['pageTitle' => $grant['title'], 'grant' => $grant, 'reports' => $reports]);
    }

    public function edit(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $grant   = $this->model->find((int)$p['id'], Auth::orgId());
        if (!$grant) $this->abort(404);
        $funders = $this->model->allFunders(Auth::orgId());
        $this->layout('modules/grants/views/form.php', ['pageTitle' => 'Edit Grant', 'grant' => $grant, 'funders' => $funders, 'errors' => []]);
    }

    public function update(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        [$data, $errors] = $this->validateGrant();
        if ($errors) { $this->layout('modules/grants/views/form.php', ['pageTitle' => 'Edit Grant', 'grant' => $_POST, 'funders' => $this->model->allFunders(Auth::orgId()), 'errors' => $errors]); return; }
        $this->model->update((int)$p['id'], Auth::orgId(), $data);
        Flash::success('Grant updated.');
        $this->redirect('/grants/' . $p['id']);
    }

    public function addReport(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $title = trim($_POST['title'] ?? '');
        $due   = $_POST['due_date'] ?? '';
        if (!$title || !$due) { Flash::error('Title and due date required.'); $this->redirect('/grants/'.$p['id']); }
        $this->model->addReport(['org_id' => Auth::orgId(), 'grant_id' => (int)$p['id'], 'title' => $title, 'due_date' => $due, 'notes' => $_POST['notes'] ?? '']);
        Flash::success('Report due date added.');
        $this->redirect('/grants/'.$p['id']);
    }

    public function submitReport(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $this->model->updateReport((int)$p['report_id'], ['submitted_date' => $_POST['submitted_date'] ?? date('Y-m-d'), 'notes' => $_POST['notes'] ?? '']);
        Flash::success('Report marked submitted.');
        $this->redirect('/grants/'.$p['id']);
    }

    public function award(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        $amount = (float)($_POST['amount_awarded'] ?? 0);
        $date   = $_POST['awarded_date'] ?? date('Y-m-d');
        if ($amount <= 0) { Flash::error('Award amount required.'); $this->redirect('/grants/'.$p['id']); }
        $this->model->recordAward((int)$p['id'], Auth::orgId(), $amount, $date);
        AuditLog::record('grant.awarded', 'grant', (int)$p['id'], "amount=$amount");
        Flash::success('Grant marked as awarded and transaction recorded.');
        $this->redirect('/grants/'.$p['id']);
    }

    // Funders
    public function funders(array $p): void
    {
        Auth::require();
        $funders = $this->model->allFunders(Auth::orgId());
        $this->layout('modules/grants/views/funders.php', ['pageTitle' => 'Funders', 'funders' => $funders]);
    }

    public function funderCreate(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->layout('modules/grants/views/funder_form.php', ['pageTitle' => 'New Funder', 'funder' => [], 'errors' => []]);
    }

    public function funderStore(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $data   = ['org_id' => Auth::orgId(), 'name' => trim($_POST['name'] ?? ''), 'contact_name' => trim($_POST['contact_name'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'phone' => trim($_POST['phone'] ?? ''), 'website' => trim($_POST['website'] ?? ''), 'notes' => trim($_POST['notes'] ?? '')];
        $errors = [];
        if (!$data['name']) $errors['name'] = 'Name required.';
        if ($errors) { $this->layout('modules/grants/views/funder_form.php', ['pageTitle' => 'New Funder', 'funder' => $_POST, 'errors' => $errors]); return; }
        $id = $this->model->insertFunder($data);
        Flash::success('Funder added.');
        $this->redirect('/grants/funders');
    }

    public function funderEdit(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $funder = $this->model->findFunder((int)$p['id'], Auth::orgId());
        if (!$funder) $this->abort(404);
        $this->layout('modules/grants/views/funder_form.php', ['pageTitle' => 'Edit Funder', 'funder' => $funder, 'errors' => []]);
    }

    public function funderUpdate(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $data = ['name' => trim($_POST['name'] ?? ''), 'contact_name' => trim($_POST['contact_name'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'phone' => trim($_POST['phone'] ?? ''), 'website' => trim($_POST['website'] ?? ''), 'notes' => trim($_POST['notes'] ?? '')];
        $this->model->updateFunder((int)$p['id'], Auth::orgId(), $data);
        Flash::success('Funder updated.');
        $this->redirect('/grants/funders');
    }

    private function validateGrant(): array
    {
        $data = [
            'funder_id'        => (int)($_POST['funder_id'] ?? 0),
            'title'            => trim($_POST['title']            ?? ''),
            'description'      => trim($_POST['description']      ?? ''),
            'status'           => $_POST['status']                ?? 'prospect',
            'amount_requested' => $_POST['amount_requested']      ?: null,
            'deadline_date'    => $_POST['deadline_date']         ?: null,
            'submitted_date'   => $_POST['submitted_date']        ?: null,
            'period_start'     => $_POST['period_start']          ?: null,
            'period_end'       => $_POST['period_end']            ?: null,
            'notes'            => trim($_POST['notes']            ?? ''),
        ];
        $errors = [];
        if (!$data['funder_id']) $errors['funder_id'] = 'Funder required.';
        if (!$data['title'])     $errors['title']     = 'Title required.';
        return [$data, $errors];
    }
}
