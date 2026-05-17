<?php
$user    = Auth::user();
$modules = $orgModules ?? [];
$uri     = $_SERVER['REQUEST_URI'] ?? '/';

// Generate user initials
$nameParts = explode(' ', trim($user['name'] ?? 'U'));
$initials  = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

function navSvg(string $paths, string $extra = ''): string {
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>'
         . $paths . '</svg>';
}

$icons = [
    'dashboard'  => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
    'crm'        => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.74"/>',
    'membership' => '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
    'donors'     => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l8.84 8.85 8.84-8.85a5.5 5.5 0 0 0 0-7.78z"/>',
    'volunteers' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>',
    'events'     => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    'grants'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>',
    'finance'    => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/>',
    'settings'   => '<circle cx="12" cy="12" r="3"/><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
    'data'       => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
];

function navLink(string $href, string $label, string $iconHtml, string $currentUri, array $prefixes = []): string {
    $prefixes = $prefixes ?: [$href];
    $active   = false;
    foreach ($prefixes as $p) {
        if ($p === '/' || $p === '/dashboard')
            $active = $active || ($currentUri === $p || str_starts_with($currentUri, '/dashboard'));
        else
            $active = $active || str_starts_with($currentUri, $p);
    }
    $cls = 'nav-item' . ($active ? ' active' : '');
    return '<li><a href="' . APP_URL . $href . '" class="' . $cls . '">' . $iconHtml . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></li>';
}

$orgLogoHtml = '';
if (!empty($orgLogo)) {
    $orgLogoHtml = '<img src="' . htmlspecialchars(UPLOAD_URL . $orgLogo, ENT_QUOTES, 'UTF-8') . '" alt="Logo" class="sidebar-logo">';
} else {
    $firstLetter = strtoupper(substr($orgName ?? 'E', 0, 1));
    $orgLogoHtml = '<div class="sidebar-logo-placeholder">' . htmlspecialchars($firstLetter, ENT_QUOTES, 'UTF-8') . '</div>';
}
?>
<?php
$_sStyle = $orgSidebarStyle ?? 'dark';
$_sClass = 'sidebar' . ($_sStyle !== 'dark' ? ' sidebar--' . $_sStyle : '');
?>
<nav class="<?= htmlspecialchars($_sClass, ENT_QUOTES, 'UTF-8') ?>" id="sidebar">
    <div class="sidebar-brand">
        <?= $orgLogoHtml ?>
        <span class="sidebar-name"><?= htmlspecialchars($orgName ?? APP_NAME, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div class="nav-section">
        <span class="nav-section-label">Overview</span>
        <ul class="nav-list">
            <?= navLink('/dashboard', 'Dashboard', navSvg($icons['dashboard']), $uri, ['/dashboard', '/']) ?>
        </ul>
    </div>

    <?php
    $moduleLinks = [];
    if (!isset($modules['crm'])        || $modules['crm'])        $moduleLinks[] = navLink('/crm',        'CRM',         navSvg($icons['crm']),        $uri, ['/crm']);
    if (!isset($modules['membership'])  || $modules['membership'])  $moduleLinks[] = navLink('/membership',  'Membership',  navSvg($icons['membership']),  $uri, ['/membership']);
    if (!isset($modules['donors'])      || $modules['donors'])      $moduleLinks[] = navLink('/donors',      'Donors',      navSvg($icons['donors']),      $uri, ['/donors']);
    if (!isset($modules['volunteers'])  || $modules['volunteers'])  $moduleLinks[] = navLink('/volunteers',  'Volunteers',  navSvg($icons['volunteers']),  $uri, ['/volunteers']);
    if (!isset($modules['events'])      || $modules['events'])      $moduleLinks[] = navLink('/events',      'Events',      navSvg($icons['events']),      $uri, ['/events']);
    if (!isset($modules['grants'])      || $modules['grants'])      $moduleLinks[] = navLink('/grants',      'Grants',      navSvg($icons['grants']),      $uri, ['/grants']);
    if (!isset($modules['finance'])     || $modules['finance'])     $moduleLinks[] = navLink('/finance',     'Finance',     navSvg($icons['finance']),     $uri, ['/finance']);
    ?>
    <?php if ($moduleLinks): ?>
    <div class="nav-section">
        <span class="nav-section-label">Modules</span>
        <ul class="nav-list">
            <?= implode('', $moduleLinks) ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (Auth::hasRole('super_admin', 'admin')): ?>
    <div class="nav-section">
        <span class="nav-section-label">Admin</span>
        <ul class="nav-list">
            <?= navLink('/settings', 'Settings', navSvg($icons['settings']), $uri, ['/settings']) ?>
            <?= navLink('/data',     'Data',     navSvg($icons['data']),     $uri, ['/data']) ?>
        </ul>
    </div>
    <?php endif; ?>

    <div style="flex:1"></div>

    <div class="sidebar-footer">
        <div class="user-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="sidebar-user-info">
            <span class="sidebar-user-name"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <span class="sidebar-user-role"><?= htmlspecialchars(str_replace('_', ' ', $user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <a href="<?= APP_URL ?>/auth/logout" class="nav-logout" title="Sign out">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>
    </div>
</nav>

<div class="main-wrapper">
<header class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" aria-label="Toggle navigation" id="menuToggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <?php if (!empty($pageTitle)): ?>
        <div class="topbar-divider"></div>
        <span class="topbar-page"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
    <div class="topbar-right">
        <div class="topbar-user">
            <div class="topbar-user-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
            <span class="topbar-user-name"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <a href="<?= APP_URL ?>/auth/logout" class="topbar-logout">Sign out</a>
        </div>
    </div>
</header>
<main class="main-content">
