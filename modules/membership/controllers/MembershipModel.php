<?php
declare(strict_types=1);

class MembershipModel extends Model
{
    protected static string $table = 'memberships';

    public function allWithDetails(int $orgId): array
    {
        return $this->query(
            'SELECT m.*, c.first_name, c.last_name, c.email,
                    t.name AS tier_name, t.billing_cycle, t.amount AS dues_rate,
                    dp.paid_on AS last_paid_on, dp.amount AS last_paid_amount,
                    COALESCE(totals.total_paid, 0) AS total_paid
             FROM memberships m
             JOIN contacts c ON c.id = m.contact_id
             JOIN membership_tiers t ON t.id = m.tier_id
             LEFT JOIN dues_payments dp ON dp.id = (
                 SELECT id FROM dues_payments WHERE membership_id = m.id AND org_id = m.org_id
                 ORDER BY paid_on DESC, id DESC LIMIT 1
             )
             LEFT JOIN (
                 SELECT membership_id, SUM(amount) AS total_paid
                 FROM dues_payments WHERE org_id = ?
                 GROUP BY membership_id
             ) totals ON totals.membership_id = m.id
             WHERE m.org_id = ?
             ORDER BY c.last_name, c.first_name',
            [$orgId, $orgId]
        );
    }

    public function allDuesPayments(int $orgId, ?string $from, ?string $to): array
    {
        $sql  = 'SELECT dp.*, c.first_name, c.last_name, t.name AS tier_name, t.billing_cycle
                 FROM dues_payments dp
                 JOIN memberships m ON m.id = dp.membership_id
                 JOIN contacts c ON c.id = dp.contact_id
                 JOIN membership_tiers t ON t.id = m.tier_id
                 WHERE dp.org_id = ?';
        $vals = [$orgId];
        if ($from) { $sql .= ' AND dp.paid_on >= ?'; $vals[] = $from; }
        if ($to)   { $sql .= ' AND dp.paid_on <= ?'; $vals[] = $to; }
        $sql .= ' ORDER BY dp.paid_on DESC, dp.id DESC';
        return $this->query($sql, $vals);
    }

    public function findDetail(int $id, int $orgId): ?array
    {
        return $this->queryOne(
            'SELECT m.*, c.first_name, c.last_name, c.email, t.name AS tier_name, t.billing_cycle, t.amount, t.grace_period_days
             FROM memberships m
             JOIN contacts c ON c.id = m.contact_id
             JOIN membership_tiers t ON t.id = m.tier_id
             WHERE m.id = ? AND m.org_id = ?',
            [$id, $orgId]
        );
    }

    public function getDues(int $membershipId, int $orgId): array
    {
        return $this->query(
            'SELECT * FROM dues_payments WHERE membership_id = ? AND org_id = ? ORDER BY paid_on DESC, id DESC',
            [$membershipId, $orgId]
        );
    }

