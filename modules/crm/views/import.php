<div class="page-header">
    <h1 class="page-title">Import Contacts</h1>
    <a href="<?= APP_URL ?>/crm" class="btn btn-secondary btn-sm">Back</a>
</div>
<div class="card" style="max-width:560px">
    <p class="text-muted">Upload a CSV file. Map the columns below. First row is treated as a header.</p>
    <form method="post" action="<?= APP_URL ?>/crm/import" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <div class="form-group">
            <label>CSV File *</label>
            <input type="file" name="csv" accept=".csv,text/csv" required>
        </div>
        <h4 style="margin:1rem 0 .5rem">Column Mapping (0-based index)</h4>
        <p class="text-muted" style="font-size:.82rem">Enter the column number (starting at 0) for each field, or leave blank to skip.</p>
        <?php
        $fields = ['first_name'=>'First Name','last_name'=>'Last Name','email'=>'Email','phone'=>'Phone','city'=>'City','state'=>'State'];
        foreach ($fields as $key => $label):
        ?>
        <div class="form-group" style="display:grid;grid-template-columns:1fr 80px;gap:.5rem;align-items:center">
            <label style="margin:0"><?= $label ?></label>
            <input type="number" name="col_<?= $key ?>" min="0" placeholder="#" style="text-align:center">
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary mt-1">Import</button>
    </form>
</div>
