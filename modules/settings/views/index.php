<div class="page-header"><h1 class="page-title">Settings</h1></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

<div class="card">
    <h3 style="margin-top:0">Organization Profile</h3>
    <form method="post" action="<?= APP_URL ?>/settings/org" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <div class="form-group"><label>Org Name *</label><input name="name" value="<?= htmlspecialchars($org['name'] ?? '', ENT_QUOTES,'UTF-8') ?>" required></div>
        <div class="form-group">
            <label>Logo</label>
            <?php if (!empty($org['logo'])): ?><img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($org['logo'], ENT_QUOTES,'UTF-8') ?>" style="height:40px;display:block;margin-bottom:.4rem"><?php endif; ?>
            <input type="file" name="logo" accept="image/*">
        </div>
        <div class="form-group"><label>Brand Color</label><input type="color" name="primary_color" value="<?= htmlspecialchars($org['primary_color'] ?? '#2563eb', ENT_QUOTES,'UTF-8') ?>" style="width:60px;height:38px;padding:2px"></div>
        <div class="form-group"><label>Timezone</label><input name="timezone" value="<?= htmlspecialchars($org['timezone'] ?? 'America/New_York', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>Fiscal Year Start Month</label><input type="number" name="fiscal_year_start" min="1" max="12" value="<?= $org['fiscal_year_start'] ?? 1 ?>"></div>
        <h4 style="margin:.8rem 0 .4rem">Module Visibility</h4>
        <?php $allMods = ['crm'=>'CRM','membership'=>'Membership','donors'=>'Donors','volunteers'=>'Volunteers','events'=>'Events','grants'=>'Grants','finance'=>'Finance']; ?>
        <?php foreach ($allMods as $key => $label): ?>
        <label style="display:block;margin:.2rem 0"><input type="checkbox" name="mod_<?= $key ?>" value="1" <?= !isset($orgModules[$key]) || $orgModules[$key] ? 'checked' : '' ?>> <?= $label ?></label>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary mt-2">Save</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0">Users</h3>
    <div class="table-wrap mb-2"><table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= htmlspecialchars($u['name'], ENT_QUOTES,'UTF-8') ?></td>
        <td><?= htmlspecialchars($u['email'], ENT_QUOTES,'UTF-8') ?></td>
        <td>
            <?php if (Auth::hasRole('super_admin')): ?>
            <form method="post" action="<?= APP_URL ?>/settings/users/<?= $u['id'] ?>/role" class="d-flex gap-1 align-center">
                <?= Csrf::field() ?>
                <select name="role" style="padding:.2rem .4rem;font-size:.8rem"><?php foreach (['super_admin','admin','staff','volunteer','readonly'] as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
                <button class="btn btn-secondary btn-sm">Set</button>
            </form>
            <?php else: ?>
            <?= htmlspecialchars($u['role'], ENT_QUOTES,'UTF-8') ?>
            <?php endif; ?>
        </td>
        <td><span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= $u['is_active'] ? 'Yes' : 'No' ?></span></td>
        <td><?php if ($u['is_active'] && $u['id'] != Auth::user()['id']): ?>
            <form method="post" action="<?= APP_URL ?>/settings/users/<?= $u['id'] ?>/deactivate"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" data-confirm="Deactivate this user?">Deactivate</button></form>
        <?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>

    <h4 style="margin:.5rem 0">Add User</h4>
    <form method="post" action="<?= APP_URL ?>/settings/users/invite">
        <?= Csrf::field() ?>
        <div class="form-group"><label>Name *</label><input name="name" required></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Role</label><select name="role"><?php foreach (['admin','staff','volunteer','readonly'] as $r): ?><option value="<?= $r ?>"><?= $r ?></option><?php endforeach; ?></select></div>
        <button type="submit" class="btn btn-primary btn-sm">Create User</button>
    </form>
</div>

</div>
