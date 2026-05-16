<div class="page-header">
    <h1 class="page-title">Duplicate Contacts</h1>
    <a href="<?= APP_URL ?>/crm" class="btn btn-secondary btn-sm">Back</a>
</div>
<?php if (!$dupes): ?>
<div class="card"><p class="text-muted">No duplicate emails found.</p></div>
<?php else: ?>
<div class="card">
<div class="table-wrap">
<table>
<thead><tr><th>Email</th><th>Count</th><th>Contact IDs</th><th>Merge</th></tr></thead>
<tbody>
<?php foreach ($dupes as $d):
    $ids = explode(',', $d['ids']);
    $keepId  = (int)$ids[0];
    $mergeId = (int)($ids[1] ?? 0);
?>
<tr>
    <td><?= htmlspecialchars($d['email'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= (int)$d['cnt'] ?></td>
    <td><?= htmlspecialchars($d['ids'], ENT_QUOTES, 'UTF-8') ?></td>
    <td>
        <?php if ($mergeId): ?>
        <form method="post" action="<?= APP_URL ?>/crm/merge">
            <?= Csrf::field() ?>
            <input type="hidden" name="keep_id"  value="<?= $keepId ?>">
            <input type="hidden" name="merge_id" value="<?= $mergeId ?>">
            <button class="btn btn-secondary btn-sm" data-confirm="Merge #<?= $mergeId ?> into #<?= $keepId ?>?">Merge</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>
