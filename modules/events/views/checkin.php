<div class="page-header">
    <h1 class="page-title">Check-In: <?= htmlspecialchars($event['title'], ENT_QUOTES,'UTF-8') ?></h1>
    <a href="<?= APP_URL ?>/events/<?= $event['id'] ?>" class="btn btn-secondary btn-sm">Back</a>
</div>
<div class="card">
<p class="text-muted"><?= htmlspecialchars($event['event_date'], ENT_QUOTES,'UTF-8') ?> · <?= $event['reg_count'] ?> registered</p>
<div class="table-wrap"><table>
<thead><tr><th>Name</th><th>Status</th><th>Check In</th></tr></thead>
<tbody>
<?php foreach ($regs as $r): ?>
<tr>
    <td style="font-size:1.05rem"><?= htmlspecialchars($r['name'], ENT_QUOTES,'UTF-8') ?></td>
    <td><span class="badge <?= $r['status'] === 'attended' ? 'badge-success' : 'badge-info' ?>"><?= htmlspecialchars($r['status'], ENT_QUOTES,'UTF-8') ?></span></td>
    <td>
        <?php if ($r['status'] === 'registered'): ?>
        <form method="post" action="<?= APP_URL ?>/events/<?= $event['id'] ?>/registrations/<?= $r['id'] ?>/checkin">
            <?= Csrf::field() ?><button class="btn btn-primary" style="font-size:1.1rem;padding:.6rem 1.2rem">✓ Check In</button>
        </form>
        <?php elseif ($r['status'] === 'attended'): ?>
        <span style="color:#16a34a;font-size:1.1rem">✓ Checked In</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</div>