    public function findDues(int $dueId, int $orgId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM dues_payments WHERE id = ? AND org_id = ?',
            [$dueId, $orgId]
        );
    }

    public function updateDues(int $id, int $orgId, array $data): void
    {
        $this->execute(
            'UPDATE dues_payments SET due_date=?, paid_on=?, amount=?, method=?, reference=?, note=?, updated_at=NOW()
             WHERE id=? AND org_id=?',
            [$data['due_date'], $data['paid_on'], $data['amount'], $data['method'], $data['reference'], $data['note'], $id, $orgId]
        );
    }

    public function addDues(array $data): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO dues_payments (org_id, membership_id, contact_id, due_date, amount, paid_on, method, reference, note)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['org_id'], $data['membership_id'], $data['contact_id'],
            $data['due_date'] ?? null,
            $data['amount'], $data['paid_on'],
            $data['method'] ?? null, $data['reference'] ?? null, $data['note'] ?? null,
        ]);
        $id = (int)$db->lastInsertId();

        // Auto-insert transaction
        $this->insertTransaction($data['org_id'], $data['contact_id'], 'dues_payment', $id, (float)$data['amount'], $data['paid_on'], 'dues');

        // Renew membership end_date
        $this->renewAfterPayment((int)$data['membership_id'], (int)$data['org_id']);

        return $id;
    }

    private function insertTransaction(int $orgId, int $contactId, string $type, int $sourceId, float $amount, string $date, string $category): void
    {
        $fy = $this->fiscalYear($date, $orgId);
        $this->execute(
            'INSERT INTO transactions (org_id, source_type, source_id, contact_id, amount, direction, category, transaction_date, fiscal_year)
             VALUES (?, ?, ?, ?, ?, "credit", ?, ?, ?)',
            [$orgId, $type, $sourceId, $contactId, $amount, $category, $date, $fy]
        );
    }

    private function fiscalYear(string $date, int $orgId): int
    {
        $start = (int)($this->scalar('SELECT fiscal_year_start FROM organizations WHERE id = ?', [$orgId]) ?? 1);
        $d = new DateTime($date);
        $month = (int)$d->format('n');
        $year  = (int)$d->format('Y');
        return $month >= $start ? $year : $year - 1;
    }

    private function renewAfterPayment(int $membershipId, int $orgId): void
    {
        $m = $this->queryOne('SELECT m.*, t.billing_cycle FROM memberships m JOIN membership_tiers t ON t.id = m.tier_id WHERE m.id = ? AND m.org_id = ?', [$membershipId, $orgId]);
        if (!$m || $m['billing_cycle'] === 'lifetime') return;

        $base = $m['end_date'] && $m['end_date'] > date('Y-m-d') ? $m['end_date'] : date('Y-m-d');
        $intervals = ['monthly' => '+1 month', 'quarterly' => '+3 months', 'annual' => '+1 year'];
        $interval  = $intervals[$m['billing_cycle']] ?? '+1 year';
        $newEnd    = (new DateTime($base))->modify($interval)->format('Y-m-d');

        $this->execute(
            'UPDATE memberships SET end_date = ?, status = "active", updated_at = NOW() WHERE id = ? AND org_id = ?',
            [$newEnd, $membershipId, $orgId]
        );
    }

    public function computeEndDate(string $startDate, string $billingCycle): ?string
    {
        if ($billingCycle === 'lifetime') return null;
        $intervals = ['monthly' => '+1 month', 'quarterly' => '+3 months', 'annual' => '+1 year'];
        $interval  = $intervals[$billingCycle] ?? '+1 year';
        return (new DateTime($startDate))->modify($interval)->format('Y-m-d');
    }

    public function expiringReport(int $orgId, int $days = 30): array
    {
        return $this->query(
            'SELECT m.*, c.first_name, c.last_name, c.email, t.name AS tier_name
             FROM memberships m JOIN contacts c ON c.id = m.contact_id JOIN membership_tiers t ON t.id = m.tier_id
             WHERE m.org_id = ? AND m.status IN ("active","grace")
               AND m.end_date IS NOT NULL AND m.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY m.end_date',
            [$orgId, $days]
        );
    }

    public function overdueReport(int $orgId): array
    {
        return $this->query(
            'SELECT m.*, c.first_name, c.last_name, c.email,
                    t.name AS tier_name, t.amount AS dues_rate, t.billing_cycle,
                    dp.paid_on AS last_paid_on,
                    COALESCE(totals.total_paid, 0) AS total_paid
             FROM memberships m
             JOIN contacts c ON c.id = m.contact_id
             JOIN membership_tiers t ON t.id = m.tier_id
             LEFT JOIN dues_payments dp ON dp.id = (
                 SELECT id FROM dues_payments WHERE membership_id = m.id AND org_id = m.org_id
                 ORDER BY paid_on DESC LIMIT 1
             )
             LEFT JOIN (
                 SELECT membership_id, SUM(amount) AS total_paid
                 FROM dues_payments WHERE org_id = ?
                 GROUP BY membership_id
             ) totals ON totals.membership_id = m.id
             WHERE m.org_id = ? AND m.status = "expired"
             ORDER BY m.end_date',
            [$orgId, $orgId]
        );
    }

    public function recordHistory(int $membershipId, int $orgId, int $contactId, string $eventType, ?string $oldValue = null, ?string $newValue = null, ?string $notes = null): void
    {
        $this->execute(
            'INSERT INTO membership_history (org_id, membership_id, contact_id, event_type, old_value, new_value, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$orgId, $membershipId, $contactId, $eventType, $oldValue, $newValue, $notes]
        );
    }

    public function getHistory(int $contactId, int $orgId): array
    {
        return $this->query(
            'SELECT * FROM membership_history
             WHERE contact_id = ? AND org_id = ?
             ORDER BY created_at DESC',
            [$contactId, $orgId]
        );
    }

    public function activeCount(int $orgId): int
    {
        return (int)$this->scalar('SELECT COUNT(*) FROM memberships WHERE org_id = ? AND status IN ("active","lifetime")', [$orgId]);
    }

    public function expiringCount(int $orgId, int $days = 30): int
    {
        return (int)$this->scalar(
            'SELECT COUNT(*) FROM memberships WHERE org_id = ? AND status IN ("active","grace") AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)',
            [$orgId, $days]
        );
    }
}
