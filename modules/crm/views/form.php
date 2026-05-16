<?php
$isEdit = !empty($contact['id']);
$action = $isEdit ? APP_URL . '/crm/' . $contact['id'] . '/update' : APP_URL . '/crm';
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Edit Contact' : 'New Contact' ?></h1>
    <?php if ($isEdit): ?><a href="<?= APP_URL ?>/crm/<?= $contact['id'] ?>" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
</div>

<div class="card" style="max-width:680px">
<form method="post" action="<?= $action ?>">
    <?= Csrf::field() ?>

    <div class="form-row">
        <div class="form-group">
            <label>First Name *</label>
            <input name="first_name" value="<?= htmlspecialchars($contact['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['first_name'])): ?><p class="field-error"><?= htmlspecialchars($errors['first_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
        <div class="form-group">
            <label>Last Name *</label>
            <input name="last_name" value="<?= htmlspecialchars($contact['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['last_name'])): ?><p class="field-error"><?= htmlspecialchars($errors['last_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['email'])): ?><p class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input name="phone" value="<?= htmlspecialchars($contact['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>

    <div class="form-group">
        <label>Address</label>
        <input name="address" value="<?= htmlspecialchars($contact['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>City</label>
            <input name="city" value="<?= htmlspecialchars($contact['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label>State</label>
            <input name="state" value="<?= htmlspecialchars($contact['state'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>ZIP</label>
            <input name="zip" value="<?= htmlspecialchars($contact['zip'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label>Country</label>
            <input name="country" value="<?= htmlspecialchars($contact['country'] ?? 'US', ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>

    <div class="form-group">
        <label>Tags <small style="font-weight:normal;color:#64748b">(comma-separated)</small></label>
        <input name="tags" value="<?= htmlspecialchars($tagsStr ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="donor, board, newsletter…">
    </div>

    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Contact' ?></button>
</form>
</div>
