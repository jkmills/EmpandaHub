<!DOCTYPE html>
<html lang="en" style="--brand: <?= htmlspecialchars($orgColor ?? '#2563eb', ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? 'Receipt', ENT_QUOTES, 'UTF-8') ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Georgia, 'Times New Roman', serif; font-size: 13pt; color: #111; background: #fff; padding: 1in; max-width: 8.5in; margin: 0 auto; }
  .no-print { margin-bottom: 1rem; display: flex; gap: .5rem; }
  .btn-print { background: var(--brand, #2563eb); color: #fff; border: none; padding: .4rem 1rem; border-radius: .25rem; cursor: pointer; font-size: .9rem; }
  .btn-close  { background: #e5e7eb; color: #111; border: none; padding: .4rem 1rem; border-radius: .25rem; cursor: pointer; font-size: .9rem; text-decoration: none; display: inline-block; }
  .receipt-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--brand, #2563eb); padding-bottom: .6rem; margin-bottom: 1.2rem; }
  .org-block img { height: 50px; display: block; margin-bottom: .3rem; }
  .org-name { font-size: 1.3rem; font-weight: bold; color: var(--brand, #2563eb); }
  .org-sub  { font-size: .85rem; color: #555; margin-top: .15rem; }
  .receipt-meta { text-align: right; font-size: .85rem; color: #555; }
  .receipt-meta strong { display: block; font-size: 1rem; color: #111; }
  .donor-block { margin-bottom: 1.2rem; }
  .donor-block h2 { font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; color: #777; margin-bottom: .3rem; }
  .donor-block p { line-height: 1.5; }
  .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 1.2rem; }
  .detail-table th { text-align: left; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: #555; padding: .25rem .5rem; border-bottom: 1px solid #ddd; }
  .detail-table td { padding: .35rem .5rem; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
  .detail-table tfoot td { border-top: 2px solid #111; border-bottom: none; font-weight: bold; }
  .amount-col { text-align: right; font-variant-numeric: tabular-nums; }
  .total-row td { padding-top: .5rem; }
  .disclosure { font-size: .78rem; color: #555; border-top: 1px solid #ddd; padding-top: .7rem; margin-top: .5rem; line-height: 1.55; }
  .sig-block { margin-top: 2rem; display: flex; gap: 3rem; }
  .sig-line { border-top: 1px solid #888; padding-top: .2rem; font-size: .8rem; color: #555; width: 200px; }
  @media print {
    .no-print { display: none !important; }
    body { padding: .5in; }
    a { text-decoration: none; color: inherit; }
  }
</style>
</head>
<body>
<?= $content ?>
</body>
</html>
