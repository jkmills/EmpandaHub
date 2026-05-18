<?php
$visLabels = ['all_staff'=>'All Staff','staff_only'=>'Staff Only','admin_only'=>'Admin Only','super_admin_only'=>'Super Admin Only'];
$visColors = ['all_staff'=>'badge-success','staff_only'=>'badge-info','admin_only'=>'badge-warning','super_admin_only'=>'badge-danger'];

function fmtBytes(int $b): string {
    if ($b >= 1048576) return round($b/1048576,1).' MB';
    if ($b >= 1024)    return round($b/1024,0).' KB';
    return $b.' B';
}
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($doc['title'], ENT_QUOTES,'UTF-8') ?></h1>
        <div class="text-muted" style="font-size:.82rem;margin-top:.2rem">
            <span class="badge <?= $visColors[$doc['visibility']] ?? 'badge-muted' ?>" style="font-size:.7rem">
                <?= $visLabels[$doc['visibility']] ?? $doc['visibility'] ?>
            </span>
            <?php if ($doc['category_name']): ?>
            &middot; <?= htmlspecialchars($doc['category_name'],ENT_QUOTES,'UTF-8') ?>
            <?php endif; ?>
            &middot; <?= fmtBytes((int)$doc['file_size']) ?>
            &middot; Uploaded by <?= htmlspecialchars($doc['uploader_name'] ?? 'Unknown',ENT_QUOTES,'UTF-8') ?>
            on <?= fmt_date($doc['created_at']) ?>
        </div>
    </div>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/documents/<?= $doc['id'] ?>/download" class="btn btn-primary btn-sm">Download</a>
        <a href="<?= APP_URL ?>/documents" class="btn btn-ghost btn-sm">&larr; Library</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;align-items:start">

<div>

<?php if (DocumentController::isPreviewable($doc['mime_type'])): ?>
<div class="card" style="padding:0;overflow:hidden">
    <?php if (str_starts_with($doc['mime_type'], 'image/')): ?>
    <img src="<?= APP_URL ?>/documents/<?= $doc['id'] ?>/preview"
         alt="<?= htmlspecialchars($doc['title'],ENT_QUOTES,'UTF-8') ?>"
         style="display:block;max-width:100%;height:auto">
    <?php else: ?>
    <iframe src="<?= APP_URL ?>/documents/<?= $doc['id'] ?>/preview"
            style="display:block;width:100%;height:72vh;border:0"
            title="<?= htmlspecialchars($doc['title'],ENT_QUOTES,'UTF-8') ?>"></iframe>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($doc['description']): ?>
<div class="card" style="margin-top:1rem">
    <div class="card-header"><h3>Description</h3></div>
    <p style="margin:0;white-space:pre-wrap"><?= htmlspecialchars($doc['description'],ENT_QUOTES,'UTF-8') ?></p>
</div>
<?php endif; ?>

<?php if ($doc['tags']): ?>
<div style="margin-top:1rem">
    <?php foreach (array_map('trim', explode(',', $doc['tags'])) as $tag): if (!$tag) continue; ?>
    <span class="badge badge-muted" style="margin-right:.25rem"><?= htmlspecialchars($tag,ENT_QUOTES,'UTF-8') ?></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($canManage): ?>
<div class="card" style="margin-top:1rem">
    <div class="card-header"><h3>Upload New Version</h3></div>
    <form method="post" action="<?= APP_URL ?>/documents/<?= $doc['id'] ?>/version" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="d-flex gap-1" style="align-items:flex-end">
            <div style="flex:1">
                <input type="file" name="file" class="form-control" required>
            </div>
            <button class="btn btn-secondary btn-sm">Upload Version</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($versions): ?>
<div class="card" style="margin-top:1rem">
    <div class="card-header"><h3>Version History</h3></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Version</th><th>Filename</th><th>Size</th><th>Uploaded By</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($versions as $v): ?>
        <tr>
            <td style="font-weight:600">v<?= $v['version_number'] ?></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($v['filename'],ENT_QUOTES,'UTF-8') ?></td>
            <td class="text-muted" style="font-size:.82rem"><?= fmtBytes((int)$v['file_size']) ?></td>
            <td class="text-muted" style="font-size:.82rem"><?= htmlspecialchars($v['uploader_name'] ?? '—',ENT_QUOTES,'UTF-8') ?></td>
            <td class="text-muted" style="font-size:.82rem;white-space:nowrap"><?= fmt_date($v['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>

</div><!-- /left col -->

<div>

<?php if ($canManage): ?>
<div class="card">
    <div class="card-header">
        <h3>Share Links</h3>
    </div>

    <form method="post" action="<?= APP_URL ?>/documents/<?= $doc['id'] ?>/share" style="margin-bottom:1rem">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:.5rem">
            <label class="form-label" style="font-size:.82rem">Expires (optional)</label>
            <input type="datetime-local" name="expires_at" class="form-control" style="font-size:.82rem">
        </div>
        <button class="btn btn-secondary btn-sm">Create Share Link</button>
    </form>

    <?php if ($shareLinks): ?>
    <div class="table-wrap"><table style="font-size:.8rem">
        <thead><tr><th>Link</th><th>Expires</th><th>Accesses</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($shareLinks as $sl): ?>
        <tr>
            <td>
                <input type="text" readonly value="<?= APP_URL ?>/docs/<?= htmlspecialchars($sl['token'],ENT_QUOTES,'UTF-8') ?>"
                       class="form-control" style="font-size:.75rem;width:180px"
                       onclick="this.select();document.execCommand('copy');this.blur();this.value='Copied!';"
                       onfocus="this.select()">
            </td>
            <td class="text-muted"><?= $sl['expires_at'] ? fmt_date($sl['expires_at']) : 'Never' ?></td>
            <td class="text-muted"><?= (int)$sl['access_count'] ?></td>
            <td>
                <?php if ($sl['is_active']): ?>
                <span class="badge badge-success" style="font-size:.7rem">Active</span>
                <?php else: ?>
                <span class="badge badge-danger" style="font-size:.7rem">Revoked</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($sl['is_active']): ?>
                <form method="post" action="<?= APP_URL ?>/documents/<?= $doc['id'] ?>/share/<?= $sl['id'] ?>/revoke"
                      onsubmit="return confirm('Revoke this link?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-ghost btn-sm" style="color:#dc2626">Revoke</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php else: ?>
    <p class="text-muted" style="font-size:.82rem;margin:0">No share links yet.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($canDelete): ?>
<div class="card" style="margin-top:1rem;border-color:#fca5a5">
    <div class="card-header"><h3 style="color:#dc2626">Delete Document</h3></div>
    <p class="text-muted" style="font-size:.82rem">This permanently removes the document and its file. Share links will stop working.</p>
    <form method="post" action="<?= APP_URL ?>/documents/<?= $doc['id'] ?>/delete"
          onsubmit="return confirm('Delete this document permanently?')">
        <?= csrf_field() ?>
        <button class="btn btn-sm" style="background:#dc2626;color:#fff;border-color:#dc2626">Delete Document</button>
    </form>
</div>
<?php endif; ?>

</div><!-- /right col -->

</div>
