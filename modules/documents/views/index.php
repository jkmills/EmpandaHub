<?php
$mimeLabels = ['application/pdf' => 'PDF', 'image/' => 'Image', 'text/' => 'Text', 'application/vnd' => 'Office', 'video/' => 'Video', 'audio/' => 'Audio'];
function mimeLabel(string $mime): string {
    $map = ['application/pdf'=>'PDF','image/'=>'Image','text/'=>'Text','application/vnd'=>'Office','video/'=>'Video','audio/'=>'Audio'];
    foreach ($map as $prefix => $label) { if (str_starts_with($mime, $prefix)) return $label; }
    return 'File';
}
function fmtSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes/1048576,1).' MB';
    if ($bytes >= 1024)    return round($bytes/1024,0).' KB';
    return $bytes.' B';
}
$canManage = Auth::hasRole('super_admin','admin','staff');
?>
<div class="page-header">
    <h1 class="page-title">Document Library</h1>
    <?php if ($canManage): ?>
    <a href="<?= APP_URL ?>/documents/create" class="btn btn-primary btn-sm">+ Upload</a>
    <?php endif; ?>
</div>

<form method="get" action="<?= APP_URL ?>/documents" class="d-flex gap-1 mb-2" style="flex-wrap:wrap;align-items:flex-end">
    <div>
        <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '', ENT_QUOTES,'UTF-8') ?>"
               placeholder="Search title, description, tags…" class="form-control" style="width:220px">
    </div>
    <?php if ($categories): ?>
    <div>
        <select name="category_id" class="form-control">
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name'], ENT_QUOTES,'UTF-8') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <button class="btn btn-secondary btn-sm">Filter</button>
    <?php if (array_filter($filters)): ?>
    <a href="<?= APP_URL ?>/documents" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
</form>

<?php if ($docs): ?>
<div class="table-wrap">
<table>
    <thead><tr>
        <th>Title</th>
        <th>Category</th>
        <th>Type</th>
        <th>Size</th>
        <th>Visibility</th>
        <th>Uploaded By</th>
        <th>Date</th>
        <th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($docs as $d): ?>
    <tr>
        <td>
            <a href="<?= APP_URL ?>/documents/<?= $d['id'] ?>" style="font-weight:600"><?= htmlspecialchars($d['title'], ENT_QUOTES,'UTF-8') ?></a>
            <?php if ($d['description']): ?>
            <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars(mb_strimwidth($d['description'],0,80,'…'), ENT_QUOTES,'UTF-8') ?></div>
            <?php endif; ?>
        </td>
        <td class="text-muted" style="font-size:.82rem"><?= $d['category_name'] ? htmlspecialchars($d['category_name'],ENT_QUOTES,'UTF-8') : '—' ?></td>
        <td><span class="badge badge-muted" style="font-size:.7rem"><?= mimeLabel($d['mime_type']) ?></span></td>
        <td class="text-muted" style="font-size:.82rem"><?= fmtSize((int)$d['file_size']) ?></td>
        <td>
            <?php
            $visColors = ['all_staff'=>'badge-success','staff_only'=>'badge-info','admin_only'=>'badge-warning','super_admin_only'=>'badge-danger'];
            $visLabels = ['all_staff'=>'All Staff','staff_only'=>'Staff','admin_only'=>'Admin','super_admin_only'=>'Super Admin'];
            ?>
            <span class="badge <?= $visColors[$d['visibility']] ?? 'badge-muted' ?>" style="font-size:.7rem">
                <?= $visLabels[$d['visibility']] ?? $d['visibility'] ?>
            </span>
        </td>
        <td class="text-muted" style="font-size:.82rem"><?= htmlspecialchars($d['uploader_name'] ?? '—', ENT_QUOTES,'UTF-8') ?></td>
        <td class="text-muted" style="font-size:.82rem;white-space:nowrap"><?= fmt_date($d['created_at']) ?></td>
        <td style="white-space:nowrap">
            <a href="<?= APP_URL ?>/documents/<?= $d['id'] ?>/download" class="btn btn-ghost btn-sm">Download</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php else: ?>
<div class="empty-state" style="padding:3rem 0;text-align:center">
    <p class="text-muted">No documents found.</p>
    <?php if ($canManage): ?>
    <a href="<?= APP_URL ?>/documents/create" class="btn btn-primary btn-sm">Upload your first document</a>
    <?php endif; ?>
</div>
<?php endif; ?>
