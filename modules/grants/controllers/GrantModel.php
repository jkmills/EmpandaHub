<?php
declare(strict_types=1);

class GrantModel extends Model
{
    protected static string $table = 'grants';

    public function pipeline(int $orgId): array
    {
        return $this->query(
            'SELECT g.*, f.name AS funder_name FROM `grants` g JOIN funders f ON f.id = g.funder_id
             WHERE g.org_id = ? ORDER BY FIELD(g.status,"prospect","drafting","submitted","awarded","declined"), g.deadline_date',
            [$orgId]
        );
    }

    public function findDetail(int $id, int $orgId): ?array
    {
        return $this->queryOne(
            'SELECT g.*, f.name AS funder_name FROM `grants` g JOIN funders f ON f.id = g.funder_id WHERE g.id = ? AND g.org_id = ?',
            [$id, $orgId]
        );
    }

    public function reports(int $grantId, int $orgId): array
    {
        return $this->query('SELECT * FROM grant_reports WHERE grant_id = ? AND org_id = ? ORDER BY due_date', [$grantId, $orgId]);
    }

    public function addReport(array $data): int
    {
        $db = Database::getInstance();
        $db->prepare('INSERT INTO grant_reports (org_id, grant_id, title, due_date, notes) VALUES (?,?,?,?,?)')->execute([$data['org_id'], $data['grant_id'], $data['title'], $data['due_date'], $data['notes'] ?? null]);
        return (int)$db->lastInsertId();
    }

    public function updateReport(int $id, array $data): void
    {
        $this->execute('UPDATE grant_reports SET submitted_date=?, notes=?, updated_at=NOW() WHERE id=?', [$data['submitted_date'] ?: null, $data['notes'] ?? null, $id]);
    }

    public function upcomingDeadlines(int $orgId, int $days = 30): array
    {
        return $this->query(
            'SELECT g.*, f.name AS funder_name, DATEDIFF(g.deadline_date, CURDATE()) AS days_left
             FROM `grants` g JOIN funders f ON f.id = g.funder_id
             WHERE g.org_id = ? AND g.status NOT IN ("awarded","declined")
               AND g.deadline_date IS NOT NULL AND g.deadline_date >= CURDATE() AND g.deadline_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY g.deadline_date',
            [$orgId, $days]
        );
    }

    public function nextDeadlines(int $orgId, int $limit = 3): array
    {
        return $this->query(
            'SELECT g.*, f.name AS funder_name, DATEDIFF(g.deadline_date, CURDATE()) AS days_left
             FROM `grants` g JOIN funders f ON f.id = g.funder_id
             WHERE g.org_id = ? AND g.status NOT IN ("awarded","declined") AND g.deadline_date >= CURDATE()
             ORDER BY g.deadline_date LIMIT ?',
            [$orgId, $limit]
        );
    }

    public function recordAward(int $id, int $orgId, float $amount, string $awardedDate): void
    {
        $this->execute('UPDATE `grants` SET status="awarded", amount_awarded=?, awarded_date=?, updated_at=NOW() WHERE id=? AND org_id=?', [$amount, $awardedDate, $id, $orgId]);
        $fy = (int)(new DateTime($awardedDate))->format('Y');
        $this->execute(
            'INSERT INTO transactions (org_id, source_type, source_id, amount, direction, category, transaction_date, fiscal_year)
             VALUES (?, "grant", ?, ?, "credit", "grant", ?, ?)',
            [$orgId, $id, $amount, $awardedDate, $fy]
        );
    }

    // Funders
    public function allFunders(int $orgId): array
    {
        return $this->query('SELECT * FROM funders WHERE org_id = ? ORDER BY name', [$orgId]);
    }

    public function findFunder(int $id, int $orgId): ?array
    {
        return $this->queryOne('SELECT * FROM funders WHERE id = ? AND org_id = ?', [$id, $orgId]);
    }

    public function insertFunder(array $data): int
    {
        $db = Database::getInstance();
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $db->prepare("INSERT INTO funders ($cols) VALUES ($phs)")->execute(array_values($data));
        return (int)$db->lastInsertId();
    }

    public function updateFunder(int $id, int $orgId, array $data): void
    {
        $sets = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        $this->execute("UPDATE funders SET $sets, updated_at=NOW() WHERE id=? AND org_id=?", [...array_values($data), $id, $orgId]);
    }
}
