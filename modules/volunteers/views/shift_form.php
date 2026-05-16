<div class="page-header"><h1 class="page-title">New Shift</h1><a href="<?= APP_URL ?>/volunteers/shifts" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card" style="max-width:480px">
<form method="post" action="<?= APP_URL ?>/volunteers/shifts">
    <?= Csrf::field() ?>
    <div class="form-group"><label>Title *</label><input name="title" value="<?= htmlspecialchars($shift['title'] ?? '', ENT_QUOTES,'UTF-8') ?>" required><?php if (!empty($errors['title'])): ?><p class="field-error"><?= htmlspecialchars($errors['title'], ENT_QUOTES,'UTF-8') ?></p><?php endif; ?></div>
    <div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= htmlspecialchars($shift['description'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea></div>
    <div class="form-group"><label>Date *</label><input type="date" name="shift_date" value="<?= htmlspecialchars($shift['shift_date'] ?? date('Y-m-d'), ENT_QUOTES,'UTF-8') ?>" required></div>
    <div class="form-row">
        <div class="form-group"><label>Start Time</label><input type="time" name="start_time" value="<?= htmlspecialchars($shift['start_time'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>End Time</label><input type="time" name="end_time" value="<?= htmlspecialchars($shift['end_time'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-group"><label>Max Volunteers</label><input type="number" name="max_volunteers" value="<?= htmlspecialchars($shift['max_volunteers'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    <button type="submit" class="btn btn-primary">Create Shift</button>
</form>
</div>
