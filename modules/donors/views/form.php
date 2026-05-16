<?php $isEdit = !empty($donation['id']); $action = $isEdit ? APP_URL.'/donors/'.$donation['id'].'/update' : APP_URL.'/donors'; ?>
<div class="page-header"><h1 class="page-title"><?= $isEdit ? 'Edit Donation' : 'New Donation' ?></h1></div>
<div class="card" style="max-width:560px">
<form method="post" action="<?= $action ?>">
    <?= Csrf::field() ?>
    <div class="form-group">
        <label>Donor</label>
        <select name="contact_id"><option value="">Anonymous</option><?php foreach ($contacts as $c): ?><option value="<?= $c['id'] ?>" <?= ($donation['contact_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['first_name'].' '.$c['last_name'], ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?></select>
    </div>
    <div class="form-group">
        <label>Campaign</label>
        <select name="campaign_id"><option value="">None</option><?php foreach ($campaigns as $ca): ?><option value="<?= $ca['id'] ?>" <?= ($donation['campaign_id'] ?? '') == $ca['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ca['name'], ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?></select>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Amount *</label><input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($donation['amount'] ?? '', ENT_QUOTES,'UTF-8') ?>" required><?php if (!empty($errors['amount'])): ?><p class="field-error"><?= htmlspecialchars($errors['amount'], ENT_QUOTES,'UTF-8') ?></p><?php endif; ?></div>
        <div class="form-group"><label>Date *</label><input type="date" name="donated_on" value="<?= htmlspecialchars($donation['donated_on'] ?? date('Y-m-d'), ENT_QUOTES,'UTF-8') ?>" required></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Method</label><input name="method" value="<?= htmlspecialchars($donation['method'] ?? '', ENT_QUOTES,'UTF-8') ?>" placeholder="Cash, Check, Card…"></div>
        <div class="form-group"><label>Note</label><input name="note" value="<?= htmlspecialchars($donation['note'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label><input type="checkbox" name="is_anonymous" value="1" <?= !empty($donation['is_anonymous']) ? 'checked' : '' ?>> Anonymous</label></div>
        <div class="form-group"><label><input type="checkbox" name="is_recurring" value="1" <?= !empty($donation['is_recurring']) ? 'checked' : '' ?>> Recurring</label></div>
    </div>
    <div class="form-group">
        <label>Recur Interval</label>
        <select name="recur_interval"><option value="">N/A</option><?php foreach (['monthly','quarterly','annual'] as $ri): ?><option value="<?= $ri ?>" <?= ($donation['recur_interval'] ?? '') === $ri ? 'selected' : '' ?>><?= ucfirst($ri) ?></option><?php endforeach; ?></select>
    </div>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Record Donation' ?></button>
</form>
</div>
