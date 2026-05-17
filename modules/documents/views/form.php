<?php
$isEdit = !empty($doc['id']);
$visibilities = DocumentModel::allowedVisibilities();
$visLabels = ['all_staff'=>'All Staff','staff_only'=>'Staff Only','admin_only'=>'Admin Only','super_admin_only'=>'Super Admin Only'];
?>
<div class="page-header">
    <h1 class="page-title">Upload Document</h1>
    <a href="<?= APP_URL ?>/documents" class="btn btn-ghost btn-sm">&larr; Back</a>
</div>

<div class="card" style="max-width:640px">
<form method="post" action="<?= APP_URL ?>/documents" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="form-group">
        <label class="form-label">File <span style="color:#dc2626">*</span></label>
        <input type="file" name="file" class="form-control<?= isset($errors['file']) ? ' is-invalid' : '' ?>" required>
        <?php if (isset($errors['file'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['file'],ENT_QUOTES,'UTF-8') ?></div>
        <?php endif; ?>
        <div class="form-hint">Executable file types (.php, .exe, .sh, etc.) are not allowed.</div>
    </div>

    <div class="form-group">
        <label class="form-label">Title <span style="color:#dc2626">*</span></label>
        <input type="text" name="title" value="<?= htmlspecialchars($doc['title'] ?? '', ENT_QUOTES,'UTF-8') ?>"
               class="form-control<?= isset($errors['title']) ? ' is-invalid' : '' ?>" required>
        <?php if (isset($errors['title'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['title'],ENT_QUOTES,'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($doc['description'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea>
    </div>

    <?php if ($categories): ?>
    <div class="form-group">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-control">
            <option value="">— None —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($doc['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name'], ENT_QUOTES,'UTF-8') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <label class="form-label">Tags <span class="text-muted" style="font-weight:400">(comma-separated)</span></label>
        <input type="text" name="tags" value="<?= htmlspecialchars($doc['tags'] ?? '', ENT_QUOTES,'UTF-8') ?>"
               class="form-control" placeholder="policy, finance, 2024">
    </div>

    <div class="form-group">
        <label class="form-label">Visibility <span style="color:#dc2626">*</span></label>
        <select name="visibility" class="form-control<?= isset($errors['visibility']) ? ' is-invalid' : '' ?>">
            <?php foreach ($visibilities as $v): ?>
            <option value="<?= $v ?>" <?= ($doc['visibility'] ?? 'all_staff') === $v ? 'selected' : '' ?>>
                <?= $visLabels[$v] ?? $v ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['visibility'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['visibility'],ENT_QUOTES,'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-1" style="margin-top:1.5rem">
        <button type="submit" class="btn btn-primary">Upload Document</button>
        <a href="<?= APP_URL ?>/documents" class="btn btn-ghost">Cancel</a>
    </div>
</form>
</div>
