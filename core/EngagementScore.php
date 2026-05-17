<?php
declare(strict_types=1);

class EngagementScore
{
    private const WEIGHTS = [
        'donation_recency'    => 25,
        'donation_frequency'  => 20,
        'donation_cumulative' => 20,
        'event_attendance'    => 15,
        'volunteer_hours'     => 10,
        'email_engagement'    => 10,
    ];

    // Module that must be enabled for each component
    private const MODULE_DEPS = [
        'donation_recency'    => 'donors',
        'donation_frequency'  => 'donors',
        'donation_cumulative' => 'donors',
        'event_attendance'    => 'events',
        'volunteer_hours'     => 'volunteers',
        'email_engagement'    => 'email', // not yet built; skipped unless explicitly on
    ];

    public static function compute(int $orgId, int $contactId, array $enabledModules): int
    {
        $db = Database::getInstance();
        $activeWeights = self::activeWeights($enabledModules);
        $total = array_sum($activeWeights);
        if ($total === 0) return 0;

        $score = 0.0;
        foreach ($activeWeights as $component => $weight) {
            $raw    = self::component($db, $orgId, $contactId, $component);
            $score += $raw * ($weight / $total) * 100;
        }

        return (int)round(min(100, max(0, $score)));
    }

    /** Returns per-component contributions for the breakdown tooltip. */
    public static function breakdown(int $orgId, int $contactId, array $enabledModules): array
    {
        $db = Database::getInstance();
        $activeWeights = self::activeWeights($enabledModules);
        $total = array_sum($activeWeights);
        $result = [];

        foreach (self::WEIGHTS as $component => $weight) {
            $active = isset($activeWeights[$component]);
            $raw    = $active ? self::component($db, $orgId, $contactId, $component) : null;
            $result[$component] = [
                'active'      => $active,
                'weight'      => $active ? (int)round(($weight / ($total ?: 1)) * 100) : $weight,
                'contribution'=> $active ? (int)round($raw * ($weight / ($total ?: 1)) * 100) : null,
            ];
        }
        return $result;
    }

    public static function refresh(int $orgId, int $contactId): int
    {
        $db      = Database::getInstance();
        $modules = self::orgModules($orgId, $db);
        $score   = self::compute($orgId, $contactId, $modules);

        $db->prepare('UPDATE contacts SET engagement_score=?, engagement_score_at=NOW() WHERE id=? AND org_id=?')
           ->execute([$score, $contactId, $orgId]);

        return $score;
    }

    /** Bulk-recalculate all contacts for every org. Returns count updated. */
    public static function refreshAll(\PDO $db): int
    {
        $orgs  = $db->query('SELECT id FROM organizations')->fetchAll(PDO::FETCH_COLUMN);
        $count = 0;

        foreach ($orgs as $orgId) {
            $orgId   = (int)$orgId;
            $modules = self::orgModules($orgId, $db);
            $stmt    = $db->prepare('SELECT id FROM contacts WHERE org_id=? AND merged_into_id IS NULL');
            $stmt->execute([$orgId]);

            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $cId) {
                $score = self::compute($orgId, (int)$cId, $modules);
                $db->prepare('UPDATE contacts SET engagement_score=?, engagement_score_at=NOW() WHERE id=?')
                   ->execute([$score, (int)$cId]);
                $count++;
            }
        }
        return $count;
    }

    public static function badge(int $score): string
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }

    public static function badgeHtml(int $score): string
    {
        $color = match(self::badge($score)) {
            'high'   => ['bg' => '#dcfce7', 'fg' => '#166534'],
            'medium' => ['bg' => '#fef3c7', 'fg' => '#92400e'],
            default  => ['bg' => '#fee2e2', 'fg' => '#991b1b'],
        };
        return sprintf(
            '<span style="display:inline-block;padding:.15rem .5rem;border-radius:.25rem;font-size:.75rem;font-weight:600;background:%s;color:%s">%d</span>',
            $color['bg'], $color['fg'], $score
        );
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private static function activeWeights(array $enabledModules): array
    {
        $active = [];
        foreach (self::WEIGHTS as $component => $weight) {
            $dep = self::MODULE_DEPS[$component];
            if ($dep === 'email') {
                // Email module must be explicitly enabled
                if (empty($enabledModules[$dep])) continue;
            } elseif (isset($enabledModules[$dep]) && !$enabledModules[$dep]) {
                continue;
            }
            $active[$component] = $weight;
        }
        return $active;
    }

    private static function component(\PDO $db, int $orgId, int $cId, string $component): float
    {
        $cut12 = date('Y-m-d', strtotime('-12 months'));
        $cut24 = date('Y-m-d', strtotime('-24 months'));

        switch ($component) {
            case 'donation_recency':
                $s = $db->prepare('SELECT MAX(donated_on) FROM donations WHERE org_id=? AND contact_id=?');
                $s->execute([$orgId, $cId]);
                $last = $s->fetchColumn();
                if (!$last) return 0.0;
                if ($last >= $cut12) return 1.0;
                if ($last >= $cut24) return 0.5;
                return 0.0;

            case 'donation_frequency':
                $s = $db->prepare('SELECT COUNT(*) FROM donations WHERE org_id=? AND contact_id=? AND donated_on >= ?');
                $s->execute([$orgId, $cId, $cut12]);
                return min(1.0, (int)$s->fetchColumn() / 4);

            case 'donation_cumulative':
                $s = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM donations WHERE org_id=? AND contact_id=?');
                $s->execute([$orgId, $cId]);
                return min(1.0, (float)$s->fetchColumn() / 10000);

            case 'event_attendance':
                $s = $db->prepare(
                    "SELECT COUNT(*) FROM event_registrations er
                     JOIN events e ON e.id = er.event_id
                     WHERE er.org_id=? AND er.contact_id=? AND e.event_date >= ? AND er.status != 'cancelled'"
                );
                $s->execute([$orgId, $cId, $cut12]);
                return min(1.0, (int)$s->fetchColumn() / 3);

            case 'volunteer_hours':
                $s = $db->prepare(
                    "SELECT COALESCE(SUM(vh.hours),0)
                     FROM volunteer_hours vh
                     JOIN volunteers v ON v.id = vh.volunteer_id
                     WHERE v.org_id=? AND v.contact_id=? AND vh.status='approved' AND vh.activity_date >= ?"
                );
                $s->execute([$orgId, $cId, $cut12]);
                return min(1.0, (float)$s->fetchColumn() / 50);

            case 'email_engagement':
                return 0.0;
        }
        return 0.0;
    }

    private static function orgModules(int $orgId, \PDO $db): array
    {
        $s = $db->prepare('SELECT config_json FROM organizations WHERE id=?');
        $s->execute([$orgId]);
        $config = json_decode($s->fetchColumn() ?: '{}', true);
        return $config['modules'] ?? [];
    }
}
