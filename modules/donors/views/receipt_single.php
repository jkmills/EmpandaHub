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
        <div class="org-sub">Official Donation Receipt</div>
    </div>
    <div class="receipt-meta">
        <strong>Receipt #<?= str_pad((string)$donation['id'], 6, '0', STR_PAD_LEFT) ?></strong>
        Issued: <?= date('F j, Y') ?><br>
        Fiscal Year: <?= htmlspecialchars($donation['fiscal_year'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>

<div class="donor-block">
    <h2>Donor</h2>
    <?php if ($donation['is_anonymous']): ?>
    <p><em>Anonymous</em></p>
    <?php else: ?>
    <p>
        <strong><?= htmlspecialchars($donation['first_name'] . ' ' . $donation['last_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
        <?php if (!empty($donation['email'])): ?>
        <?= htmlspecialchars($donation['email'], ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </p>
    <?php endif; ?>
</div>

<table class="detail-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Method</th>
            <th class="amount-col">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?= htmlspecialchars($donation['donated_on'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                Charitable Contribution
                <?php if (!empty($donation['campaign_name'])): ?>
                — <?= htmlspecialchars($donation['campaign_name'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                <?php if (!empty($donation['note'])): ?>
                <br><span style="font-size:.85em;color:#555"><?= htmlspecialchars($donation['note'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($donation['method'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="amount-col">$<?= number_format((float)$donation['amount'], 2) ?></td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3"><strong>Total</strong></td>
            <td class="amount-col">$<?= number_format((float)$donation['amount'], 2) ?></td>
        </tr>
    </tfoot>
</table>

<div class="disclosure">
    <strong><?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8') ?></strong> is a nonprofit organization.
    No goods or services were provided in exchange for this contribution.
    Please retain this receipt for your tax records.
    Consult your tax advisor regarding the deductibility of charitable contributions.
</div>

<div class="sig-block">
    <div>
        <div class="sig-line">Authorized Signature</div>
    </div>
    <div>
        <div class="sig-line">Date</div>
    </div>
</div>
