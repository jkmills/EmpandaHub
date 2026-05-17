<?php
$total     = array_sum(array_column($donations, 'amount'));
$fromLabel = date('F j, Y', strtotime($from));
$toLabel   = date('F j, Y', strtotime($to));
$donorName = htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name'], ENT_QUOTES, 'UTF-8');
?>
<div class="no-print">
    <button class="btn-print" onclick="window.print()">Print / Save PDF</button>
    <a class="btn-close" href="javascript:history.back()">Close</a>
</div>

<div class="receipt-header">
    <div class="org-block">
        <?php if (!empty($orgLogo)): ?>
        <img src="<?= htmlspecialchars(APP_URL . '/uploads/' . $orgLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo">
        <?php endif; ?>
        <div class="org-name"><?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="org-sub">Annual Giving Statement / Tax Receipt</div>
    </div>
    <div class="receipt-meta">
        <strong>Period: <?= $fromLabel ?> – <?= $toLabel ?></strong>
        Issued: <?= date('F j, Y') ?>
    </div>
</div>

<div class="donor-block">
    <h2>Prepared For</h2>
    <p>
        <strong><?= $donorName ?></strong><br>
        <?php if (!empty($contact['email'])): ?>
        <?= htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8') ?><br>
        <?php endif; ?>
        <?php if (!empty($contact['address'])): ?>
        <?= htmlspecialchars($contact['address'], ENT_QUOTES, 'UTF-8') ?><br>
        <?php endif; ?>
        <?php
        $cityLine = trim(
            ($contact['city'] ?? '') . ', ' .
            ($contact['state'] ?? '') . ' ' .
            ($contact['zip'] ?? ''),
            ', '
        );
        if ($cityLine !== ', '): ?>
        <?= htmlspecialchars($cityLine, ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </p>
</div>

<?php if ($donations): ?>
<table class="detail-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Campaign / Description</th>
            <th>Method</th>
            <th class="amount-col">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($donations as $d): ?>
        <tr>
            <td><?= htmlspecialchars($d['donated_on'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <?= !empty($d['campaign_name']) ? htmlspecialchars($d['campaign_name'], ENT_QUOTES, 'UTF-8') : 'General Contribution' ?>
                <?php if (!empty($d['note'])): ?>
                <br><span style="font-size:.85em;color:#555"><?= htmlspecialchars($d['note'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($d['method'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="amount-col">$<?= number_format((float)$d['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3"><strong>Total Contributions — <?= $fromLabel ?> to <?= $toLabel ?></strong></td>
            <td class="amount-col"><strong>$<?= number_format($total, 2) ?></strong></td>
        </tr>
    </tfoot>
</table>
<?php else: ?>
<p style="margin:1.5rem 0;color:#555;font-style:italic">No donations found for <?= $donorName ?> in this date range.</p>
<?php endif; ?>

<div class="disclosure">
    <strong><?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8') ?></strong> is a nonprofit organization.
    No goods or services were provided in exchange for these contributions unless noted above.
    This statement covers the period <?= $fromLabel ?> through <?= $toLabel ?>.
    Please retain this receipt for your tax records.
    Consult your tax advisor regarding the deductibility of charitable contributions.
</div>

<div class="sig-block">
    <div><div class="sig-line">Authorized Signature</div></div>
    <div><div class="sig-line">Date</div></div>
</div>
