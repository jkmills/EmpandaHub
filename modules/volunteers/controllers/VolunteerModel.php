<?php
declare(strict_types=1);

class VolunteerModel extends Model
{
    protected static string $table = 'volunteers';

    public function roster(int $orgId): array
    {
        return $this->query(
            'SELECT v.*, c.first_name, c.last_name, c.email, c.phone FROM volunteers v
             JOIN contacts c ON c.id = v.contact_id WHERE v.org_id = ? ORDER BY c.last_name, c.first_name',
            [$orgId]
        );
    }

    public function findDetail(int $id, int $orgId): ?array
    {
        return $this->queryOne(
            'SELECT v.*, c.first_name, c.last_name, c.email FROM volunteers v
             JOIN contacts c ON c.id = v.contact_id WHERE v.id = ? AND v.org_id = ?',
            [$id, $orgId]
        );
    }

    public function getHours(int $volunteerId, int $orgId): array
    {
        return $this->query(
            'SELECT vh.*, vs.title AS shift_title FROM volunteer_hours vh
             LEFT JOIN volunteer_shifts vs ON vs.id = vh.shift_id
             WHERE vh.volunteer_id = ? AND vh.org_id = ? ORDER BY vh.activity_date DESC',
            [$volunteerId, $orgId]
        );
    }

    public function pendingApprovals(int $orgId): array
    {
        return $this->query(
            'SELECT vh.*, c.first_name, c.last_name FROM volunteer_hours vh
             JOIN volunteers v ON v.id = vh.volunteer_id JOIN contacts c ON c.id = v.contact_id
             WHERE vh.org_id = ? AND vh.status = "pending" ORDER BY vh.activity_date',
            [$orgId]
        );
    }

    public function pendingCount(int $orgId): int
    {
        return (int)$this->scalar('SELECT COUNT(*) FROM volunteer_hours WHERE org_id = ? AND status = "pending"', [$orgId]);
    }

    public function mtdHours(int $orgId): float
    {
        return (float)$this->scalar(
            'SELECT COALESCE(SUM(hours),0) FROM volunteer_hours WHERE org_id = ? AND status = "approved"
             AND MONTH(activity_date)=MONTH(CURDATE()) AND YEAR(activity_date)=YEAR(CURDATE())',
            [$orgId]
        );
    }

    public function logHours(array $data): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO volunteer_hours (org_id, volunteer_id, shift_id, hours, activity_date, description, status) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$data['org_id'], $data['volunteer_id'], $data['shift_id'] ?: null, $data['hours'], $data['activity_date'], $data['description'] ?? null, 'pending']);
        return (int)$db->lastInsertId();
    }

    public function approveHours(int $hoursId, int $orgId, int $userId): void
    {
        $this->execute('UPDATE volunteer_hours SET status="approved", approved_by=?, updated_at=NOW() WHERE id=? AND org_id=?', [$userId, $hoursId, $orgId]);
    }

    public function rejectHours(int $hoursId, int $orgId): void
    {
        $this->execute('UPDATE volunteer_hours SET status="rejected", updated_at=NOW() WHERE id=? AND org_id=?', [$hoursId, $orgId]);
    }

    public function allShifts(int $orgId): array
    {
        return $this->query('SELECT * FROM volunteer_shifts WHERE org_id = ? ORDER BY shift_date DESC', [$orgId]);
    }

    public function findShift(int $id, int $orgId): ?array
    {
        return $this->queryOne('SELECT * FROM volunteer_shifts WHERE id = ? AND org_id = ?', [$id, $orgId]);
    }

    public function insertShift(array $data): int
    {
        $db = Database::getInstance();
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $db->prepare("INSERT INTO volunteer_shifts ($cols) VALUES ($phs)")->execute(array_values($data));
        return (int)$db->lastInsertId();
    }

    public function completeShift(int $shiftId, int $orgId): void
    {
        $shift = $this->findShift($shiftId, $orgId);
        if (!$shift) return;

        // Calculate hours from start/end time
        $hours = 1.0;
        if ($shift['start_time'] && $shift['end_time']) {
            $start = new DateTime($shift['shift_date'] . ' ' . $shift['start_time']);
            $end   = new DateTime($shift['shift_date'] . ' ' . $shift['end_time']);
            $diff  = $end->getTimestamp() - $start->getTimestamp();
            $hours = round($diff / 3600, 2);
        }

        // Find all volunteers who were assigned (naive: all active volunteers)
        $volunteers = $this->roster($orgId);
        foreach ($volunteers as $v) {
            $this->execute(
                'INSERT INTO volunteer_hours (org_id, volunteer_id, shift_id, hours, activity_date, description, status)
                 VALUES (?, ?, ?, ?, ?, ?, "pending")',
                [$orgId, $v['id'], $shiftId, $hours, $shift['shift_date'], 'Auto-logged from shift: ' . $shift['title']]
            );
        }
    }
}
