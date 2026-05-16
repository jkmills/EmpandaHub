<?php $user = Auth::user(); $modules = $orgModules ?? []; ?>
<button class="menu-toggle" aria-label="Toggle navigation">&#9776;</button>
<nav class="sidebar">
    <div class="sidebar-brand">
        <?php if (!empty($orgLogo)): ?>
            <img src="<?= htmlspecialchars(UPLOAD_URL . $orgLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="sidebar-logo">
        <?php endif; ?>
        <span class="sidebar-name"><?= htmlspecialchars($orgName ?? APP_NAME, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <ul class="nav-list">
        <li><a href="<?= APP_URL ?>/dashboard" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : '' ?>">Dashboard</a></li>

        <?php if (empty($modules['crm']) || $modules['crm']): ?>
        <li><a href="<?= APP_URL ?>/crm" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/crm') ? 'active' : '' ?>">CRM</a></li>
        <?php endif; ?>

        <?php if (empty($modules['membership']) || $modules['membership']): ?>
        <li><a href="<?= APP_URL ?>/membership" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/membership') ? 'active' : '' ?>">Membership</a></li>
        <?php endif; ?>

        <?php if (empty($modules['donors']) || $modules['donors']): ?>
        <li><a href="<?= APP_URL ?>/donors" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/donors') ? 'active' : '' ?>">Donors</a></li>
        <?php endif; ?>

        <?php if (empty($modules['volunteers']) || $modules['volunteers']): ?>
        <li><a href="<?= APP_URL ?>/volunteers" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/volunteers') ? 'active' : '' ?>">Volunteers</a></li>
        <?php endif; ?>

        <?php if (empty($modules['events']) || $modules['events']): ?>
        <li><a href="<?= APP_URL ?>/events" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/events') ? 'active' : '' ?>">Events</a></li>
        <?php endif; ?>

        <?php if (empty($modules['grants']) || $modules['grants']): ?>
        <li><a href="<?= APP_URL ?>/grants" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/grants') ? 'active' : '' ?>">Grants</a></li>
        <?php endif; ?>

        <?php if (empty($modules['finance']) || $modules['finance']): ?>
        <li><a href="<?= APP_URL ?>/finance" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/finance') ? 'active' : '' ?>">Finance</a></li>
        <?php endif; ?>

        <?php if (Auth::hasRole('super_admin', 'admin')): ?>
        <li><a href="<?= APP_URL ?>/settings" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/settings') ? 'active' : '' ?>">Settings</a></li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <span class="sidebar-user"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <a href="<?= APP_URL ?>/auth/logout" class="nav-logout">Logout</a>
    </div>
</nav>
<main class="main-content">
