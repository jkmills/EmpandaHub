<div class="page-header">
    <h1 class="page-title">Settings</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/settings/export" class="btn btn-secondary btn-sm">Export / Backup</a>
        <a href="<?= APP_URL ?>/settings/import" class="btn btn-secondary btn-sm">Import / Restore</a>
    </div>
</div>

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
        <div style="border-top:1px solid var(--border);margin:.75rem 0 1rem;padding-top:.875rem">
            <p style="font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-500);margin:0 0 .875rem">Appearance</p>

            <div class="form-group">
                <label>Brand Color</label>
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                    <input type="color" name="primary_color" id="primaryColorPicker"
                           value="<?= htmlspecialchars($org['primary_color'] ?? '#2563eb', ENT_QUOTES,'UTF-8') ?>"
                           style="width:44px;height:36px;padding:2px;border-radius:var(--radius-sm);border:1.5px solid var(--gray-300);cursor:pointer;flex-shrink:0">
                    <div style="display:flex;align-items:center;gap:.625rem;padding:.5rem .75rem;background:var(--gray-50);border:1px solid var(--border);border-radius:var(--radius-sm)">
                        <button class="btn btn-primary btn-sm" type="button" style="pointer-events:none">Button</button>
                        <a href="#" onclick="return false" style="font-size:.8125rem">Link text</a>
                        <span style="font-size:.7rem;font-weight:700;padding:.2rem .5rem;border-radius:9999px;background:var(--brand-50);color:var(--brand)">Badge</span>
                        <span style="display:inline-block;width:3px;height:18px;background:var(--brand);border-radius:2px"></span>
                    </div>
                </div>
                <p class="form-hint">Updates live — save to apply permanently.</p>
            </div>

            <div class="form-group" style="margin-bottom:0">
                <label style="margin-bottom:.6rem">Sidebar Style</label>
                <div class="sidebar-style-picker">
                    <?php foreach (['dark' => 'Dark', 'light' => 'Light', 'brand' => 'Brand'] as $val => $lbl): ?>
                    <label class="sidebar-style-opt <?= ($orgSidebarStyle ?? 'dark') === $val ? 'is-selected' : '' ?>">
                        <input type="radio" name="sidebar_style" value="<?= $val ?>" <?= ($orgSidebarStyle ?? 'dark') === $val ? 'checked' : '' ?>>
                        <div class="ssp-thumb ssp-thumb--<?= $val ?>">
                            <div class="ssp-rail"></div>
                            <div class="ssp-body">
                                <div class="ssp-line"></div>
                                <div class="ssp-line ssp-line--short"></div>
                                <div class="ssp-line"></div>
                                <div class="ssp-line ssp-line--short"></div>
                            </div>
                        </div>
                        <span class="ssp-label"><?= $lbl ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="form-group"><label>Timezone</label><input name="timezone" value="<?= htmlspecialchars($org['timezone'] ?? 'America/New_York', ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label>Fiscal Year Start Month</label><input type="number" name="fiscal_year_start" min="1" max="12" value="<?= $org['fiscal_year_start'] ?? 1 ?>"></div>
        <h4 style="margin:.8rem 0 .4rem">Module Visibility</h4>
        <?php $allMods = ['crm'=>'CRM','membership'=>'Membership','donors'=>'Donors','volunteers'=>'Volunteers','events'=>'Events','grants'=>'Grants','finance'=>'Finance']; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.25rem .5rem;margin-bottom:.5rem">
        <?php foreach ($allMods as $key => $label): ?>
        <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer"><input type="checkbox" name="mod_<?= $key ?>" value="1" <?= !isset($orgModules[$key]) || $orgModules[$key] ? 'checked' : '' ?> style="margin:0;width:15px;height:15px;flex-shrink:0"> <?= $label ?></label>
        <?php endforeach; ?>
        </div>
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
