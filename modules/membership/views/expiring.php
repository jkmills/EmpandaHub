<div class="page-header"><h1 class="page-title">Expiring Memberships (Next 30 Days)</h1><a href="<?= APP_URL ?>/membership" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Member</th><th>Tier</th><th>Status</th><th>Expires</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($list as $m): ?>
<tr>
    <td><?= htmlspecialchars($m['first_name'].' '.$m['last_name'], ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($m['tier_name'], ENT_QUOTES,'UTF-8') ?></td>
    <td><span class="badge badge-warning"><?= htmlspecialchars($m['status'], ENT_QUOTES,'UTF-8') ?></span></td>
    <td><?= htmlspecialchars($m['end_date'], ENT_QUOTES,'UTF-8') ?></td>
    <td><a href="<?= APP_URL ?>/membership/<?= $m['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$list): ?><tr><td colspan="5" class="text-muted" style="text-align:center">No memberships expiring in the next 30 days.</td></tr><?php endif; ?>
</tbody></table></div></div>
