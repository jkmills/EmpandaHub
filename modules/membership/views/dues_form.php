<?php $action = APP_URL . '/membership/' . $membership['id'] . '/dues/' . $due['id'] . '/update'; ?>
<div class="page-header">
    <h1 class="page-title">Edit Payment — <?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="<?= APP_URL ?>/membership/<?= $membership['id'] ?>" class="btn btn-secondary btn-sm">Back</a>
</div>

<div class="card" style="max-width:520px">
    <form method="post" action="<?= $action ?>">
        <?= Csrf::field() ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
            <div class="form-group">
                <label>Amount *</label>
                <input type="number" step="0.01" min="0.01" name="amount"
                       value="<?= htmlspecialchars($due['amount'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
                <label>Date Paid *</label>
                <input type="date" name="paid_on"
                       value="<?= htmlspecialchars($due['paid_on'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
                <label>Due Date (period)</label>
                <input type="date" name="due_date"
                       value="<?= htmlspecialchars($due['due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Method</label>
                <select name="method">
                    <option value="">— select —</option>
                    <?php foreach (['Check','Cash','Credit Card','ACH / Bank Transfer','Online','Other'] as $m): ?>
                    <option <?= ($due['method'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Reference / Check #</label>
                <input name="reference" value="<?= htmlspecialchars($due['reference'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Note</label>
                <input name="note" value="<?= htmlspecialchars($due['note'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="d-flex gap-1 mt-1">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= APP_URL ?>/membership/<?= $membership['id'] ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
