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

    public function getBoardPositions(int $contactId, int $orgId): array
    {
        return $this->query(
            'SELECT * FROM board_positions WHERE contact_id = ? AND org_id = ? ORDER BY start_date DESC',
            [$contactId, $orgId]
        );
    }

    public function addBoardPosition(int $contactId, int $orgId, array $data): int
    {
        $this->execute(
            'INSERT INTO board_positions (org_id, contact_id, title, committee, start_date, end_date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$orgId, $contactId, $data['title'], $data['committee'] ?: null,
             $data['start_date'], $data['end_date'] ?: null, $data['notes'] ?: null]
        );
        return (int)Database::getInstance()->lastInsertId();
    }

    public function endBoardPosition(int $posId, int $orgId, string $endDate): void
    {
        $this->execute(
            'UPDATE board_positions SET end_date = ?, updated_at = NOW() WHERE id = ? AND org_id = ?',
            [$endDate, $posId, $orgId]
        );
    }

    public function getDonorProfile(int $contactId, int $orgId): array
    {
        $summary = $this->queryOne(
            'SELECT COUNT(*) AS gift_count, COALESCE(SUM(amount),0) AS total_given,
                    MAX(donated_on) AS last_gift_date, MIN(donated_on) AS first_gift_date
             FROM donations WHERE contact_id = ? AND org_id = ?',
            [$contactId, $orgId]
        ) ?: [];
        $recent = $this->query(
            'SELECT d.*, ca.name AS campaign_name FROM donations d
             LEFT JOIN campaigns ca ON ca.id = d.campaign_id
             WHERE d.contact_id = ? AND d.org_id = ?
             ORDER BY d.donated_on DESC LIMIT 5',
            [$contactId, $orgId]
        );
        return ['summary' => $summary, 'recent' => $recent];
    }

    public function getVolunteerProfile(int $contactId, int $orgId): array
    {
        $vol = $this->queryOne(
            'SELECT v.*, COALESCE(h.total_hours, 0) AS total_hours, h.recent_date
             FROM volunteers v
             LEFT JOIN (
                 SELECT volunteer_id, SUM(hours) AS total_hours, MAX(activity_date) AS recent_date
                 FROM volunteer_hours WHERE org_id = ? AND status = "approved"
                 GROUP BY volunteer_id
             ) h ON h.volunteer_id = v.id
             WHERE v.contact_id = ? AND v.org_id = ?',
            [$orgId, $contactId, $orgId]
        );
        $recent = $vol ? $this->query(
            'SELECT * FROM volunteer_hours WHERE volunteer_id = ? AND org_id = ? AND status = "approved"
             ORDER BY activity_date DESC LIMIT 5',
            [$vol['id'], $orgId]
        ) : [];
        return ['volunteer' => $vol, 'recent' => $recent];
    }

    public function getMembershipProfile(int $contactId, int $orgId): ?array
    {
        return $this->queryOne(
            'SELECT m.*, t.name AS tier_name, t.billing_cycle, t.amount AS dues_rate,
                    COALESCE(paid.total_paid, 0) AS total_paid
             FROM memberships m
             JOIN membership_tiers t ON t.id = m.tier_id
             LEFT JOIN (
                 SELECT membership_id, SUM(amount) AS total_paid
                 FROM dues_payments WHERE org_id = ?
                 GROUP BY membership_id
             ) paid ON paid.membership_id = m.id
             WHERE m.contact_id = ? AND m.org_id = ?
             ORDER BY m.created_at DESC LIMIT 1',
            [$orgId, $contactId, $orgId]
        );
    }

    public function allActive(int $orgId): array
    {
        return $this->query(
            'SELECT * FROM contacts WHERE org_id = ? AND merged_into_id IS NULL ORDER BY last_name, first_name',
            [$orgId]
        );
    }
}
