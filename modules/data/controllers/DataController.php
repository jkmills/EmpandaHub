<?php
declare(strict_types=1);

class DataController extends Controller
{
    private DataModel $model;

    public function __construct()
    {
        $this->model = new DataModel();
    }

    private function layout(string $view, array $data = []): void
    {
        $this->renderLayout($view, $data);
    }

    public function index(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $orgId  = Auth::orgId();
        $counts = $this->model->rowCounts($orgId, array_keys(DataModel::MODULE_TABLES));
        $this->layout('modules/data/views/index.php', [
            'pageTitle' => 'Data Management',
            'counts'    => $counts,
        ]);
    }

    public function backup(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();

        $modules = $_POST['modules'] ?? [];
        if (empty($modules)) {
            Flash::error('Select at least one module to back up.');
            $this->redirect('/data');
            return;
        }

        $orgId   = Auth::orgId();
        $orgData = [];
        $this->loadOrgData($orgData);

        $payload = [
            'meta' => [
                'app'         => 'EmpandaHub',
                'version'     => '1.0',
                'org_name'    => $orgData['orgName'],
                'exported_at' => date('c'),
                'modules'     => $modules,
            ],
            'data' => $this->model->backup($orgId, $modules),
        ];

        AuditLog::record('data.backup', 'org', $orgId, implode(',', $modules));

        $filename = 'empanda-backup-' . date('Y-m-d') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function wipe(array $params): void
    {
        Auth::requireRole('super_admin');
        $this->requirePost();

        $orgData = [];
        $this->loadOrgData($orgData);
        $orgName = $orgData['orgName'];

        $confirm = trim($_POST['confirm_name'] ?? '');
        if ($confirm !== $orgName) {
            Flash::error('Organization name did not match. No data was deleted.');
            $this->redirect('/data');
            return;
        }

        $modules = $_POST['modules'] ?? [];
        if (empty($modules)) {
            Flash::error('Select at least one module to wipe.');
            $this->redirect('/data');
            return;
        }

        $counts = $this->model->wipe(Auth::orgId(), $modules);
        AuditLog::record('data.wipe', 'org', Auth::orgId(), json_encode($counts));

        $total = array_sum($counts);
        Flash::success("Wipe complete: $total records deleted across " . count($modules) . ' module(s).');
        $this->redirect('/data');
    }

    public function restore(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();

        if (empty($_FILES['backup']['tmp_name']) || $_FILES['backup']['error'] !== UPLOAD_ERR_OK) {
            Flash::error('No file uploaded or upload error.');
            $this->redirect('/data');
            return;
        }

        $json    = file_get_contents($_FILES['backup']['tmp_name']);
        $payload = json_decode($json, true);

        if (!$payload || !isset($payload['meta'], $payload['data'])) {
            Flash::error('Invalid backup file — expected EmpandaHub JSON format.');
            $this->redirect('/data');
            return;
        }

        $availableModules = array_intersect(
            array_keys(DataModel::MODULE_TABLES),
            array_keys($payload['data'])
        );
        $modules = !empty($_POST['modules'])
            ? array_intersect($_POST['modules'], $availableModules)
            : $availableModules;

        if (empty($modules)) {
            Flash::error('No valid modules found in backup file.');
            $this->redirect('/data');
            return;
        }

        $orgId = Auth::orgId();

        if (!empty($_POST['wipe_first'])) {
            $this->model->wipe($orgId, $modules);
        }

        $result = $this->model->restore($orgId, $payload['data'], $modules);
        AuditLog::record('data.restore', 'org', $orgId, implode(',', $modules));

        $this->layout('modules/data/views/restore_result.php', [
            'pageTitle' => 'Restore Complete',
            'result'    => $result,
            'modules'   => $modules,
            'meta'      => $payload['meta'],
            'wipedFirst' => !empty($_POST['wipe_first']),
        ]);
    }
}
