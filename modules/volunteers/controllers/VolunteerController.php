<?php
declare(strict_types=1);

class VolunteerController extends Controller
{
    private VolunteerModel $model;

    public function __construct() { $this->model = new VolunteerModel(); }

    private function layout(string $view, array $data = []): void
    {
        $this->renderLayout($view, $data);
    }

    public function index(array $p): void
    {
        Auth::require();
        $roster = $this->model->roster(Auth::orgId());
        $pending = $this->model->pendingApprovals(Auth::orgId());
        $this->layout('modules/volunteers/views/index.php', ['pageTitle' => 'Volunteers', 'roster' => $roster, 'pending' => $pending]);
    }

    public function create(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $contacts = (new CrmModel())->allActive(Auth::orgId());
        $this->layout('modules/volunteers/views/form.php', ['pageTitle' => 'Add Volunteer', 'volunteer' => [], 'contacts' => $contacts, 'errors' => []]);
    }

    public function store(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $data   = $this->extract();
        $errors = [];
        if (!$data['contact_id']) $errors['contact_id'] = 'Contact required.';
        if ($errors) { $this->layout('modules/volunteers/views/form.php', ['pageTitle' => 'Add Volunteer', 'volunteer' => $_POST, 'contacts' => (new CrmModel())->allActive(Auth::orgId()), 'errors' => $errors]); return; }
        $data['org_id'] = Auth::orgId();
        $id = $this->model->insert($data);
        AuditLog::record('volunteer.create', 'volunteer', $id);
        Flash::success('Volunteer added.');
        $this->redirect('/volunteers/' . $id);
    }

    public function show(array $p): void
    {
        Auth::require();
        $v = $this->model->findDetail((int)$p['id'], Auth::orgId());
        if (!$v) $this->abort(404);
        $hours  = $this->model->getHours((int)$p['id'], Auth::orgId());
        $shifts = $this->model->allShifts(Auth::orgId());
        $this->layout('modules/volunteers/views/show.php', ['pageTitle' => 'Volunteer', 'volunteer' => $v, 'hours' => $hours, 'shifts' => $shifts]);
    }

    public function edit(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $v = $this->model->find((int)$p['id'], Auth::orgId());
        if (!$v) $this->abort(404);
        $contacts = (new CrmModel())->allActive(Auth::orgId());
        $this->layout('modules/volunteers/views/form.php', ['pageTitle' => 'Edit Volunteer', 'volunteer' => $v, 'contacts' => $contacts, 'errors' => []]);
    }

    public function update(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $id   = (int)$p['id'];
        $data = $this->extract();
        $this->model->update($id, Auth::orgId(), $data);
        Flash::success('Volunteer updated.');
        $this->redirect('/volunteers/' . $id);
    }

    public function logHours(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff', 'volunteer');
        $this->requirePost();
        $hours = (float)($_POST['hours'] ?? 0);
        if ($hours <= 0) { Flash::error('Hours must be > 0.'); $this->redirect('/volunteers/' . $p['id']); }
        $this->model->logHours([
            'org_id'        => Auth::orgId(),
            'volunteer_id'  => (int)$p['id'],
            'shift_id'      => (int)($_POST['shift_id'] ?? 0),
            'hours'         => $hours,
            'activity_date' => $_POST['activity_date'] ?? date('Y-m-d'),
            'description'   => $_POST['description'] ?? '',
        ]);
        Flash::success('Hours logged, pending approval.');
        $this->redirect('/volunteers/' . $p['id']);
    }

    public function approveHours(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $this->model->approveHours((int)$p['hours_id'], Auth::orgId(), Auth::user()['id']);
        AuditLog::record('hours.approve', 'volunteer_hours', (int)$p['hours_id']);
        Flash::success('Hours approved.');
        $this->redirect('/volunteers');
    }

    public function rejectHours(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $this->model->rejectHours((int)$p['hours_id'], Auth::orgId());
        Flash::success('Hours rejected.');
        $this->redirect('/volunteers');
    }

    // Shifts
    public function shifts(array $p): void
    {
        Auth::require();
        $shifts = $this->model->allShifts(Auth::orgId());
        $this->layout('modules/volunteers/views/shifts.php', ['pageTitle' => 'Volunteer Shifts', 'shifts' => $shifts]);
    }

    public function shiftCreate(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->layout('modules/volunteers/views/shift_form.php', ['pageTitle' => 'New Shift', 'shift' => [], 'errors' => []]);
    }

    public function shiftStore(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $data   = ['org_id' => Auth::orgId(), 'title' => trim($_POST['title'] ?? ''), 'description' => trim($_POST['description'] ?? ''), 'shift_date' => $_POST['shift_date'] ?? date('Y-m-d'), 'start_time' => $_POST['start_time'] ?: null, 'end_time' => $_POST['end_time'] ?: null, 'max_volunteers' => $_POST['max_volunteers'] ?: null];
        $errors = [];
        if (!$data['title']) $errors['title'] = 'Title required.';
        if ($errors) { $this->layout('modules/volunteers/views/shift_form.php', ['pageTitle' => 'New Shift', 'shift' => $_POST, 'errors' => $errors]); return; }
        $id = $this->model->insertShift($data);
        Flash::success('Shift created.');
        $this->redirect('/volunteers/shifts');
    }

    public function shiftComplete(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $this->model->completeShift((int)$p['id'], Auth::orgId());
        Flash::success('Shift completed — hours logged for all volunteers pending approval.');
        $this->redirect('/volunteers/shifts');
    }

    private function extract(): array
    {
        return [
            'contact_id'   => (int)($_POST['contact_id'] ?? 0),
            'skills'       => trim($_POST['skills']       ?? ''),
            'availability' => trim($_POST['availability'] ?? ''),
            'is_active'    => (int)($_POST['is_active']   ?? 1),
            'notes'        => trim($_POST['notes']        ?? ''),
        ];
    }
}
