<?php
declare(strict_types=1);

class CrmModel extends Model
{
    protected static string $table = 'contacts';

    public function search(int $orgId, string $q, int $limit = 100): array
    {
        $like = '%' . $q . '%';
        return $this->query(
            'SELECT * FROM contacts WHERE org_id = ? AND merged_into_id IS NULL
             AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)
             ORDER BY last_name, first_name LIMIT ?',
            [$orgId, $like, $like, $like, $like, $limit]
        );
    }

    public function findWithTags(int $id, int $orgId): ?array
    {
        $contact = $this->find($id, $orgId);
        if (!$contact) return null;
        $contact['tags']  = $this->getTags($id, $orgId);
        $contact['notes'] = $this->getNotes($id, $orgId);
        return $contact;
    }

    public function getTags(int $contactId, int $orgId): array
    {
        return $this->query(
            'SELECT * FROM contact_tags WHERE contact_id = ? AND org_id = ? ORDER BY tag',
            [$contactId, $orgId]
        );
    }

    public function syncTags(int $contactId, int $orgId, array $tags): void
    {
        $this->execute('DELETE FROM contact_tags WHERE contact_id = ? AND org_id = ?', [$contactId, $orgId]);
        foreach (array_unique($tags) as $tag) {
            $tag = trim($tag);
            if ($tag === '') continue;
            $this->execute(
                'INSERT INTO contact_tags (org_id, contact_id, tag) VALUES (?, ?, ?)',
                [$orgId, $contactId, $tag]
            );
        }
    }

    public function getNotes(int $contactId, int $orgId): array
    {
        return $this->query(
            'SELECT cn.*, u.name AS author FROM contact_notes cn
             LEFT JOIN users u ON u.id = cn.user_id
             WHERE cn.contact_id = ? AND cn.org_id = ?
             ORDER BY cn.created_at DESC',
            [$contactId, $orgId]
        );
    }

    public function addNote(int $contactId, int $orgId, int $userId, string $body): int
    {
        $this->execute(
            'INSERT INTO contact_notes (org_id, contact_id, user_id, body) VALUES (?, ?, ?, ?)',
            [$orgId, $contactId, $userId, $body]
        );
        return (int)Database::getInstance()->lastInsertId();
    }

    public function findDuplicates(int $orgId): array
    {
        return $this->query(
            'SELECT email, COUNT(*) AS cnt, GROUP_CONCAT(id ORDER BY id) AS ids
             FROM contacts
             WHERE org_id = ? AND email IS NOT NULL AND email != "" AND merged_into_id IS NULL
             GROUP BY email HAVING cnt > 1',
            [$orgId]
        );
    }

    public function merge(int $keepId, int $mergeId, int $orgId): void
    {
        $db = Database::getInstance();
        // Re-point child records
        foreach (['memberships', 'donations', 'dues_payments', 'volunteers', 'event_registrations', 'contact_tags', 'contact_notes'] as $tbl) {
            $db->prepare("UPDATE $tbl SET contact_id = ? WHERE contact_id = ? AND org_id = ?")->execute([$keepId, $mergeId, $orgId]);
        }
        $db->prepare('UPDATE contacts SET merged_into_id = ? WHERE id = ? AND org_id = ?')->execute([$keepId, $mergeId, $orgId]);
    }

    public function importCsv(int $orgId, string $csvPath, array $map): array
    {
        $handle  = fopen($csvPath, 'r');
        $headers = fgetcsv($handle);
        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = ['org_id' => $orgId];
            foreach ($map as $col => $idx) {
                if ($idx !== '' && isset($row[(int)$idx])) {
                    $data[$col] = trim($row[(int)$idx]);
                }
            }
            if (empty($data['first_name']) && empty($data['last_name'])) { $skipped++; continue; }
            $data['first_name'] = $data['first_name'] ?? 'Unknown';
            $data['last_name']  = $data['last_name']  ?? '';
            $this->insert($data);
            $created++;
        }
        fclose($handle);
        return compact('created', 'skipped');
    }

    public function allActive(int $orgId): array
    {
        return $this->query(
            'SELECT * FROM contacts WHERE org_id = ? AND merged_into_id IS NULL ORDER BY last_name, first_name',
            [$orgId]
        );
    }
}
