<?php
declare(strict_types=1);

class FinanceController extends Controller
{
    private function layout(string $view, array $data = []): void
    {
        $org = Database::getInstance()->prepare('SELECT * FROM organizations WHERE id = ?');
        $org->execute([Auth::orgId()]); $row = $org->fetch();
        $data['orgName']    = $row['name'] ?? APP_NAME;
        $data['orgColor']   = $row['primary_color'] ?? '#2563eb';
        $data['orgLogo']    = $row['logo'] ?? '';
        $data['orgModules'] = json_decode($row['config_json'] ?? '{}', true)['modules'] ?? [];
        ob_start(); $this->render($view, $data); $content = ob_get_clean();
        require ROOT . '/views/layout/header.php';
        require ROOT . '/views/layout/nav.php';
        echo '<div class="flash-messages">';
        foreach (Flash::get() as $msg) echo '<div class="flash flash-' . htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '</div>';
        echo $content;
        require ROOT . '/views/layout/footer.php';
    }

    public function index(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');

        $orgId    = Auth::orgId();
        $db       = Database::getInstance();

        // Date range
        $from = $_GET['from'] ?? date('Y-01-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $stmt = fn(string $sql, array $params = []) => ($s = $db->prepare($sql)) && $s->execute($params) ? (float)$s->fetchColumn() : 0.0;

        $base = 'SELECT COALESCE(SUM(amount),0) FROM transactions WHERE org_id = ? AND direction = "credit" AND transaction_date BETWEEN ? AND ?';

        $data = [
            'from'          => $from,
            'to'            => $to,
            'total_revenue' => $stmt($base, [$orgId, $from, $to]),
            'donations'     => $stmt($base . ' AND category = "donation"', [$orgId, $from, $to]),
            'dues'          => $stmt($base . ' AND category = "dues"', [$orgId, $from, $to]),
            'events'        => $stmt($base . ' AND category = "event"', [$orgId, $from, $to]),
            'grants'        => $stmt($base . ' AND category = "grant"', [$orgId, $from, $to]),
            'pageTitle'     => 'Finance Reports',
        ];

        // Fiscal year summary
        $fy = $_GET['fy'] ?? date('Y');
        $data['fy'] = $fy;
        $data['fy_total'] = $stmt('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE org_id = ? AND direction = "credit" AND fiscal_year = ?', [$orgId, $fy]);

        // Ledger
        $ledger = $db->prepare('SELECT t.*, c.first_name, c.last_name FROM transactions t LEFT JOIN contacts c ON c.id = t.contact_id WHERE t.org_id = ? AND t.transaction_date BETWEEN ? AND ? ORDER BY t.transaction_date DESC LIMIT 500');
        $ledger->execute([$orgId, $from, $to]);
        $data['ledger'] = $ledger->fetchAll();

        $this->layout('modules/finance/views/index.php', $data);
    }

    public function exportCsv(array $p): void
    {
        Auth::requireRole('super_admin', 'admin', 'staff');
        $orgId = Auth::orgId();
        $from  = $_GET['from'] ?? date('Y-01-01');
        $to    = $_GET['to']   ?? date('Y-m-d');

        $stmt = Database::getInstance()->prepare('SELECT t.*, c.first_name, c.last_name FROM transactions t LEFT JOIN contacts c ON c.id = t.contact_id WHERE t.org_id = ? AND t.transaction_date BETWEEN ? AND ? ORDER BY t.transaction_date DESC');
        $stmt->execute([$orgId, $from, $to]);
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions-' . $from . '-to-' . $to . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Category', 'Direction', 'Amount', 'Description', 'Contact', 'Fiscal Year']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['transaction_date'], $r['category'], $r['direction'], $r['amount'], $r['description'], trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')), $r['fiscal_year']]);
        }
        fclose($out);
        exit;
    }
}
