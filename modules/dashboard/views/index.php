<?php
$greeting = match(true) {
    (int)date('G') < 12 => 'Good morning',
    (int)date('G') < 17 => 'Good afternoon',
    default             => 'Good evening',
};
$user = Auth::user();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $greeting ?>, <?= htmlspecialchars(explode(' ', $user['name'] ?? 'there')[0], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted" style="margin:0;font-size:.8125rem"><?= date('l, F j, Y') ?></p>
    </div>
</div>

<div class="stat-cards">
    <a href="<?= APP_URL ?>/membership" class="stat-card stat-card-link" style="--accent:#2563eb">
        <div class="stat-label">Active Members</div>
        <div class="stat-value"><?= number_format($activeMembers) ?></div>
        <div class="stat-sub">
            <?php if ($expiringMembers > 0): ?>
            <span style="color:#d97706;font-weight:600"><?= $expiringMembers ?> expiring</span> in 30 days
            <?php else: ?>
            All memberships current
            <?php endif; ?>
        </div>
    </a>
    <a href="<?= APP_URL ?>/donors" class="stat-card stat-card-link" style="--accent:#16a34a">
        <div class="stat-label">Donations MTD</div>
        <div class="stat-value">$<?= number_format($donMtd, 0) ?></div>
        <div class="stat-sub">YTD: <strong>$<?= number_format($donYtd, 0) ?></strong></div>
    </a>
    <a href="<?= APP_URL ?>/volunteers" class="stat-card stat-card-link" style="--accent:#7c3aed">
        <div class="stat-label">Volunteer Hours MTD</div>
        <div class="stat-value"><?= number_format($volMtd, 1) ?></div>
        <div class="stat-sub">
            <?php if ($volPending > 0): ?>
            <span style="color:#d97706;font-weight:600"><?= $volPending ?> pending</span> approval
            <?php else: ?>
            All hours approved
            <?php endif; ?>
        </div>
    </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem">

<div class="card">
    <div class="card-header">
        <h3>Upcoming Events</h3>
        <a href="<?= APP_URL ?>/events" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <?php if ($upcomingEvents): ?>
    <?php foreach ($upcomingEvents as $e): ?>
    <div style="padding:.5rem 0;border-bottom:1px solid var(--gray-100)">
        <a href="<?= APP_URL ?>/events/<?= $e['id'] ?>" style="font-weight:600;font-size:.875rem;color:var(--gray-900);text-decoration:none;display:block"><?= htmlspecialchars($e['title'], ENT_QUOTES,'UTF-8') ?></a>
        <span class="text-muted" style="font-size:.75rem"><?= fmt_date($e['event_date']) ?><?= $e['location'] ? ' &middot; ' . htmlspecialchars($e['location'], ENT_QUOTES,'UTF-8') : '' ?></span>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="empty-state" style="padding:1.5rem 0">
        <p class="text-muted" style="font-size:.8125rem;text-align:center;margin:0">No upcoming events scheduled.</p>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3>Grant Deadlines</h3>
        <a href="<?= APP_URL ?>/grants" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <?php if ($nextDeadlines): ?>
    <?php foreach ($nextDeadlines as $g):
        $days = (int)$g['days_left'];
        $urgencyColor = $days <= 7 ? '#dc2626' : ($days <= 14 ? '#d97706' : '#16a34a');
        $urgencyBg    = $days <= 7 ? '#fef2f2' : ($days <= 14 ? '#fffbeb' : '#f0fdf4');
    ?>
    <div style="padding:.5rem 0;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center;gap:.5rem">
        <div style="min-width:0">
            <a href="<?= APP_URL ?>/grants/<?= $g['id'] ?>" style="font-weight:600;font-size:.875rem;color:var(--gray-900);text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($g['title'], ENT_QUOTES,'UTF-8') ?></a>
            <span class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($g['funder_name'], ENT_QUOTES,'UTF-8') ?></span>
        </div>
        <span style="background:<?= $urgencyBg ?>;color:<?= $urgencyColor ?>;font-size:.7rem;font-weight:700;padding:.2rem .5rem;border-radius:9999px;white-space:nowrap;flex-shrink:0"><?= $days ?>d left</span>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div style="padding:1.5rem 0;text-align:center">
        <p class="text-muted" style="font-size:.8125rem;margin:0">No upcoming grant deadlines.</p>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Activity</h3>
    </div>
    <?php if ($auditLog): ?>
    <?php foreach ($auditLog as $a): ?>
    <div style="padding:.4rem 0;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:baseline;gap:.75rem">
        <div style="min-width:0">
            <span style="font-weight:500;font-size:.8125rem;color:var(--gray-800)"><?= htmlspecialchars(humanize_action($a['action']), ENT_QUOTES,'UTF-8') ?></span>
            <span class="text-muted" style="font-size:.75rem"> &mdash; <?= htmlspecialchars($a['user_name'] ?? 'System', ENT_QUOTES,'UTF-8') ?></span>
        </div>
        <span class="text-muted" style="white-space:nowrap;font-size:.7rem;flex-shrink:0"><?= fmt_date($a['created_at'], 'M j') ?></span>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <p class="text-muted" style="font-size:.8125rem;text-align:center;padding:1rem 0;margin:0">No activity recorded yet.</p>
    <?php endif; ?>
</div>

</div>
