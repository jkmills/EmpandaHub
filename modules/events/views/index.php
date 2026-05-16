<div class="page-header">
    <h1 class="page-title">Events</h1>
    <?php if (Auth::hasRole('super_admin','admin','staff')): ?><a href="<?= APP_URL ?>/events/create" class="btn btn-primary btn-sm">+ New Event</a><?php endif; ?>
</div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Title</th><th>Date</th><th>Location</th><th>Registered</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($events as $e): ?>
<tr>
    <td><a href="<?= APP_URL ?>/events/<?= $e['id'] ?>"><?= htmlspecialchars($e['title'], ENT_QUOTES,'UTF-8') ?></a></td>
    <td><?= htmlspecialchars($e['event_date'], ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($e['location'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?= $e['reg_count'] ?><?= $e['capacity'] ? ' / '.$e['capacity'] : '' ?></td>
    <td><?= $e['price'] > 0 ? '$'.number_format((float)$e['price'], 2) : 'Free' ?></td>
    <td><span class="badge <?= $e['is_published'] ? 'badge-success' : 'badge-muted' ?>"><?= $e['is_published'] ? 'Published' : 'Draft' ?></span></td>
    <td class="d-flex gap-1">
        <a href="<?= APP_URL ?>/events/<?= $e['id'] ?>" class="btn btn-secondary btn-sm">View</a>
        <a href="<?= APP_URL ?>/events/<?= $e['id'] ?>/checkin" class="btn btn-secondary btn-sm">Check-In</a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$events): ?><tr><td colspan="7" class="text-muted" style="text-align:center">No events.</td></tr><?php endif; ?>
</tbody></table></div></div>
