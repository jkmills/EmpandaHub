<?php
declare(strict_types=1);

// Must be run from CLI only
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

define('ROOT', dirname(__DIR__));
require ROOT . '/config/config.php';
require ROOT . '/core/Database.php';
require ROOT . '/core/EngagementScore.php';
require ROOT . '/core/Updater.php';

$db  = Database::getInstance();
$now = date('Y-m-d');
$log = fn(string $msg) => print(date('[Y-m-d H:i:s] ') . $msg . PHP_EOL);

// --- 1. Membership status transitions ---
// active → grace when past end_date and within grace period
$db->prepare("
    UPDATE memberships m
    JOIN membership_tiers t ON t.id = m.tier_id
    SET m.status = 'grace', m.updated_at = NOW()
    WHERE m.status = 'active'
      AND m.end_date IS NOT NULL
      AND m.end_date < ?
      AND DATE_ADD(m.end_date, INTERVAL t.grace_period_days DAY) >= ?
      AND t.billing_cycle != 'lifetime'
")->execute([$now, $now]);

// grace → expired when past grace period
$db->prepare("
    UPDATE memberships m
    JOIN membership_tiers t ON t.id = m.tier_id
    SET m.status = 'expired', m.updated_at = NOW()
    WHERE m.status = 'grace'
      AND m.end_date IS NOT NULL
      AND DATE_ADD(m.end_date, INTERVAL t.grace_period_days DAY) < ?
      AND t.billing_cycle != 'lifetime'
")->execute([$now]);

$log('Membership status transitions complete.');

// --- 2. Dues reminders 14 days before end_date ---
$upcoming = $db->prepare("
    SELECT m.*, c.email, c.first_name, o.name AS org_name
    FROM memberships m
    JOIN contacts c ON c.id = m.contact_id
    JOIN organizations o ON o.id = m.org_id
    WHERE m.status = 'active'
      AND m.end_date IS NOT NULL
      AND m.end_date = DATE_ADD(?, INTERVAL 14 DAY)
")->execute([$now]) ? $db->query('SELECT * FROM memberships WHERE 1=0') : null;

// Re-run with real execute
$remind14 = $db->prepare("
    SELECT m.id, m.org_id, c.email, c.first_name, m.end_date, o.name AS org_name
    FROM memberships m
    JOIN contacts c ON c.id = m.contact_id
    JOIN organizations o ON o.id = m.org_id
    WHERE m.status = 'active'
      AND m.end_date IS NOT NULL
      AND m.end_date = DATE_ADD(?, INTERVAL 14 DAY)
");
$remind14->execute([$now]);
$rows = $remind14->fetchAll(PDO::FETCH_ASSOC);
$log('Dues reminders: ' . count($rows) . ' memberships expiring in 14 days.');
// In production: instantiate Mailer and send per-row

// --- 3. Membership expiry notices 30 days out ---
$remind30 = $db->prepare("
    SELECT m.id, m.org_id, c.email, c.first_name, m.end_date
    FROM memberships m JOIN contacts c ON c.id = m.contact_id
    WHERE m.status = 'active' AND m.end_date = DATE_ADD(?, INTERVAL 30 DAY)
");
$remind30->execute([$now]);
$log('Expiry 30-day notices: ' . $remind30->rowCount() . ' memberships.');

// --- 4. Grant deadline alerts 14 days out ---
$grantAlerts = $db->prepare("
    SELECT g.id, g.org_id, g.title, g.deadline_date, f.name AS funder_name
    FROM `grants` g JOIN funders f ON f.id = g.funder_id
    WHERE g.status NOT IN ('awarded','declined')
      AND g.deadline_date = DATE_ADD(?, INTERVAL 14 DAY)
");
$grantAlerts->execute([$now]);
$log('Grant deadline alerts: ' . $grantAlerts->rowCount() . ' grants due in 14 days.');

// --- 5. Grant report due date alerts 14 days out ---
$reportAlerts = $db->prepare("
    SELECT gr.id, gr.grant_id, gr.title, gr.due_date, g.org_id
    FROM grant_reports gr JOIN `grants` g ON g.id = gr.grant_id
    WHERE gr.submitted_date IS NULL AND gr.due_date = DATE_ADD(?, INTERVAL 14 DAY)
");
$reportAlerts->execute([$now]);
$log('Grant report alerts: ' . $reportAlerts->rowCount() . ' reports due in 14 days.');

// --- 6. Recurring donation record generation ---
// Create a new donation row (no charge) for recurring donations whose interval is due
$recurring = $db->prepare("
    SELECT d.* FROM donations d
    WHERE d.is_recurring = 1 AND d.recur_interval IS NOT NULL
      AND d.donated_on = CASE
            WHEN d.recur_interval = 'monthly'   THEN DATE_SUB(?, INTERVAL 1 MONTH)
            WHEN d.recur_interval = 'quarterly' THEN DATE_SUB(?, INTERVAL 3 MONTH)
            WHEN d.recur_interval = 'annual'    THEN DATE_SUB(?, INTERVAL 1 YEAR)
          END
");
$recurring->execute([$now, $now, $now]);
$newDonations = $recurring->fetchAll(PDO::FETCH_ASSOC);

foreach ($newDonations as $d) {
    try {
        $db->prepare("INSERT INTO donations (org_id, contact_id, campaign_id, amount, donated_on, is_recurring, recur_interval, is_anonymous, method, note, fiscal_year)
                      VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, 'Auto-generated recurring', ?)")
           ->execute([$d['org_id'], $d['contact_id'], $d['campaign_id'], $d['amount'], $now, $d['recur_interval'], $d['is_anonymous'], $d['method'], date('Y')]);
    } catch (Throwable $e) {
        $log('Recurring donation error: ' . $e->getMessage());
    }
}
$log('Recurring donations generated: ' . count($newDonations));

// --- 7. Engagement score bulk recalculation ---
$count = EngagementScore::refreshAll($db);
$log("Engagement scores recalculated: $count contacts.");

// --- 8. Refresh update cache so the nav banner stays current ---
$release = Updater::latestRelease(forceRefresh: true);
$log('Update cache refreshed' . ($release ? ': latest is v' . $release['version'] : ': GitHub unreachable, cache unchanged') . '.');

$log('Daily cron complete.');
