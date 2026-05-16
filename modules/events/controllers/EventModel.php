<?php
declare(strict_types=1);

class EventModel extends Model
{
    protected static string $table = 'events';

    public function allWithCount(int $orgId): array
    {
        return $this->query(
            'SELECT e.*, COUNT(r.id) AS reg_count FROM events e
             LEFT JOIN event_registrations r ON r.event_id = e.id AND r.status = "registered"
             WHERE e.org_id = ? GROUP BY e.id ORDER BY e.event_date DESC',
            [$orgId]
        );
    }

    public function findDetail(int $id, int $orgId): ?array
    {
        return $this->queryOne(
            'SELECT e.*, COUNT(r.id) AS reg_count FROM events e
             LEFT JOIN event_registrations r ON r.event_id = e.id AND r.status = "registered"
             WHERE e.id = ? AND e.org_id = ? GROUP BY e.id',
            [$id, $orgId]
        );
    }

    public function registrations(int $eventId, int $orgId): array
    {
        return $this->query(
            'SELECT er.*, c.first_name, c.last_name FROM event_registrations er
             LEFT JOIN contacts c ON c.id = er.contact_id WHERE er.event_id = ? AND er.org_id = ? ORDER BY er.created_at',
            [$eventId, $orgId]
        );
    }

    public function register(array $data, int $orgId): int
    {
        $event = $this->find((int)$data['event_id'], $orgId);
        if (!$event) return 0;

        $regCount = (int)$this->scalar('SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status = "registered"', [$data['event_id']]);
        $status   = ($event['capacity'] && $regCount >= $event['capacity']) ? 'waitlist' : 'registered';

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO event_registrations (org_id, event_id, contact_id, name, email, status, amount_paid)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$orgId, $data['event_id'], $data['contact_id'] ?: null, $data['name'], $data['email'] ?? null, $status, $data['amount_paid'] ?? 0]);
        $id = (int)$db->lastInsertId();

        if ((float)($data['amount_paid'] ?? 0) > 0) {
            $fy = $this->fiscalYear(date('Y-m-d'), $orgId);
            $this->execute(
                'INSERT INTO transactions (org_id, source_type, source_id, contact_id, amount, direction, category, transaction_date, fiscal_year)
                 VALUES (?, "event_registration", ?, ?, ?, "credit", "event", CURDATE(), ?)',
                [$orgId, $id, $data['contact_id'] ?: null, $data['amount_paid'], $fy]
            );
        }

        return $id;
    }

    public function checkIn(int $regId, int $orgId): void
    {
        $this->execute('UPDATE event_registrations SET status="attended", checked_in_at=NOW(), updated_at=NOW() WHERE id=? AND org_id=?', [$regId, $orgId]);
    }

    public function cancel(int $regId, int $orgId, string $refundNote = ''): void
    {
        $this->execute('UPDATE event_registrations SET status="cancelled", refund_note=?, updated_at=NOW() WHERE id=? AND org_id=?', [$refundNote ?: null, $regId, $orgId]);
    }

    public function upcoming(int $orgId, int $limit = 3): array
    {
        return $this->query('SELECT * FROM events WHERE org_id = ? AND event_date >= CURDATE() ORDER BY event_date LIMIT ?', [$orgId, $limit]);
    }

    public function attendanceReport(int $eventId, int $orgId): array
    {
        return $this->query('SELECT er.*, c.first_name, c.last_name FROM event_registrations er LEFT JOIN contacts c ON c.id = er.contact_id WHERE er.event_id = ? AND er.org_id = ? ORDER BY er.name', [$eventId, $orgId]);
    }

    private function fiscalYear(string $date, int $orgId): int
    {
        $start = (int)(Database::getInstance()->prepare('SELECT fiscal_year_start FROM organizations WHERE id = ?')->execute([$orgId]) ?: 1);
        $d = new DateTime($date);
        return ((int)$d->format('n') >= $start) ? (int)$d->format('Y') : (int)$d->format('Y') - 1;
    }
}
