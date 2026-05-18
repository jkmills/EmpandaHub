<?php
declare(strict_types=1);

class DocumentController extends Controller
{
    private DocumentModel $model;

    private const BLOCKED_EXTS = ['php','php3','php4','php5','phtml','phar','exe','sh','bat','cmd','ps1','py','rb','pl'];

    public function __construct()
    {
        $this->model = new DocumentModel();
    }

    public function index(array $params): void
    {
        Auth::require();
        $orgId      = Auth::orgId();
        $filters    = array_intersect_key($_GET, array_flip(['q','category_id','mime']));
        $docs       = $this->model->listForOrg($orgId, $filters);
        $categories = $this->model->getCategoriesForOrg($orgId);
        $this->renderLayout('modules/documents/views/index.php', [
            'pageTitle'  => 'Document Library',
            'docs'       => $docs,
            'categories' => $categories,
            'filters'    => $filters,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $categories = $this->model->getCategoriesForOrg(Auth::orgId());
        $this->renderLayout('modules/documents/views/form.php', [
            'pageTitle'  => 'Upload Document',
            'doc'        => [],
            'categories' => $categories,
            'errors'     => [],
        ]);
    }

    public function store(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();

        $orgId  = Auth::orgId();
        $userId = Auth::user()['id'];

        [$fileData, $fileErr] = $this->handleUpload($orgId);
        $errors = $fileErr;

        $title      = trim($_POST['title'] ?? '');
        $visibility = $_POST['visibility'] ?? 'all_staff';
        if (!$title) $errors['title'] = 'Title is required.';

        $allowed = DocumentModel::allowedVisibilities();
        if (!in_array($visibility, $allowed, true)) {
            $errors['visibility'] = 'Invalid visibility level.';
        }

        if ($errors) {
            $categories = $this->model->getCategoriesForOrg($orgId);
            $this->renderLayout('modules/documents/views/form.php', [
                'pageTitle'  => 'Upload Document',
                'doc'        => $_POST,
                'categories' => $categories,
                'errors'     => $errors,
            ]);
            return;
        }

        $docId = $this->model->insertDoc([
            'org_id'      => $orgId,
            'category_id' => ($_POST['category_id'] ?? '') ?: null,
            'title'       => $title,
            'description' => trim($_POST['description'] ?? ''),
            'tags'        => trim($_POST['tags'] ?? ''),
            'filename'    => $fileData['filename'],
            'file_path'   => $fileData['file_path'],
            'file_size'   => $fileData['file_size'],
            'mime_type'   => $fileData['mime_type'],
            'visibility'  => $visibility,
            'uploaded_by' => $userId,
        ]);

        $this->model->addVersion($docId, [
            'version_number' => 1,
            'filename'       => $fileData['filename'],
            'file_path'      => $fileData['file_path'],
            'file_size'      => $fileData['file_size'],
            'uploaded_by'    => $userId,
        ]);

        AuditLog::record('document.upload', 'document', $docId, $title);
        Flash::success('Document uploaded.');
        $this->redirect('/documents/' . $docId);
    }

    public function show(array $params): void
    {
        Auth::require();
        $orgId = Auth::orgId();
        $doc   = $this->model->findDoc((int)$params['id'], $orgId);
        if (!$doc) $this->abort(404);

        $versions    = $this->model->getVersions((int)$params['id']);
        $shareLinks  = $this->model->getShareLinks((int)$params['id']);
        $canManage   = Auth::hasRole('super_admin', 'admin', 'staff');
        $canDelete   = Auth::hasRole('super_admin', 'admin');
        $visibilities = DocumentModel::allowedVisibilities();

        $this->renderLayout('modules/documents/views/show.php', [
            'pageTitle'    => $doc['title'],
            'doc'          => $doc,
            'versions'     => $versions,
            'shareLinks'   => $shareLinks,
            'canManage'    => $canManage,
            'canDelete'    => $canDelete,
            'visibilities' => $visibilities,
        ]);
    }

    public function newVersion(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();

        $orgId = Auth::orgId();
        $doc   = $this->model->findDoc((int)$params['id'], $orgId);
        if (!$doc) $this->abort(404);

        [$fileData, $errors] = $this->handleUpload($orgId);
        if ($errors) {
            Flash::error(implode(' ', $errors));
            $this->redirect('/documents/' . $params['id']);
            return;
        }

        $versions = $this->model->getVersions((int)$params['id']);
        $nextVer  = ($versions ? (int)$versions[0]['version_number'] : 0) + 1;

        $this->model->addVersion((int)$params['id'], [
            'version_number' => $nextVer,
            'filename'       => $fileData['filename'],
            'file_path'      => $fileData['file_path'],
            'file_size'      => $fileData['file_size'],
            'uploaded_by'    => Auth::user()['id'],
        ]);

        $this->model->updateDoc((int)$params['id'], [
            'filename'  => $fileData['filename'],
            'file_path' => $fileData['file_path'],
            'file_size' => $fileData['file_size'],
            'mime_type' => $fileData['mime_type'],
        ]);

        AuditLog::record('document.version', 'document', (int)$params['id'], "v{$nextVer}");
        Flash::success("Version {$nextVer} uploaded.");
        $this->redirect('/documents/' . $params['id']);
    }

    public function share(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();

        $orgId = Auth::orgId();
        $doc   = $this->model->findDoc((int)$params['id'], $orgId);
        if (!$doc) $this->abort(404);

        $expiresAt = null;
        if (!empty($_POST['expires_at'])) {
            $ts = strtotime($_POST['expires_at']);
            if ($ts && $ts > time()) {
                $expiresAt = date('Y-m-d H:i:s', $ts);
            }
        }

        $token = $this->model->createShareLink((int)$params['id'], $orgId, Auth::user()['id'], $expiresAt);
        AuditLog::record('document.share_link.create', 'document', (int)$params['id']);
        Flash::success('Share link created.');
        $this->redirect('/documents/' . $params['id']);
    }

    public function revokeShareLink(array $params): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $this->requirePost();

        $orgId = Auth::orgId();
        $doc   = $this->model->findDoc((int)$params['id'], $orgId);
        if (!$doc) $this->abort(404);

        $this->model->revokeShareLink((int)$params['linkId'], $orgId);
        AuditLog::record('document.share_link.revoke', 'document', (int)$params['id']);
        Flash::success('Share link revoked.');
        $this->redirect('/documents/' . $params['id']);
    }

    private const PREVIEWABLE_MIMES = [
        'application/pdf',
        'image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml',
    ];

    public static function isPreviewable(string $mimeType): bool
    {
        return in_array($mimeType, self::PREVIEWABLE_MIMES, true);
    }

    public function download(array $params): void
    {
        Auth::require();
        $orgId = Auth::orgId();
        $doc   = $this->model->findDoc((int)$params['id'], $orgId);
        if (!$doc) $this->abort(404);

        $this->serveFile($doc['file_path'], $doc['filename'], $doc['mime_type']);
    }

    public function preview(array $params): void
    {
        Auth::require();
        $orgId = Auth::orgId();
        $doc   = $this->model->findDoc((int)$params['id'], $orgId);
        if (!$doc) $this->abort(404);

        if (!self::isPreviewable($doc['mime_type'])) $this->abort(415);

        $this->serveFile($doc['file_path'], $doc['filename'], $doc['mime_type'], inline: true);
    }

    public function publicDownload(array $params): void
    {
        $token = $params['token'] ?? '';
        $link  = $this->model->findByToken($token);

        if (!$link || !$link['is_active']) {
            $this->render('modules/documents/views/public_expired.php', ['reason' => 'invalid']);
            return;
        }

        if ($link['expires_at'] && strtotime($link['expires_at']) < time()) {
            $this->render('modules/documents/views/public_expired.php', ['reason' => 'expired']);
            return;
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ip = trim(explode(',', $ip)[0]);
        $this->model->logAccess((int)$link['id'], $ip);

        $this->serveFile($link['file_path'], $link['filename'], $link['mime_type']);
    }

    public function delete(array $params): void
    {
        Auth::requireRole('super_admin', 'admin');
        $this->requirePost();

        $orgId = Auth::orgId();
        $doc   = $this->model->findDoc((int)$params['id'], $orgId);
        if (!$doc) $this->abort(404);

        // Remove physical file; ignore errors (file may already be gone)
        $absPath = ROOT . '/' . ltrim($doc['file_path'], '/');
        if (is_file($absPath)) @unlink($absPath);

        $this->model->deleteDoc((int)$params['id'], $orgId);
        AuditLog::record('document.delete', 'document', (int)$params['id'], $doc['title']);
        Flash::success('Document deleted.');
        $this->redirect('/documents');
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function handleUpload(int $orgId): array
    {
        $errors = [];

        if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errors['file'] = 'File upload failed or no file selected.';
            return [[], $errors];
        }

        $tmpPath  = $_FILES['file']['tmp_name'];
        $origName = $_FILES['file']['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (in_array($ext, self::BLOCKED_EXTS, true)) {
            $errors['file'] = 'File type not allowed.';
            return [[], $errors];
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        // Block script MIME types regardless of extension
        $blockedMimes = ['text/x-php','application/x-php','application/x-httpd-php','text/x-sh','application/x-sh'];
        if (in_array($mimeType, $blockedMimes, true)) {
            $errors['file'] = 'File type not allowed.';
            return [[], $errors];
        }

        $dir  = ROOT . '/storage/documents/' . $orgId;
        if (!is_dir($dir)) mkdir($dir, 0750, true);

        $uuid     = bin2hex(random_bytes(16));
        $stored   = $uuid . ($ext ? '.' . $ext : '');
        $destPath = $dir . '/' . $stored;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $errors['file'] = 'Could not save uploaded file.';
            return [[], $errors];
        }

        return [[
            'filename'  => $origName,
            'file_path' => 'storage/documents/' . $orgId . '/' . $stored,
            'file_size' => filesize($destPath),
            'mime_type' => $mimeType,
        ], []];
    }

    private function serveFile(string $relPath, string $filename, string $mimeType, bool $inline = false): void
    {
        $absPath = ROOT . '/' . ltrim($relPath, '/');
        if (!is_file($absPath)) $this->abort(404);

        $disposition = $inline ? 'inline' : 'attachment';
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . filesize($absPath));
        header('X-Content-Type-Options: nosniff');
        readfile($absPath);
        exit;
    }
}
