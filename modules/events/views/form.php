<?php $isEdit = !empty($event['id']); $action = $isEdit ? APP_URL.'/events/'.$event['id'].'/update' : APP_URL.'/events'; ?>
<div class="page-header"><h1 class="page-title"><?= $isEdit ? 'Edit Event' : 'New Event' ?></h1><a href="<?= APP_URL ?>/events" class="btn btn-secondary btn-sm">Back</a></div>
<div class="card" style="max-width:600px">
<form method="post" action="<?= $action ?>">
    <?= Csrf::field() ?>
    <div class="form-group"><label>Title *</label><input name="title" value="<?= htmlspecialchars($event['title'] ?? '', ENT_QUOTES,'UTF-8') ?>" required><?php if (!empty($errors['title'])): ?><p class="field-error"><?= htmlspecialchars($errors['title'], ENT_QUOTES,'UTF-8') ?></p><?php endif; ?></div>
    <div class="form-group"><label>Description</label><textarea name="description" rows="3"><?= htmlspecialchars($event['description'] ?? '', ENT_QUOTES,'UTF-8') ?></textarea></div>
    <div class="form-row">
        <div class="form-group"><label>Date *</label><input type="date" name="event_date" value="<?= htmlspecialchars($event['event_date'] ?? '', ENT_QUOTES,'UTF-8') ?>" required></div>
        <div class="form-group"><label>Location</label><input name="location" value="<?= htmlspecialchars($event['location'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Start Time</label><input type="time" name="start_time" value="<?= htmlspecialchars($event['start_time'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>End Time</label><input type="time" name="end_time" value="<?= htmlspecialchars($event['end_time'] ?? '', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Capacity</label><input type="number" name="capacity" value="<?= htmlspecialchars($event['capacity'] ?? '', ENT_QUOTES,'UTF-8') ?>" placeholder="Unlimited"></div>
        <div class="form-group"><label>Price</label><input type="number" step="0.01" name="price" value="<?= htmlspecialchars($event['price'] ?? '0', ENT_QUOTES,'UTF-8') ?>"></div>
    </div>
    <div class="form-group"><label><input type="checkbox" name="is_published" value="1" <?= !empty($event['is_published']) ? 'checked' : '' ?>> Published</label></div>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Create Event' ?></button>
</form>
</div>
