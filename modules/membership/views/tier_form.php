<?php $isEdit = !empty($tier['id']); $action = $isEdit ? APP_URL.'/membership/tiers/'.$tier['id'].'/update' : APP_URL.'/membership/tiers'; ?>
<div class="page-header"><h1 class="page-title"><?= $isEdit ? 'Edit Tier' : 'New Tier' ?></h1><a href="<?= APP_URL ?>/membership/tiers" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card" style="max-width:480px">
<form method="post" action="<?= $action ?>">
    <?= Csrf::field() ?>
    <div class="form-group"><label>Name *</label><input name="name" value="<?= htmlspecialchars($tier['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required><?php if (!empty($errors['name'])): ?><p class="field-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?></div>
    <div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= htmlspecialchars($tier['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
    <div class="form-group"><label>Parent Tier</label><select name="parent_tier_id"><option value="">None</option><?php foreach ($tiers as $t): if ($t['id'] == ($tier['id'] ?? 0)) continue; ?><option value="<?= $t['id'] ?>" <?= ($tier['parent_tier_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
    <div class="form-row">
        <div class="form-group"><label>Billing Cycle *</label><select name="billing_cycle"><?php foreach (['monthly','quarterly','annual','lifetime'] as $bc): ?><option value="<?= $bc ?>" <?= ($tier['billing_cycle'] ?? 'annual') === $bc ? 'selected' : '' ?>><?= ucfirst($bc) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($tier['amount'] ?? '0', ENT_QUOTES, 'UTF-8') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Grace Period (days)</label><input type="number" name="grace_period_days" value="<?= htmlspecialchars($tier['grace_period_days'] ?? '30', ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="<?= htmlspecialchars($tier['sort_order'] ?? '0', ENT_QUOTES, 'UTF-8') ?>"></div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Create Tier' ?></button>
</form>
</div>
