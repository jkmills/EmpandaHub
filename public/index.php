<?php
declare(strict_types=1);

// Bootstrap
define('ROOT', dirname(__DIR__));
$config = ROOT . '/config/config.php';
if (!file_exists($config)) {
    header('Location: /install/install.php');
    exit;
}
require $config;

// Composer autoload
if (file_exists(ROOT . '/vendor/autoload.php')) {
    require ROOT . '/vendor/autoload.php';
}

// Core classes
foreach ([
    'Database', 'Flash', 'Csrf', 'Auth',
    'Controller', 'Model', 'Mailer', 'Router', 'AuditLog', 'Updater', 'EngagementScore',
] as $class) {
    require ROOT . '/core/' . $class . '.php';
}
require ROOT . '/core/helpers.php';

// Module models (loaded so controllers can reference them)
$moduleFiles = [
    'modules/crm/controllers/CrmModel.php',
    'modules/membership/controllers/MembershipModel.php',
    'modules/membership/controllers/TierModel.php',
    'modules/donors/controllers/DonorModel.php',
    'modules/volunteers/controllers/VolunteerModel.php',
    'modules/events/controllers/EventModel.php',
    'modules/grants/controllers/GrantModel.php',
    'modules/data/controllers/DataModel.php',
    'modules/documents/controllers/DocumentModel.php',
];
foreach ($moduleFiles as $f) {
    require ROOT . '/' . $f;
}

// Start session
Auth::start();

// Routes
$router = new Router();

// Auth
$router->get('/auth/login',    'AuthController', 'loginForm');
$router->post('/auth/login',   'AuthController', 'login');
$router->get('/auth/logout',   'AuthController', 'logout');

// Dashboard
$router->get('/',              'DashboardController', 'index');
$router->get('/dashboard',     'DashboardController', 'index');

// CRM
$router->module('crm', function ($r) {
    $r->get('/crm',                          'CrmController', 'index');
    $r->get('/crm/create',                   'CrmController', 'create');
    $r->post('/crm',                         'CrmController', 'store');
    $r->get('/crm/import',                   'CrmController', 'importForm');
    $r->post('/crm/import',                  'CrmController', 'import');
    $r->get('/crm/export',                   'CrmController', 'exportCsv');
    $r->get('/crm/duplicates',               'CrmController', 'duplicates');
    $r->post('/crm/merge',                   'CrmController', 'mergePost');
    $r->get('/crm/:id',                      'CrmController', 'show');
    $r->get('/crm/:id/edit',                 'CrmController', 'edit');
    $r->post('/crm/:id/update',              'CrmController', 'update');
    $r->post('/crm/:id/delete',              'CrmController', 'delete');
    $r->post('/crm/:id/note',                'CrmController', 'addNote');
    $r->post('/crm/:id/board-position',              'CrmController', 'addBoardPosition');
    $r->post('/crm/:id/board-position/:posId/end',   'CrmController', 'endBoardPosition');
});

// Membership — tiers (must come before :id)
$router->module('membership', function ($r) {
    $r->get('/membership',                   'MembershipController', 'index');
    $r->get('/membership/tiers',             'MembershipController', 'tierIndex');
    $r->get('/membership/tiers/create',      'MembershipController', 'tierCreate');
    $r->post('/membership/tiers',            'MembershipController', 'tierStore');
    $r->get('/membership/tiers/:id/edit',    'MembershipController', 'tierEdit');
    $r->post('/membership/tiers/:id/update', 'MembershipController', 'tierUpdate');
    $r->get('/membership/dues',              'MembershipController', 'duesIndex');
    $r->get('/membership/dues/export',       'MembershipController', 'duesExport');
    $r->get('/membership/expiring',          'MembershipController', 'expiring');
    $r->get('/membership/overdue',           'MembershipController', 'overdue');
    $r->get('/membership/create',            'MembershipController', 'create');
    $r->post('/membership',                  'MembershipController', 'store');
    $r->get('/membership/:id',               'MembershipController', 'show');
    $r->get('/membership/:id/edit',          'MembershipController', 'edit');
    $r->post('/membership/:id/update',       'MembershipController', 'update');
    $r->post('/membership/:id/dues',              'MembershipController', 'addDues');
    $r->get('/membership/:id/dues/:dueId/edit',   'MembershipController', 'editDues');
    $r->post('/membership/:id/dues/:dueId/update','MembershipController', 'updateDues');
});

// Donors — campaigns (before :id)
$router->module('donors', function ($r) {
    $r->get('/donors/campaigns',                 'DonorController', 'campaigns');
    $r->get('/donors/campaigns/create',          'DonorController', 'campaignCreate');
    $r->post('/donors/campaigns',                'DonorController', 'campaignStore');
    $r->get('/donors/campaigns/:id/edit',        'DonorController', 'campaignEdit');
    $r->post('/donors/campaigns/:id/update',     'DonorController', 'campaignUpdate');
    $r->get('/donors/lybunt',                    'DonorController', 'lybunt');
    $r->get('/donors/sybunt',                    'DonorController', 'sybunt');
    $r->get('/donors/batch-receipt',             'DonorController', 'batchReceipt');
    $r->get('/donors/create',                    'DonorController', 'create');
    $r->post('/donors',                          'DonorController', 'store');
    $r->get('/donors',                           'DonorController', 'index');
    $r->get('/donors/receipt',                   'DonorController', 'receiptForm');
    $r->get('/donors/receipt/print',             'DonorController', 'receiptSummary');
    $r->get('/donors/:id',                       'DonorController', 'show');
    $r->get('/donors/:id/edit',                  'DonorController', 'edit');
    $r->post('/donors/:id/update',               'DonorController', 'update');
    $r->get('/donors/:id/receipt',               'DonorController', 'singleReceipt');
});

