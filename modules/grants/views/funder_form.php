<?php $isEdit = !empty($funder['id']); $action = $isEdit ? APP_URL.'/grants/funders/'.$funder['id'].'/update' : APP_URL.'/grants/funders'; ?>
<div class="page-header"><h1 class="page-title"><?= $isEdit ? 'Edit Funder' : 'New Funder' ?></h1><a href="<?= APP_URL ?>/grants/funders" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card" style="max-width:480px">
<form method="post" action="<?= $action ?>">
    <?= Csrf::field() ?>
    <div class="form-group"><label>Name *</label><input name="name" value="<?= htmlspecialchars($funder['name'] ?? '', ENT_QUOTES,'UTF-8') ?>" required><?php if (!empty($errors['name'])): ?><p class="field-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES,'UTF-8') ?></p><?php endif; ?></div>
    <div class="form-row">
        <div class="form-group"><label>Contact Name</label><input name="contact_name" value="<?= htmlspecialchars($funder['contact_name'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($funder['email'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Phone</label><input name="phone" value="<?= htmlspecialchars($funder['phone'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>Website</label><input name="website" value="<?= htmlspecialchars($funder['website'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-group"><label>Notes</label><textarea name="notes" rows="3"><?= htmlspecialchars($funder['notes'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea></div>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Add Funder' ?></button>
</form>
</div>
