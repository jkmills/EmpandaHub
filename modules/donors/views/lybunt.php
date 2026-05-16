<div class="page-header"><h1 class="page-title">LYBUNT Report</h1><p class="text-muted" style="font-size:.88rem;margin:0">Donors who gave Last Year But Unfortunately Not This year.</p><a href="<?= APP_URL ?>/donors" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Name</th><th>Email</th><th>Last Year Total</th></tr></thead>
<tbody>
<?php foreach ($list as $r): ?>
<tr><td><a href="<?= APP_URL ?>/crm/<?= $r['id'] ?>"><?= htmlspecialchars($r['first_name'].' '.$r['last_name'], ENT_QUOTES,'UTF-8') ?></a></td><td><?= htmlspecialchars($r['email'] ?? '', ENT_QUOTES,'UTF-8') ?></td><td>$<?= number_format((float)$r['last_year_total'], 2) ?></td></tr>
<?php endforeach; ?>
<?php if (!$list): ?><tr><td colspan="3" class="text-muted" style="text-align:center">No LYBUNT donors found.</td></tr><?php endif; ?>
</tbody></table></div></div>
