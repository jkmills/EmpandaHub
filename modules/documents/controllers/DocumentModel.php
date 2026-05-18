<?php
declare(strict_types=1);

class DocumentModel extends Model
{
    protected static string $table = 'documents';

    // Visibility levels each role may access
    public static function allowedVisibilities(): array
    {
        if (Auth::hasRole('super_admin'))    return ['all_staff','staff_only','admin_only','super_admin_only'];
        if (Auth::hasRole('admin'))          return ['all_staff','staff_only','admin_only'];
        if (Auth::hasRole('staff'))          return ['all_staff','staff_only'];
        return ['all_staff'];
    }

    public function listForOrg(int $orgId, array $filters = []): array
    {
        $visible = self::allowedVisibilities();
        $in      = implode(',', array_fill(0, count($visible), '?'));

        $where  = ["d.org_id = ?", "d.visibility IN ($in)"];
        $params = [$orgId, ...$visible];

        if (!empty($filters['q'])) {
            $where[]  = '(d.title LIKE ? OR d.description LIKE ? OR d.tags LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if (!empty($filters['category_id'])) {
            $where[]  = 'd.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['mime'])) {
            $where[]  = 'd.mime_type LIKE ?';
            $params[] = $filters['mime'] . '%';
        }

        $sql = 'SELECT d.*, u.name AS uploader_name, c.name AS category_name
                FROM documents d
                LEFT JOIN users u ON u.id = d.uploaded_by
                LEFT JOIN doc_categories c ON c.id = d.category_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY d.created_at DESC';

        return $this->query($sql, $params);
    }

    public function findDoc(int $id, int $orgId): ?array
    {
        $visible = self::allowedVisibilities();
        $in      = implode(',', array_fill(0, count($visible), '?'));
        $row = $this->queryOne(
            "SELECT d.*, u.name AS uploader_name, c.name AS category_name
             FROM documents d
             LEFT JOIN users u ON u.id = d.uploaded_by
             LEFT JOIN doc_categories c ON c.id = d.category_id
             WHERE d.id = ? AND d.org_id = ? AND d.visibility IN ($in)",
            [$id, $orgId, ...$visible]
        );
        return $row ?: null;
    }

    public function getVersions(int $docId): array
    {
        return $this->query(
            'SELECT dv.*, u.name AS uploader_name
             FROM document_versions dv
             LEFT JOIN users u ON u.id = dv.uploaded_by
             WHERE dv.document_id = ?
             ORDER BY dv.version_number DESC',
            [$docId]
        );
    }

    public function getShareLinks(int $docId): array
    {
        return $this->query(
            'SELECT dsl.*, u.name AS creator_name,
                    (SELECT COUNT(*) FROM document_link_access_log WHERE share_link_id = dsl.id) AS access_count
             FROM document_share_links dsl
             LEFT JOIN users u ON u.id = dsl.created_by
             WHERE dsl.document_id = ?
             ORDER BY dsl.created_at DESC',
            [$docId]
        );
    }

    public function getAccessLog(int $shareLinkId, int $limit = 20): array
    {
        return $this->query(
            'SELECT * FROM document_link_access_log WHERE share_link_id = ? ORDER BY accessed_at DESC LIMIT ' . $limit,
            [$shareLinkId]
        );
    }

    public function insertDoc(array $data): int
    {
        return $this->insert($data);
    }

    public function updateDoc(int $id, array $data): void
    {
        $this->update($id, $data);
    }

    public function addVersion(int $docId, array $data): void
    {
        $db = Database::getInstance();
        $db->prepare(
            'INSERT INTO document_versions (document_id, version_number, filename, file_path, file_size, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$docId, $data['version_number'], $data['filename'], $data['file_path'], $data['file_size'], $data['uploaded_by']]);
    }

    public function createShareLink(int $docId, int $orgId, int $userId, ?string $expiresAt): string
    {
        $token = bin2hex(random_bytes(32)); // 64-char hex
        $db    = Database::getInstance();
        $db->prepare(
            'INSERT INTO document_share_links (org_id, document_id, token, expires_at, created_by) VALUES (?,?,?,?,?)'
        )->execute([$orgId, $docId, $token, $expiresAt, $userId]);
        return $token;
    }

    public function revokeShareLink(int $linkId, int $orgId): void
    {
        Database::getInstance()
            ->prepare('UPDATE document_share_links SET is_active=0 WHERE id=? AND org_id=?')
            ->execute([$linkId, $orgId]);
    }

    public function findByToken(string $token): ?array
    {
        return $this->queryOne(
            'SELECT dsl.*, d.title, d.filename, d.file_path, d.mime_type, d.file_size, d.org_id, d.visibility
             FROM document_share_links dsl
             JOIN documents d ON d.id = dsl.document_id
             WHERE dsl.token = ?',
            [$token]
        ) ?: null;
    }

    public function logAccess(int $shareLinkId, string $ip): void
    {
        Database::getInstance()
            ->prepare('INSERT INTO document_link_access_log (share_link_id, ip_address) VALUES (?,?)')
            ->execute([$shareLinkId, $ip]);
    }

    public function getCategoriesForOrg(int $orgId): array
    {
        return $this->query(
            'SELECT * FROM doc_categories WHERE org_id=? ORDER BY sort_order, name',
            [$orgId]
        );
    }

    public function insertCategory(array $data): int
    {
        $db = Database::getInstance();
        $db->prepare('INSERT INTO doc_categories (org_id, parent_id, name, min_visibility, sort_order) VALUES (?,?,?,?,?)')
           ->execute([$data['org_id'], $data['parent_id'] ?? null, $data['name'], $data['min_visibility'] ?? 'all_staff', $data['sort_order'] ?? 0]);
        return (int)$db->lastInsertId();
    }

    public function deleteDoc(int $id, int $orgId): void
    {
        Database::getInstance()
            ->prepare('DELETE FROM documents WHERE id=? AND org_id=?')
            ->execute([$id, $orgId]);
    }

}
