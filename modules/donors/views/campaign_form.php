<?php $isEdit = !empty($campaign['id']); $action = $isEdit ? APP_URL.'/donors/campaigns/'.$campaign['id'].'/update' : APP_URL.'/donors/campaigns'; ?>
<div class="page-header"><h1 class="page-title"><?= $isEdit ? 'Edit Campaign' : 'New Campaign' ?></h1><a href="<?= APP_URL ?>/donors/campaigns" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card" style="max-width:480px">
<form method="post" action="<?= $action ?>">
    <?= Csrf::field() ?>
    <div class="form-group"><label>Name *</label><input name="name" value="<?= htmlspecialchars($campaign['name'] ?? '', ENT_QUOTES,'UTF-8') ?>" required><?php if (!empty($errors['name'])): ?><p class="field-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES,'UTF-8') ?></p><?php endif; ?></div>
    <div class="form-group"><label>Description</label><textarea name="description" rows="3"><?= htmlspecialchars($campaign['description'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea></div>
    <div class="form-row">
        <div class="form-group"><label>Goal Amount</label><input type="number" step="0.01" name="goal_amount" value="<?= htmlspecialchars($campaign['goal_amount'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>Active</label><select name="is_active"><option value="1" <?= ($campaign['is_active'] ?? 1) ? 'selected' : '' ?>>Yes</option><option value="0" <?= !($campaign['is_active'] ?? 1) ? 'selected' : '' ?>>No</option></select></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?= htmlspecialchars($campaign['start_date'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>End Date</label><input type="date" name="end_date" value="<?= htmlspecialchars($campaign['end_date'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Create Campaign' ?></button>
</form>
</div>
