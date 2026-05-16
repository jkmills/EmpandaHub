<?php $pageTitle = 'Contacts'; ?>
<div class="page-header">
    <h1 class="page-title">Contacts</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/crm/import" class="btn btn-secondary btn-sm">Import CSV</a>
        <a href="<?= APP_URL ?>/crm/export" class="btn btn-secondary btn-sm">Export CSV</a>
        <a href="<?= APP_URL ?>/crm/duplicates" class="btn btn-secondary btn-sm">Duplicates</a>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <a href="<?= APP_URL ?>/crm/create" class="btn btn-primary btn-sm">+ Add Contact</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-2">
    <form method="get" action="<?= APP_URL ?>/crm" class="d-flex gap-1 align-center">
        <input type="search" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search name, email, phone…" style="max-width:320px">
        <button type="submit" class="btn btn-secondary btn-sm">Search</button>
        <?php if ($q): ?><a href="<?= APP_URL ?>/crm" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
    </form>
</div>

<div class="card">
<div class="table-wrap">
<table>
<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($contacts as $c): ?>
<tr>
    <td><a href="<?= APP_URL ?>/crm/<?= $c['id'] ?>"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'], ENT_QUOTES, 'UTF-8') ?></a></td>
    <td><?= htmlspecialchars($c['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($c['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($c['city'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    <td>
        <a href="<?= APP_URL ?>/crm/<?= $c['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
        <?php if (Auth::hasRole('super_admin','admin')): ?>
        <form method="post" action="<?= APP_URL ?>/crm/<?= $c['id'] ?>/delete" style="display:inline">
            <?= Csrf::field() ?>
            <button class="btn btn-danger btn-sm" data-confirm="Delete this contact?">Delete</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$contacts): ?>
<tr><td colspan="5" style="color:#64748b;text-align:center">No contacts found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
