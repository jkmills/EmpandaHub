<div class="page-header"><h1 class="page-title">Overdue Memberships</h1><a href="<?= APP_URL ?>/membership" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Member</th><th>Email</th><th>Tier</th><th>Expired</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($list as $m): ?>
<tr>
    <td><?= htmlspecialchars($m['first_name'].' '.$m['last_name'], ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($m['email'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($m['tier_name'], ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($m['end_date'], ENT_QUOTES,'UTF-8') ?></td>
    <td><a href="<?= APP_URL ?>/membership/<?= $m['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$list): ?><tr><td colspan="5" class="text-muted" style="text-align:center">No overdue memberships.</td></tr><?php endif; ?>
</tbody></table></div></div>
