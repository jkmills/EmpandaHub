<?php
$orgColor = '#2563eb';
require ROOT . '/views/layout/header.php';
?>
<body class="auth-layout">
<div class="auth-box">
    <div class="auth-logo"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></div>

    <?php if (!empty($errors['_global'])): ?>
    <div class="flash flash-error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php foreach (Flash::get() as $msg): ?>
    <div class="flash flash-<?= htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= APP_URL ?>/auth/login">
        <?= Csrf::field() ?>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= $email ?? '' ?>" autocomplete="email" required>
            <?php if (!empty($errors['email'])): ?>
            <p class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <?php if (!empty($errors['password'])): ?>
            <p class="field-error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-100" style="margin-top:.5rem">Sign In</button>
    </form>
</div>
<?php require ROOT . '/views/layout/footer.php'; ?>
