<?php /* No auth required — standalone page */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Link Unavailable</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:2.5rem 3rem;text-align:center;max-width:420px;width:100%}
h1{font-size:1.5rem;margin-bottom:.75rem;color:#dc2626}
p{color:#64748b;line-height:1.6}
</style>
</head>
<body>
<div class="card">
    <h1>Link Unavailable</h1>
    <?php if (($reason ?? '') === 'expired'): ?>
    <p>This share link has expired. Please contact the person who shared it with you to request a new link.</p>
    <?php else: ?>
    <p>This share link is no longer valid. It may have been revoked or the URL may be incorrect.</p>
    <?php endif; ?>
</div>
</body>
</html>
