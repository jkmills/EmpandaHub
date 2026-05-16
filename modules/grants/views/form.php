<?php $isEdit = !empty($grant['id']); $action = $isEdit ? APP_URL.'/grants/'.$grant['id'].'/update' : APP_URL.'/grants'; ?>
<div class="page-header"><h1 class="page-title"><?= $isEdit ? 'Edit Grant' : 'New Grant' ?></h1><a href="<?= APP_URL ?>/grants" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card" style="max-width:600px">
<form method="post" action="<?= $action ?>">
    <?= Csrf::field() ?>
    <div class="form-group"><label>Funder *</label><select name="funder_id" required><option value="">— Select —</option><?php foreach ($funders as $f): ?><option value="<?= $f['id'] ?>" <?= ($grant['funder_id'] ?? '') == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name'], ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?></select><?php if (!empty($errors['funder_id'])): ?><p class="field-error"><?= htmlspecialchars($errors['funder_id'], ENT_QUOTES,'UTF-8') ?></p><?php endif; ?></div>
    <div class="form-group"><label>Title *</label><input name="title" value="<?= htmlspecialchars($grant['title'] ?? '', ENT_QUOTES,'UTF-8') ?>" required><?php if (!empty($errors['title'])): ?><p class="field-error"><?= htmlspecialchars($errors['title'], ENT_QUOTES,'UTF-8') ?></p><?php endif; ?></div>
    <div class="form-group"><label>Description</label><textarea name="description" rows="3"><?= htmlspecialchars($grant['description'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea></div>
    <div class="form-row">
        <div class="form-group"><label>Status</label><select name="status"><?php foreach (['prospect','drafting','submitted','awarded','declined'] as $s): ?><option value="<?= $s ?>" <?= ($grant['status'] ?? 'prospect') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Amount Requested</label><input type="number" step="0.01" name="amount_requested" value="<?= htmlspecialchars($grant['amount_requested'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Deadline</label><input type="date" name="deadline_date" value="<?= htmlspecialchars($grant['deadline_date'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>Submitted</label><input type="date" name="submitted_date" value="<?= htmlspecialchars($grant['submitted_date'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Period Start</label><input type="date" name="period_start" value="<?= htmlspecialchars($grant['period_start'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>Period End</label><input type="date" name="period_end" value="<?= htmlspecialchars($grant['period_end'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"><?= htmlspecialchars($grant['notes'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea></div>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Create Grant' ?></button>
</form>
</div>
