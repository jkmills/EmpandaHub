<?php
declare(strict_types=1);

class DonorModel extends Model
{
    protected static string $table = 'donations';

    private function fiscalYear(string $date, int $orgId): int
    {
        $start = (int)(Database::getInstance()->query('SELECT fiscal_year_start FROM organizations WHERE id = ' . $orgId)->fetchColumn() ?: 1);
        $d = new DateTime($date);
        $month = (int)$d->format('n');
        $year  = (int)$d->format('Y');
        return $month >= $start ? $year : $year - 1;
    }

    public function allWithContact(int $orgId): array
    {
        return $this->query(
            'SELECT d.*, c.first_name, c.last_name, ca.name AS campaign_name
             FROM donations d
             LEFT JOIN contacts c ON c.id = d.contact_id
             LEFT JOIN campaigns ca ON ca.id = d.campaign_id
             WHERE d.org_id = ? ORDER BY d.donated_on DESC',
            [$orgId]
        );
    }

    public function findDetail(int $id, int $orgId): ?array
    {
        return $this->queryOne(
            'SELECT d.*, c.first_name, c.last_name, c.email, ca.name AS campaign_name
             FROM donations d LEFT JOIN contacts c ON c.id = d.contact_id LEFT JOIN campaigns ca ON ca.id = d.campaign_id
             WHERE d.id = ? AND d.org_id = ?',
            [$id, $orgId]
        );
    }

    public function recordDonation(array $data, int $orgId): int
    {
        $data['fiscal_year'] = $this->fiscalYear($data['donated_on'], $orgId);
        $id = $this->insert($data);

        // Auto-insert transaction
        $fy = $data['fiscal_year'];
        $this->execute(
            'INSERT INTO transactions (org_id, source_type, source_id, contact_id, amount, direction, category, description, transaction_date, fiscal_year)
             VALUES (?, "donation", ?, ?, ?, "credit", "donation", ?, ?, ?)',
            [$orgId, $id, $data['contact_id'] ?? null, $data['amount'], $data['campaign_id'] ? 'Campaign: ' . ($data['campaign_id']) : null, $data['donated_on'], $fy]
        );

        return $id;
    }

    public function mtd(int $orgId): float
    {
        return (float)$this->scalar(
            'SELECT COALESCE(SUM(amount),0) FROM donations WHERE org_id = ? AND MONTH(donated_on)=MONTH(CURDATE()) AND YEAR(donated_on)=YEAR(CURDATE())',
            [$orgId]
        );
    }

    public function ytd(int $orgId): float
    {
        return (float)$this->scalar(
            'SELECT COALESCE(SUM(amount),0) FROM donations WHERE org_id = ? AND YEAR(donated_on)=YEAR(CURDATE())',
            [$orgId]
        );
    }

    public function lybunt(int $orgId): array
    {
        return $this->query(
            'SELECT c.id, c.first_name, c.last_name, c.email,
                    SUM(CASE WHEN YEAR(d.donated_on)=YEAR(CURDATE())-1 THEN d.amount ELSE 0 END) AS last_year_total
             FROM donations d JOIN contacts c ON c.id = d.contact_id
             WHERE d.org_id = ? AND YEAR(d.donated_on) = YEAR(CURDATE())-1
               AND c.id NOT IN (SELECT DISTINCT contact_id FROM donations WHERE org_id = ? AND YEAR(donated_on)=YEAR(CURDATE()))
             GROUP BY c.id ORDER BY last_year_total DESC',
            [$orgId, $orgId]
        );
    }

    public function sybunt(int $orgId): array
    {
        return $this->query(
            'SELECT c.id, c.first_name, c.last_name, c.email, MAX(d.donated_on) AS last_gift
             FROM donations d JOIN contacts c ON c.id = d.contact_id
             WHERE d.org_id = ? AND YEAR(d.donated_on) < YEAR(CURDATE())-1
               AND c.id NOT IN (SELECT DISTINCT contact_id FROM donations WHERE org_id = ? AND YEAR(donated_on)>=YEAR(CURDATE())-1)
             GROUP BY c.id ORDER BY last_gift DESC',
            [$orgId, $orgId]
        );
    }

    // Campaigns
    public function allCampaigns(int $orgId): array
    {
        return $this->query(
            'SELECT ca.*, COALESCE(SUM(d.amount),0) AS raised
             FROM campaigns ca LEFT JOIN donations d ON d.campaign_id = ca.id AND d.org_id = ca.org_id
             WHERE ca.org_id = ? GROUP BY ca.id ORDER BY ca.created_at DESC',
            [$orgId]
        );
    }

    public function findCampaign(int $id, int $orgId): ?array
    {
        return $this->queryOne(
            'SELECT ca.*, COALESCE(SUM(d.amount),0) AS raised FROM campaigns ca
             LEFT JOIN donations d ON d.campaign_id = ca.id WHERE ca.id = ? AND ca.org_id = ? GROUP BY ca.id',
            [$id, $orgId]
        );
    }

    public function insertCampaign(array $data): int
    {
        $db   = Database::getInstance();
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $db->prepare("INSERT INTO campaigns ($cols) VALUES ($phs)")->execute(array_values($data));
        return (int)$db->lastInsertId();
    }

    public function updateCampaign(int $id, int $orgId, array $data): void
    {
        $sets = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        $this->execute("UPDATE campaigns SET $sets, updated_at=NOW() WHERE id=? AND org_id=?", [...array_values($data), $id, $orgId]);
    }

    public function donationsByContactRange(int $orgId, int $contactId, string $from, string $to): array
    {
        return $this->query(
            'SELECT d.*, ca.name AS campaign_name
             FROM donations d
             LEFT JOIN campaigns ca ON ca.id = d.campaign_id
             WHERE d.org_id = ? AND d.contact_id = ? AND d.donated_on BETWEEN ? AND ?
             ORDER BY d.donated_on ASC',
            [$orgId, $contactId, $from, $to]
        );
    }

    public function markReceiptSent(int $id, int $orgId): void
    {
        $this->execute(
            'UPDATE donations SET receipt_sent = 1 WHERE id = ? AND org_id = ?',
            [$id, $orgId]
        );
    }

    public function allContactsWithDonations(int $orgId): array
    {
        return $this->query(
            'SELECT DISTINCT c.id, c.first_name, c.last_name, c.email
             FROM contacts c
             JOIN donations d ON d.contact_id = c.id
             WHERE d.org_id = ? AND d.is_anonymous = 0
             ORDER BY c.last_name, c.first_name',
            [$orgId]
        );
    }

    public function batchFiscalYear(int $orgId, int $fy): array
    {
        return $this->query(
            'SELECT d.*, c.first_name, c.last_name, c.email FROM donations d
             LEFT JOIN contacts c ON c.id = d.contact_id
             WHERE d.org_id = ? AND d.fiscal_year = ? AND d.is_anonymous = 0
             ORDER BY c.last_name, c.first_name',
            [$orgId, $fy]
        );
    }
}
