<div class="page-header">
    <h1 class="page-title">Campaigns</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/donors" class="btn btn-secondary btn-sm">Donations</a>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <a href="<?= APP_URL ?>/donors/campaigns/create" class="btn btn-primary btn-sm">+ New Campaign</a>
        <?php endif; ?>
    </div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem">
<?php foreach ($campaigns as $c): $pct = $c['goal_amount'] ? min(100, round($c['raised'] / $c['goal_amount'] * 100)) : 0; ?>
<div class="card">
    <h3 style="margin:0 0 .4rem"><?= htmlspecialchars($c['name'], ENT_QUOTES,'UTF-8') ?></h3>
    <p class="text-muted" style="font-size:.82rem;margin:.2rem 0"><?= htmlspecialchars($c['description'] ?? '', ENT_QUOTES,'UTF-8') ?></p>
    <div style="margin:.8rem 0">
        <div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
        <div class="d-flex justify-between mt-1" style="font-size:.82rem">
            <span>$<?= number_format((float)$c['raised'], 0) ?> raised</span>
            <?php if ($c['goal_amount']): ?><span>Goal: $<?= number_format((float)$c['goal_amount'], 0) ?></span><?php endif; ?>
        </div>
    </div>
    <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
    <a href="<?= APP_URL ?>/donors/campaigns/<?= $c['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php if (!$campaigns): ?><p class="text-muted">No campaigns yet.</p><?php endif; ?>
</div>
