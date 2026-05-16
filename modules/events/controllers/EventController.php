<?php
declare(strict_types=1);

class EventController extends Controller
{
    private EventModel $model;

    public function __construct() { $this->model = new EventModel(); }

    private function layout(string $view, array $data = []): void
    {
        $this->renderLayout($view, $data);
    }

    public function index(array $p): void
    {
        Auth::require();
        $events = $this->model->allWithCount(Auth::orgId());
        $this->layout('modules/events/views/index.php', ['pageTitle' => 'Events', 'events' => $events]);
    }

    public function create(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->layout('modules/events/views/form.php', ['pageTitle' => 'New Event', 'event' => [], 'errors' => []]);
    }

    public function store(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        [$data, $errors] = $this->validateEvent();
        if ($errors) { $this->layout('modules/events/views/form.php', ['pageTitle' => 'New Event', 'event' => $_POST, 'errors' => $errors]); return; }
        $data['org_id'] = Auth::orgId();
        $id = $this->model->insert($data);
        AuditLog::record('event.create', 'event', $id);
        Flash::success('Event created.');
        $this->redirect('/events/' . $id);
    }

    public function show(array $p): void
    {
        Auth::require();
        $event = $this->model->findDetail((int)$p['id'], Auth::orgId());
        if (!$event) $this->abort(404);
        $regs     = $this->model->registrations((int)$p['id'], Auth::orgId());
        $contacts = (new CrmModel())->allActive(Auth::orgId());
        $this->layout('modules/events/views/show.php', ['pageTitle' => $event['title'], 'event' => $event, 'regs' => $regs, 'contacts' => $contacts]);
    }

    public function edit(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $event = $this->model->find((int)$p['id'], Auth::orgId());
        if (!$event) $this->abort(404);
        $this->layout('modules/events/views/form.php', ['pageTitle' => 'Edit Event', 'event' => $event, 'errors' => []]);
    }

    public function update(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        [$data, $errors] = $this->validateEvent();
        if ($errors) { $this->layout('modules/events/views/form.php', ['pageTitle' => 'Edit Event', 'event' => $_POST, 'errors' => $errors]); return; }
        $this->model->update((int)$p['id'], Auth::orgId(), $data);
        Flash::success('Event updated.');
        $this->redirect('/events/' . $p['id']);
    }

    public function delete(array $p): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        $this->model->delete((int)$p['id'], Auth::orgId());
        Flash::success('Event deleted.');
        $this->redirect('/events');
    }

    public function register(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $name = trim($_POST['name'] ?? '');
        if (!$name) { Flash::error('Name required.'); $this->redirect('/events/'.$p['id']); }
        $id = $this->model->register([
            'event_id'    => (int)$p['id'],
            'contact_id'  => $_POST['contact_id'] ?: null,
            'name'        => $name,
            'email'       => trim($_POST['email'] ?? ''),
            'amount_paid' => (float)($_POST['amount_paid'] ?? 0),
        ], Auth::orgId());
        AuditLog::record('event.register', 'event_registration', $id);
        Flash::success('Registration added.');
        $this->redirect('/events/'.$p['id']);
    }

    public function checkin(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $this->model->checkIn((int)$p['reg_id'], Auth::orgId());
        Flash::success('Checked in.');
        $this->redirect('/events/'.$p['id']);
    }

    public function cancel(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $this->model->cancel((int)$p['reg_id'], Auth::orgId(), trim($_POST['refund_note'] ?? ''));
        Flash::success('Registration cancelled.');
        $this->redirect('/events/'.$p['id']);
    }

    public function checkinView(array $p): void
    {
        Auth::require();
        $event = $this->model->findDetail((int)$p['id'], Auth::orgId());
        if (!$event) $this->abort(404);
        $regs = $this->model->attendanceReport((int)$p['id'], Auth::orgId());
        $this->layout('modules/events/views/checkin.php', ['pageTitle' => 'Check-In: '.$event['title'], 'event' => $event, 'regs' => $regs]);
    }

    private function validateEvent(): array
    {
        $data = [
            'title'        => trim($_POST['title']       ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'event_date'   => $_POST['event_date']       ?? '',
            'start_time'   => $_POST['start_time']       ?: null,
            'end_time'     => $_POST['end_time']         ?: null,
            'location'     => trim($_POST['location']    ?? ''),
            'capacity'     => $_POST['capacity']         ?: null,
            'price'        => (float)($_POST['price']    ?? 0),
            'is_published' => (int)($_POST['is_published'] ?? 0),
        ];
        $errors = [];
        if (!$data['title'])      $errors['title']      = 'Title required.';
        if (!$data['event_date']) $errors['event_date'] = 'Date required.';
        return [$data, $errors];
    }
}