// Volunteers — shifts (before :id)
$router->module('volunteers', function ($r) {
    $r->get('/volunteers/shifts',                'VolunteerController', 'shifts');
    $r->get('/volunteers/shifts/create',         'VolunteerController', 'shiftCreate');
    $r->post('/volunteers/shifts',               'VolunteerController', 'shiftStore');
    $r->post('/volunteers/shifts/:id/complete',  'VolunteerController', 'shiftComplete');
    $r->post('/volunteers/hours/:hours_id/approve', 'VolunteerController', 'approveHours');
    $r->post('/volunteers/hours/:hours_id/reject',  'VolunteerController', 'rejectHours');
    $r->get('/volunteers/create',                'VolunteerController', 'create');
    $r->post('/volunteers',                      'VolunteerController', 'store');
    $r->get('/volunteers',                       'VolunteerController', 'index');
    $r->get('/volunteers/:id',                   'VolunteerController', 'show');
    $r->get('/volunteers/:id/edit',              'VolunteerController', 'edit');
    $r->post('/volunteers/:id/update',           'VolunteerController', 'update');
    $r->post('/volunteers/:id/hours',            'VolunteerController', 'logHours');
});

// Events
$router->module('events', function ($r) {
    $r->get('/events',                           'EventController', 'index');
    $r->get('/events/create',                    'EventController', 'create');
    $r->post('/events',                          'EventController', 'store');
    $r->get('/events/:id',                       'EventController', 'show');
    $r->get('/events/:id/edit',                  'EventController', 'edit');
    $r->post('/events/:id/update',               'EventController', 'update');
    $r->post('/events/:id/delete',               'EventController', 'delete');
    $r->post('/events/:id/register',             'EventController', 'register');
    $r->get('/events/:id/checkin',               'EventController', 'checkinView');
    $r->post('/events/:id/registrations/:reg_id/checkin', 'EventController', 'checkin');
    $r->post('/events/:id/registrations/:reg_id/cancel',  'EventController', 'cancel');
});

// Grants — funders (before :id)
$router->module('grants', function ($r) {
    $r->get('/grants/funders',                   'GrantController', 'funders');
    $r->get('/grants/funders/create',            'GrantController', 'funderCreate');
    $r->post('/grants/funders',                  'GrantController', 'funderStore');
    $r->get('/grants/funders/:id/edit',          'GrantController', 'funderEdit');
    $r->post('/grants/funders/:id/update',       'GrantController', 'funderUpdate');
    $r->get('/grants/create',                    'GrantController', 'create');
    $r->post('/grants',                          'GrantController', 'store');
    $r->get('/grants',                           'GrantController', 'index');
    $r->get('/grants/:id',                       'GrantController', 'show');
    $r->get('/grants/:id/edit',                  'GrantController', 'edit');
    $r->post('/grants/:id/update',               'GrantController', 'update');
    $r->post('/grants/:id/award',                'GrantController', 'award');
    $r->post('/grants/:id/reports',              'GrantController', 'addReport');
    $r->post('/grants/:id/reports/:report_id/submit', 'GrantController', 'submitReport');
});

// Documents
$router->module('documents', function ($r) {
    $r->get('/documents',                                       'DocumentController', 'index');
    $r->get('/documents/create',                               'DocumentController', 'create');
    $r->post('/documents',                                     'DocumentController', 'store');
    $r->get('/documents/:id',                                  'DocumentController', 'show');
    $r->post('/documents/:id/version',                         'DocumentController', 'newVersion');
    $r->post('/documents/:id/share',                           'DocumentController', 'share');
    $r->post('/documents/:id/share/:linkId/revoke',            'DocumentController', 'revokeShareLink');
    $r->get('/documents/:id/download',                         'DocumentController', 'download');
    $r->post('/documents/:id/delete',                          'DocumentController', 'delete');
});

// Public document share links — no auth, no module gate
$router->get('/docs/:token',                                   'DocumentController', 'publicDownload');

// Finance
$router->module('finance', function ($r) {
    $r->get('/finance',        'FinanceController', 'index');
    $r->get('/finance/export', 'FinanceController', 'exportCsv');
});

// Data Management
$router->get('/data',                             'DataController', 'index');
$router->post('/data/backup',                     'DataController', 'backup');
$router->post('/data/wipe',                       'DataController', 'wipe');
$router->post('/data/restore',                    'DataController', 'restore');

// Settings
$router->get('/settings',                         'SettingsController', 'index');
$router->post('/settings/org',                    'SettingsController', 'updateOrg');
$router->post('/settings/users/invite',           'SettingsController', 'inviteUser');
$router->post('/settings/users/:id/deactivate',   'SettingsController', 'deactivateUser');
$router->post('/settings/users/:id/role',         'SettingsController', 'updateRole');
$router->get('/settings/export',                  'SettingsController', 'export');
$router->get('/settings/import',                  'SettingsController', 'importForm');
$router->post('/settings/import',                 'SettingsController', 'importRestore');
$router->get('/settings/updates',                 'SettingsController', 'updates');
$router->post('/settings/updates/check',          'SettingsController', 'checkUpdate');
$router->post('/settings/updates/migrate',        'SettingsController', 'runMigrations');
$router->post('/settings/updates/upgrade',            'SettingsController', 'upgradePerform');
$router->get('/settings/updates/upgrade/backup',      'SettingsController', 'downloadUpgradeBackup');
$router->post('/settings/updates/upgrade/clear-lock', 'SettingsController', 'clearUpgradeLock');

// Dispatch
$uri    = $_GET['route'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$router->dispatch($uri, $method);
