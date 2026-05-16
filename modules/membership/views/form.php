<?php $isEdit = !empty($membership['id']); $action = $isEdit ? APP_URL . '/membership/' . $membership['id'] . '/update' : APP_URL . '/membership'; ?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Edit Membership' : 'New Membership' ?></h1>
</div>
<div class="card" style="max-width:520px">
<form method="post" action="<?= $action ?>">
    <?= Csrf::field() ?>
    <div class="form-group">
        <label>Contact *</label>
        <select name="contact_id" required>
            <option value="">— Select —</option>
            <?php foreach ($contacts as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($membership['contact_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['contact_id'])): ?><p class="field-error"><?= htmlspecialchars($errors['contact_id'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    </div>
    <div class="form-group">
        <label>Tier *</label>
        <select name="tier_id" required>
            <option value="">— Select —</option>
            <?php foreach ($tiers as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($membership['tier_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($t['billing_cycle'], ENT_QUOTES, 'UTF-8') ?> — $<?= number_format((float)$t['amount'], 2) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['tier_id'])): ?><p class="field-error"><?= htmlspecialchars($errors['tier_id'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    </div>
    <div class="form-group">
        <label>Start Date *</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($membership['start_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
        <?php if (!empty($errors['start_date'])): ?><p class="field-error"><?= htmlspecialchars($errors['start_date'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    </div>
    <?php if ($isEdit): ?>
    <div class="form-group">
        <label>Status</label>
        <select name="status">
            <?php foreach (['active','grace','expired','cancelled','lifetime'] as $s): ?>
            <option value="<?= $s ?>" <?= ($membership['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" rows="3"><?= htmlspecialchars($membership['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Create' ?></button>
</form>
</div>
