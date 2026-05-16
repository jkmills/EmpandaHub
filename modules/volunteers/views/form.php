<?php $isEdit = !empty($volunteer['id']); $action = $isEdit ? APP_URL.'/volunteers/'.$volunteer['id'].'/update' : APP_URL.'/volunteers'; ?>
<div class="page-header"><h1 class="page-title"><?= $isEdit ? 'Edit Volunteer' : 'Add Volunteer' ?></h1><a href="<?= APP_URL ?>/volunteers" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card" style="max-width:520px">
<form method="post" action="<?= $action ?>">
    <?= Csrf::field() ?>
    <?php if (!$isEdit): ?>
    <div class="form-group"><label>Contact *</label><select name="contact_id" required><option value="">— Select —</option><?php foreach ($contacts as $c): ?><option value="<?= $c['id'] ?>" <?= ($volunteer['contact_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['first_name'].' '.$c['last_name'], ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?></select><?php if (!empty($errors['contact_id'])): ?><p class="field-error"><?= htmlspecialchars($errors['contact_id'], ENT_QUOTES,'UTF-8') ?></p><?php endif; ?></div>
    <?php endif; ?>
    <div class="form-group"><label>Skills</label><textarea name="skills" rows="3"><?= htmlspecialchars($volunteer['skills'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea></div>
    <div class="form-group"><label>Availability</label><textarea name="availability" rows="3"><?= htmlspecialchars($volunteer['availability'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea></div>
    <div class="form-group"><label>Active</label><select name="is_active"><option value="1" <?= ($volunteer['is_active'] ?? 1) ? 'selected' : '' ?>>Yes</option><option value="0" <?= !($volunteer['is_active'] ?? 1) ? 'selected' : '' ?>>No</option></select></div>
    <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"><?= htmlspecialchars($volunteer['notes'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea></div>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Add Volunteer' ?></button>
</form>
</div>
