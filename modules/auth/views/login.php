<?php
$orgColor   = '#2563eb';
$body_class = 'auth-layout';
require ROOT . '/views/layout/header.php';
?>
<div class="auth-container">
    <div class="auth-brand">
        <div class="auth-brand-logo"><?= strtoupper(substr(APP_NAME, 0, 1)) ?></div>
        <span class="auth-brand-name"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="auth-brand-tagline">Nonprofit Management Platform</span>
    </div>

    <div class="auth-box">
        <h2 class="auth-title">Welcome back</h2>
        <p class="auth-subtitle">Sign in to your organization's account</p>

        <?php if (!empty($errors['_global'])): ?>
        <div class="flash flash-error" style="margin-bottom:1.25rem"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php foreach (Flash::get() as $msg): ?>
        <div class="flash flash-<?= htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8') ?>" style="margin-bottom:1.25rem">
            <?= htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endforeach; ?>

        <form method="post" action="<?= APP_URL ?>/auth/login">
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       autocomplete="email" placeholder="you@organization.org" required>
                <?php if (!empty($errors['email'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       autocomplete="current-password" placeholder="••••••••" required>
                <?php if (!empty($errors['password'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg" style="margin-top:.25rem">
                Sign In
            </button>
        </form>
    </div>

    <p style="text-align:center;margin-top:1.5rem;font-size:.75rem;color:rgba(255,255,255,.35)">
        &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
    </p>
</div>
<?php require ROOT . '/views/layout/footer.php'; ?>
