<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="d-flex gap-1">
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <a href="<?= APP_URL ?>/crm/<?= $contact['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/crm" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>

<!-- Contact Info + Notes -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

<div class="card">
    <h3 style="margin-top:0">Contact Info</h3>
    <table style="width:100%">
        <tr><td class="text-muted" style="width:35%">Email</td>
            <td><?php $e = $contact['email'] ?? ''; echo $e ? '<a href="mailto:' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</a>' : '—'; ?></td></tr>
        <tr><td class="text-muted">Phone</td><td><?= htmlspecialchars($contact['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Address</td><td><?php
            $addr = trim(($contact['address'] ?? '') . ' ' . ($contact['city'] ?? '') . ' ' . ($contact['state'] ?? '') . ' ' . ($contact['zip'] ?? ''));
            echo htmlspecialchars($addr ?: '—', ENT_QUOTES, 'UTF-8');
        ?></td></tr>
        <tr><td class="text-muted">Country</td><td><?= htmlspecialchars($contact['country'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Added</td><td><?= fmt_date($contact['created_at']) ?></td></tr>
    </table>
    <?php if ($contact['tags']): ?>
    <div class="mt-2">
        <?php foreach ($contact['tags'] as $tag): ?>
        <span class="badge badge-info"><?= htmlspecialchars($tag['tag'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-top:0">Notes</h3>
    <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
    <form method="post" action="<?= APP_URL ?>/crm/<?= $contact['id'] ?>/note" class="mb-2">
        <?= Csrf::field() ?>
        <textarea name="body" rows="3" maxlength="2000" placeholder="Add a note…" style="width:100%;margin-bottom:.4rem"></textarea>
        <button type="submit" class="btn btn-primary btn-sm">Add Note</button>
    </form>
    <?php endif; ?>
    <?php foreach ($contact['notes'] as $note): ?>
    <div style="border-top:1px solid #e2e8f0;padding:.6rem 0">
        <p style="margin:0"><?= nl2br(htmlspecialchars($note['body'], ENT_QUOTES, 'UTF-8')) ?></p>
        <p class="text-muted" style="font-size:.78rem;margin:.2rem 0 0"><?= htmlspecialchars($note['author'] ?? 'Staff', ENT_QUOTES, 'UTF-8') ?> · <?= fmt_datetime($note['created_at']) ?></p>
    </div>
    <?php endforeach; ?>
    <?php if (!$contact['notes']): ?><p class="text-muted">No notes yet.</p><?php endif; ?>
</div>

</div>

<!-- Board Positions -->
<div class="card" style="margin-bottom:1.5rem">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <h3 style="margin:0">Board &amp; Committee Positions</h3>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <details style="position:relative">
            <summary class="btn btn-primary btn-sm" style="cursor:pointer;list-style:none;display:inline-block">+ Add Position</summary>
            <div style="position:absolute;right:0;top:2.2rem;z-index:10;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;min-width:540px;box-shadow:0 4px 16px rgba(0,0,0,.08)">
                <form method="post" action="<?= APP_URL ?>/crm/<?= $contact['id'] ?>/board-position">
                    <?= Csrf::field() ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;margin-bottom:.5rem">
                        <div class="form-group" style="margin:0">
                            <label style="font-size:.78rem">Title *</label>
                            <input name="title" required placeholder="e.g. President">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label style="font-size:.78rem">Committee</label>
                            <input name="committee" placeholder="e.g. Executive">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label style="font-size:.78rem">Start Date *</label>
                            <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 2fr;gap:.5rem;margin-bottom:.75rem">
                        <div class="form-group" style="margin:0">
                            <label style="font-size:.78rem">End Date</label>
                            <input type="date" name="end_date">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label style="font-size:.78rem">Notes</label>
                            <input name="notes" placeholder="Optional notes">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Save Position</button>
                </form>
            </div>
        </details>
        <?php endif; ?>
    </div>

    <?php if ($boardPositions): ?>
    <div class="table-wrap">
    <table>
    <thead>
        <tr>
            <th>Title</th><th>Committee</th><th>Start</th><th>End</th><th>Status</th>
            <?php if (Auth::hasRole('super_admin','admin','staff')): ?><th></th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($boardPositions as $pos): ?>
    <?php $isActive = empty($pos['end_date']) || $pos['end_date'] >= date('Y-m-d'); ?>
    <tr>
        <td><strong><?= htmlspecialchars($pos['title'], ENT_QUOTES, 'UTF-8') ?></strong></td>
        <td><?= htmlspecialchars($pos['committee'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= fmt_date($pos['start_date']) ?></td>
        <td><?= !empty($pos['end_date']) ? fmt_date($pos['end_date']) : '<span class="text-muted">Present</span>' ?></td>
        <td><span class="badge <?= $isActive ? 'badge-success' : 'badge-muted' ?>"><?= $isActive ? 'Active' : 'Ended' ?></span></td>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <td>
            <?php if (empty($pos['end_date'])): ?>
            <form method="post" action="<?= APP_URL ?>/crm/<?= $contact['id'] ?>/board-position/<?= $pos['id'] ?>/end"
                  style="display:inline-flex;gap:.3rem;align-items:center">
                <?= Csrf::field() ?>
                <input type="date" name="end_date" value="<?= date('Y-m-d') ?>"
                       style="padding:.2rem .4rem;font-size:.82rem;border:1px solid #d1d5db;border-radius:4px">
                <button type="submit" class="btn btn-secondary btn-sm">End</button>
            </form>
            <?php endif; ?>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php else: ?>
    <p class="text-muted" style="margin:0">No board or committee positions recorded.</p>
    <?php endif; ?>
</div>

<!-- Module-gated profile sections -->
<?php $showModules = ($modules['membership'] ?? false) || ($modules['donors'] ?? false) || ($modules['volunteers'] ?? false); ?>
<?php if ($showModules): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:1.5rem">

<?php if ($modules['membership'] ?? false): ?>
<div class="card">
    <h3 style="margin-top:0">Membership</h3>
    <?php if ($membershipProfile): ?>
    <table style="width:100%">
        <tr><td class="text-muted" style="width:40%">Tier</td>
            <td><strong><?= htmlspecialchars($membershipProfile['tier_name'], ENT_QUOTES, 'UTF-8') ?></strong></td></tr>
        <tr><td class="text-muted">Status</td><td>
            <?php
            $sc = match($membershipProfile['status']) {
                'active','lifetime' => 'badge-success',
                'grace'   => 'badge-warning',
                'expired' => 'badge-danger',
                default   => 'badge-muted',
            };
            ?>
            <span class="badge <?= $sc ?>"><?= htmlspecialchars(ucfirst($membershipProfile['status']), ENT_QUOTES, 'UTF-8') ?></span>
        </td></tr>
        <tr><td class="text-muted">Since</td><td><?= fmt_date($membershipProfile['start_date']) ?></td></tr>
        <?php if ($membershipProfile['billing_cycle'] !== 'lifetime' && !empty($membershipProfile['end_date'])): ?>
        <tr><td class="text-muted">Next Due</td><td><?= fmt_date($membershipProfile['end_date']) ?></td></tr>
        <?php endif; ?>
        <tr><td class="text-muted">Total Paid</td>
            <td>$<?= number_format((float)($membershipProfile['total_paid'] ?? 0), 2) ?></td></tr>
    </table>
    <div class="mt-2">
        <a href="<?= APP_URL ?>/membership/<?= $membershipProfile['id'] ?>" class="btn btn-secondary btn-sm">View Membership</a>
    </div>
    <?php else: ?>
    <p class="text-muted">No active membership on record.</p>
    <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
    <a href="<?= APP_URL ?>/membership/create" class="btn btn-primary btn-sm">+ Add Membership</a>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (($modules['donors'] ?? false) && $donorProfile !== null && (int)($donorProfile['summary']['gift_count'] ?? 0) > 0): ?>
<?php $summary = $donorProfile['summary']; ?>
<div class="card">
    <h3 style="margin-top:0">Giving History</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.75rem">
        <div style="background:#f8fafc;border-radius:6px;padding:.5rem .75rem;text-align:center">
            <div style="font-size:.78rem;color:#64748b">Total Given</div>
            <div style="font-size:1.4rem;font-weight:700">$<?= number_format((float)$summary['total_given'], 0) ?></div>
        </div>
        <div style="background:#f8fafc;border-radius:6px;padding:.5rem .75rem;text-align:center">
            <div style="font-size:.78rem;color:#64748b">Gifts</div>
            <div style="font-size:1.4rem;font-weight:700"><?= (int)$summary['gift_count'] ?></div>
        </div>
    </div>
    <?php if ($summary['last_gift_date']): ?>
    <p style="font-size:.83rem;color:#64748b;margin:.1rem 0 .6rem">
        First gift: <?= fmt_date($summary['first_gift_date']) ?> &nbsp;·&nbsp; Last: <?= fmt_date($summary['last_gift_date']) ?>
    </p>
    <?php endif; ?>
    <?php if ($donorProfile['recent']): ?>
    <div class="table-wrap">
    <table style="font-size:.85rem">
        <thead><tr><th>Date</th><th>Campaign</th><th style="text-align:right">Amount</th></tr></thead>
        <tbody>
        <?php foreach ($donorProfile['recent'] as $d): ?>
        <tr>
            <td><?= fmt_date($d['donated_on']) ?></td>
            <td><?= htmlspecialchars($d['campaign_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
            <td style="text-align:right">$<?= number_format((float)$d['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
    <div class="mt-2">
        <a href="<?= APP_URL ?>/donors" class="btn btn-secondary btn-sm">View All Donations</a>
    </div>
</div>
<?php endif; ?>

<?php if (($modules['volunteers'] ?? false) && $volunteerProfile !== null && $volunteerProfile['volunteer'] !== null): ?>
<?php $vol = $volunteerProfile['volunteer']; ?>
<div class="card">
    <h3 style="margin-top:0">Volunteer</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.75rem">
        <div style="background:#f8fafc;border-radius:6px;padding:.5rem .75rem;text-align:center">
            <div style="font-size:.78rem;color:#64748b">Approved Hours</div>
            <div style="font-size:1.4rem;font-weight:700"><?= number_format((float)$vol['total_hours'], 1) ?></div>
        </div>
        <div style="background:#f8fafc;border-radius:6px;padding:.5rem .75rem;text-align:center">
            <div style="font-size:.78rem;color:#64748b">Status</div>
            <div style="margin-top:.3rem">
                <?php $vs = $vol['status'] ?? 'active'; ?>
                <span class="badge <?= $vs === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= htmlspecialchars(ucfirst($vs), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
    <?php if (!empty($vol['recent_date'])): ?>
    <p style="font-size:.83rem;color:#64748b;margin:.1rem 0 .6rem">Last activity: <?= fmt_date($vol['recent_date']) ?></p>
    <?php endif; ?>
    <?php if ($volunteerProfile['recent']): ?>
    <div class="table-wrap">
    <table style="font-size:.85rem">
        <thead><tr><th>Date</th><th>Hours</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach ($volunteerProfile['recent'] as $h): ?>
        <tr>
            <td><?= fmt_date($h['activity_date']) ?></td>
            <td><?= number_format((float)$h['hours'], 1) ?></td>
            <td><?= htmlspecialchars($h['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
    <div class="mt-2">
        <a href="<?= APP_URL ?>/volunteers/<?= $vol['id'] ?>" class="btn btn-secondary btn-sm">View Volunteer Profile</a>
    </div>
</div>
<?php endif; ?>

</div><!-- end module grid -->
<?php endif; ?>
