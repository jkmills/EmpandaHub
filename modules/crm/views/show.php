<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="d-flex gap-1">
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <a href="<?= APP_URL ?>/crm/<?= $contact['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/crm" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

<div class="card">
    <h3 style="margin-top:0">Contact Info</h3>
    <table style="width:100%">
        <tr><td class="text-muted" style="width:35%">Email</td><td><?= htmlspecialchars($contact['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Phone</td><td><?= htmlspecialchars($contact['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Address</td><td><?= htmlspecialchars(trim(($contact['address'] ?? '') . ' ' . ($contact['city'] ?? '') . ' ' . ($contact['state'] ?? '') . ' ' . ($contact['zip'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Country</td><td><?= htmlspecialchars($contact['country'] ?? '', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Added</td><td><?= htmlspecialchars($contact['created_at'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    </table>
    <?php if ($contact['tags']): ?>
    <div class="mt-2">
        <?php foreach ($contact['tags'] as $tag): ?>
        <span class="badge badge-info"><?= htmlspecialchars($tag['tag'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-top:0">Notes</h3>
    <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
    <form method="post" action="<?= APP_URL ?>/crm/<?= $contact['id'] ?>/note" class="mb-2">
        <?= Csrf::field() ?>
        <textarea name="body" rows="3" maxlength="2000" placeholder="Add a note…" style="width:100%;margin-bottom:.4rem"></textarea>
        <button type="submit" class="btn btn-primary btn-sm">Add Note</button>
    </form>
    <?php endif; ?>
    <?php foreach ($contact['notes'] as $note): ?>
    <div style="border-top:1px solid #e2e8f0;padding:.6rem 0">
        <p style="margin:0"><?= nl2br(htmlspecialchars($note['body'], ENT_QUOTES, 'UTF-8')) ?></p>
        <p class="text-muted" style="font-size:.78rem;margin:.2rem 0 0"><?= htmlspecialchars($note['author'] ?? 'Staff', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($note['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <?php endforeach; ?>
    <?php if (!$contact['notes']): ?><p class="text-muted">No notes yet.</p><?php endif; ?>
</div>

</div>
