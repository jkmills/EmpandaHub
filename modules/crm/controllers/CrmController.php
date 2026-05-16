<?php
declare(strict_types=1);

class CrmController extends Controller
{
    private CrmModel $model;

    public function __construct()
    {
        $this->model = new CrmModel();
    }

    private function layout(string $view, array $data = []): void
    {
        $this->renderLayout($view, $data);
    }

    public function index(array $params): void
    {
        Auth::require();
        $orgId    = Auth::orgId();
        $q        = trim($_GET['q'] ?? '');
        $contacts = $q ? $this->model->search($orgId, $q) : $this->model->allActive($orgId);
        $this->layout('modules/crm/views/index.php', compact('contacts', 'q') + ['pageTitle' => 'Contacts']);
    }

    public function create(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->layout('modules/crm/views/form.php', ['pageTitle' => 'New Contact', 'contact' => [], 'errors' => []]);
    }

    public function store(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();

        [$data, $errors] = $this->validateContact();
        if ($errors) {
            $this->layout('modules/crm/views/form.php', ['pageTitle' => 'New Contact', 'contact' => $_POST, 'errors' => $errors]);
            return;
        }

        $data['org_id'] = Auth::orgId();
        $id = $this->model->insert($data);

        if (!empty($_POST['tags'])) {
            $tags = array_map('trim', explode(',', $_POST['tags']));
            $this->model->syncTags($id, Auth::orgId(), $tags);
        }

        AuditLog::record('contact.create', 'contact', $id);
        Flash::success('Contact created.');
        $this->redirect('/crm/' . $id);
    }

    public function show(array $params): void
    {
        Auth::require();
        $contact = $this->model->findWithTags((int)$params['id'], Auth::orgId());
        if (!$contact) $this->abort(404);
        $this->layout('modules/crm/views/show.php', ['pageTitle' => $contact['first_name'] . ' ' . $contact['last_name'], 'contact' => $contact]);
    }

    public function edit(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $contact = $this->model->find((int)$params['id'], Auth::orgId());
        if (!$contact) $this->abort(404);
        $contact['tags'] = $this->model->getTags((int)$params['id'], Auth::orgId());
        $tagsStr = implode(', ', array_column($contact['tags'], 'tag'));
        $this->layout('modules/crm/views/form.php', ['pageTitle' => 'Edit Contact', 'contact' => $contact, 'tagsStr' => $tagsStr, 'errors' => []]);
    }

    public function update(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();

        $id = (int)$params['id'];
        if (!$this->model->find($id, Auth::orgId())) $this->abort(404);

        [$data, $errors] = $this->validateContact();
        if ($errors) {
            $contact = $_POST;
            $this->layout('modules/crm/views/form.php', ['pageTitle' => 'Edit Contact', 'contact' => $contact, 'errors' => $errors]);
            return;
        }

        $this->model->update($id, Auth::orgId(), $data);

        $tags = array_map('trim', explode(',', $_POST['tags'] ?? ''));
        $this->model->syncTags($id, Auth::orgId(), $tags);

        AuditLog::record('contact.update', 'contact', $id);
        Flash::success('Contact updated.');
        $this->redirect('/crm/' . $id);
    }

    public function delete(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        $id = (int)$params['id'];
        $this->model->delete($id, Auth::orgId());
        AuditLog::record('contact.delete', 'contact', $id);
        Flash::success('Contact deleted.');
        $this->redirect('/crm');
    }

    public function addNote(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();
        $id   = (int)$params['id'];
        $body = trim($_POST['body'] ?? '');
        if (!$body) {
            Flash::error('Note cannot be empty.');
        } else {
            $this->model->addNote($id, Auth::orgId(), Auth::user()['id'], $body);
            AuditLog::record('contact.note', 'contact', $id);
        }
        $this->redirect('/crm/' . $id);
    }

    public function importForm(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->layout('modules/crm/views/import.php', ['pageTitle' => 'Import Contacts']);
    }

    public function import(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();

        if (empty($_FILES['csv']['tmp_name'])) {
            Flash::error('No file uploaded.');
            $this->redirect('/crm/import');
        }

        $map = [
            'first_name' => $_POST['col_first_name'] ?? '',
            'last_name'  => $_POST['col_last_name']  ?? '',
            'email'      => $_POST['col_email']      ?? '',
            'phone'      => $_POST['col_phone']      ?? '',
            'city'       => $_POST['col_city']       ?? '',
            'state'      => $_POST['col_state']      ?? '',
        ];

        $result = $this->model->importCsv(Auth::orgId(), $_FILES['csv']['tmp_name'], $map);
        AuditLog::record('contact.import', 'contact', 0, json_encode($result));
        Flash::success("Imported {$result['created']} contacts, skipped {$result['skipped']}.");
        $this->redirect('/crm');
    }

    public function exportCsv(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $contacts = $this->model->allActive(Auth::orgId());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contacts-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'City', 'State', 'Zip', 'Country', 'Created']);
        foreach ($contacts as $c) {
            fputcsv($out, [$c['id'], $c['first_name'], $c['last_name'], $c['email'], $c['phone'], $c['city'], $c['state'], $c['zip'], $c['country'], $c['created_at']]);
        }
        fclose($out);
        exit;
    }

    public function duplicates(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $dupes = $this->model->findDuplicates(Auth::orgId());
        $this->layout('modules/crm/views/duplicates.php', ['pageTitle' => 'Duplicate Contacts', 'dupes' => $dupes]);
    }

    public function mergePost(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();
        $keepId  = (int)($_POST['keep_id']  ?? 0);
        $mergeId = (int)($_POST['merge_id'] ?? 0);
        if (!$keepId || !$mergeId || $keepId === $mergeId) {
            Flash::error('Invalid merge selection.');
            $this->redirect('/crm/duplicates');
        }
        $this->model->merge($keepId, $mergeId, Auth::orgId());
        AuditLog::record('contact.merge', 'contact', $keepId, "merged $mergeId into $keepId");
        Flash::success('Contacts merged.');
        $this->redirect('/crm/' . $keepId);
    }

    private function validateContact(): array
    {
        $data   = [];
        $errors = [];

        $data['first_name'] = trim($_POST['first_name'] ?? '');
        $data['last_name']  = trim($_POST['last_name']  ?? '');
        $data['email']      = trim($_POST['email']      ?? '');
        $data['phone']      = trim($_POST['phone']      ?? '');
        $data['address']    = trim($_POST['address']    ?? '');
        $data['city']       = trim($_POST['city']       ?? '');
        $data['state']      = trim($_POST['state']      ?? '');
        $data['zip']        = trim($_POST['zip']        ?? '');
        $data['country']    = trim($_POST['country']    ?? 'US');

        if (!$data['first_name']) $errors['first_name'] = 'First name is required.';
        if (!$data['last_name'])  $errors['last_name']  = 'Last name is required.';
        if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address.';
        }

        return [$data, $errors];
    }
}
