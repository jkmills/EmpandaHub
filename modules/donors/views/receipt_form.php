<div class="page-header">
    <h1 class="page-title">Generate Summary Receipt</h1>
    <a href="<?= APP_URL ?>/donors" class="btn btn-secondary btn-sm">Back</a>
</div>

<div class="card" style="max-width:480px">
    <p class="text-muted" style="margin-top:0;font-size:.9rem">
        Generates a printable tax receipt summarizing all donations from one donor within a date range.
        Useful for year-end giving statements.
    </p>
    <form method="get" action="<?= APP_URL ?>/donors/receipt/print">
        <div class="form-group">
            <label>Donor *</label>
            <select name="contact_id" required>
                <option value="">— select donor —</option>
                <?php foreach ($contacts as $c): ?>
                <option value="<?= $c['id'] ?>">
                    <?= htmlspecialchars($c['last_name'] . ', ' . $c['first_name'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($c['email'])): ?>
                     — <?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
            <div class="form-group">
                <label>From *</label>
                <input type="date" name="from" value="<?= date('Y') ?>-01-01" required>
            </div>
            <div class="form-group">
                <label>To *</label>
                <input type="date" name="to" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Generate Receipt</button>
    </form>
</div>
