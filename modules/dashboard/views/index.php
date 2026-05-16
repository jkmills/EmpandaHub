<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <span class="text-muted" style="font-size:.85rem"><?= date('F j, Y') ?></span>
</div>

<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-label">Active Members</div>
        <div class="stat-value"><?= number_format($activeMembers) ?></div>
        <div class="stat-sub"><?= $expiringMembers ?> expiring in 30 days</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Donations MTD</div>
        <div class="stat-value">$<?= number_format($donMtd, 0) ?></div>
        <div class="stat-sub">YTD: $<?= number_format($donYtd, 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Volunteer Hours MTD</div>
        <div class="stat-value"><?= number_format($volMtd, 1) ?></div>
        <div class="stat-sub"><?= $volPending ?> pending approval<?= $volPending !== 1 ? 's' : '' ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.5rem">

<div class="card">
    <h3 style="margin-top:0">Upcoming Events</h3>
    <?php foreach ($upcomingEvents as $e): ?>
    <div style="padding:.4rem 0;border-bottom:1px solid #f1f5f9">
        <a href="<?= APP_URL ?>/events/<?= $e['id'] ?>" style="font-weight:600"><?= htmlspecialchars($e['title'], ENT_QUOTES,'UTF-8') ?></a>
        <p class="text-muted" style="font-size:.8rem;margin:.1rem 0 0"><?= htmlspecialchars($e['event_date'], ENT_QUOTES,'UTF-8') ?><?= $e['location'] ? ' · '.htmlspecialchars($e['location'], ENT_QUOTES,'UTF-8') : '' ?></p>
    </div>
    <?php endforeach; ?>
    <?php if (!$upcomingEvents): ?><p class="text-muted">No upcoming events.</p><?php endif; ?>
    <a href="<?= APP_URL ?>/events" class="btn btn-secondary btn-sm mt-1">All Events</a>
</div>

<div class="card">
    <h3 style="margin-top:0">Grant Deadlines</h3>
    <?php foreach ($nextDeadlines as $g): $urgency = $g['days_left'] <= 7 ? '#dc2626' : ($g['days_left'] <= 14 ? '#d97706' : '#16a34a'); ?>
    <div style="padding:.4rem 0;border-bottom:1px solid #f1f5f9">
        <a href="<?= APP_URL ?>/grants/<?= $g['id'] ?>" style="font-weight:600"><?= htmlspecialchars($g['title'], ENT_QUOTES,'UTF-8') ?></a>
        <p class="text-muted" style="font-size:.8rem;margin:.1rem 0 0"><?= htmlspecialchars($g['funder_name'], ENT_QUOTES,'UTF-8') ?> · <span style="color:<?= $urgency ?>"><?= $g['days_left'] ?> days</span></p>
    </div>
    <?php endforeach; ?>
    <?php if (!$nextDeadlines): ?><p class="text-muted">No upcoming deadlines.</p><?php endif; ?>
    <a href="<?= APP_URL ?>/grants" class="btn btn-secondary btn-sm mt-1">All Grants</a>
</div>

<div class="card">
    <h3 style="margin-top:0">Recent Activity</h3>
    <?php foreach ($auditLog as $a): ?>
    <div style="padding:.3rem 0;border-bottom:1px solid #f1f5f9;font-size:.82rem">
        <span class="badge badge-muted"><?= htmlspecialchars($a['action'], ENT_QUOTES,'UTF-8') ?></span>
        <span class="text-muted"><?= htmlspecialchars($a['user_name'] ?? 'System', ENT_QUOTES,'UTF-8') ?></span>
        <span class="text-muted" style="float:right"><?= htmlspecialchars(substr($a['created_at'], 5, 11), ENT_QUOTES,'UTF-8') ?></span>
    </div>
    <?php endforeach; ?>
    <?php if (!$auditLog): ?><p class="text-muted">No activity yet.</p><?php endif; ?>
</div>

</div>
