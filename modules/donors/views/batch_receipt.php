<div class="page-header">
    <h1 class="page-title">Batch Receipts — FY <?= $fy ?></h1>
    <div class="d-flex gap-1">
        <form method="get" action="<?= APP_URL ?>/donors/batch-receipt" class="d-flex gap-1 align-center">
            <label style="margin:0">Fiscal Year:</label>
            <input type="number" name="fy" value="<?= $fy ?>" style="width:80px">
            <button class="btn btn-secondary btn-sm">Load</button>
        </form>
        <a href="<?= APP_URL ?>/donors" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>
<div class="card">
<p class="text-muted"><?= count($list) ?> donation(s) in FY<?= $fy ?>. Print this page or save as PDF to send batch receipts.</p>
<div class="table-wrap"><table>
<thead><tr><th>Donor</th><th>Email</th><th>Date</th><th>Amount</th><th>Method</th></tr></thead>
<tbody>
<?php foreach ($list as $d): ?>
<tr>
    <td><?= htmlspecialchars($d['first_name'].' '.$d['last_name'], ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($d['email'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($d['donated_on'], ENT_QUOTES,'UTF-8') ?></td>
    <td>$<?= number_format((float)$d['amount'], 2) ?></td>
    <td><?= htmlspecialchars($d['method'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td colspan="3"><strong>Total</strong></td><td><strong>$<?= number_format(array_sum(array_column($list, 'amount')), 2) ?></strong></td><td></td></tr></tfoot>
</table></div>
</div>
