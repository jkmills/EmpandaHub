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
    'Controller', 'Model', 'Mailer', 'Router', 'AuditLog',
] as $class) {
    require ROOT . '/core/' . $class . '.php';
}

// Module models (loaded so controllers can reference them)
$moduleFiles = [
    'modules/crm/controllers/CrmModel.php',
    'modules/membership/controllers/MembershipModel.php',
    'modules/membership/controllers/TierModel.php',
    'modules/donors/controllers/DonorModel.php',
    'modules/volunteers/controllers/VolunteerModel.php',
    'modules/events/controllers/EventModel.php',
    'modules/grants/controllers/GrantModel.php',
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
$router->get('/crm',                          'CrmController', 'index');
$router->get('/crm/create',                   'CrmController', 'create');
$router->post('/crm',                         'CrmController', 'store');
$router->get('/crm/import',                   'CrmController', 'importForm');
$router->post('/crm/import',                  'CrmController', 'import');
$router->get('/crm/export',                   'CrmController', 'exportCsv');
$router->get('/crm/duplicates',               'CrmController', 'duplicates');
$router->post('/crm/merge',                   'CrmController', 'mergePost');
$router->get('/crm/:id',                      'CrmController', 'show');
$router->get('/crm/:id/edit',                 'CrmController', 'edit');
$router->post('/crm/:id/update',              'CrmController', 'update');
$router->post('/crm/:id/delete',              'CrmController', 'delete');
$router->post('/crm/:id/note',                'CrmController', 'addNote');

// Membership — tiers (must come before :id)
$router->get('/membership',                   'MembershipController', 'index');
$router->get('/membership/tiers',             'MembershipController', 'tierIndex');
$router->get('/membership/tiers/create',      'MembershipController', 'tierCreate');
$router->post('/membership/tiers',            'MembershipController', 'tierStore');
$router->get('/membership/tiers/:id/edit',    'MembershipController', 'tierEdit');
$router->post('/membership/tiers/:id/update', 'MembershipController', 'tierUpdate');
$router->get('/membership/expiring',          'MembershipController', 'expiring');
$router->get('/membership/overdue',           'MembershipController', 'overdue');
$router->get('/membership/create',            'MembershipController', 'create');
$router->post('/membership',                  'MembershipController', 'store');
$router->get('/membership/:id',               'MembershipController', 'show');
$router->get('/membership/:id/edit',          'MembershipController', 'edit');
$router->post('/membership/:id/update',       'MembershipController', 'update');
$router->post('/membership/:id/dues',         'MembershipController', 'addDues');

// Donors — campaigns (before :id)
$router->get('/donors/campaigns',                 'DonorController', 'campaigns');
$router->get('/donors/campaigns/create',          'DonorController', 'campaignCreate');
$router->post('/donors/campaigns',                'DonorController', 'campaignStore');
$router->get('/donors/campaigns/:id/edit',        'DonorController', 'campaignEdit');
$router->post('/donors/campaigns/:id/update',     'DonorController', 'campaignUpdate');
$router->get('/donors/lybunt',                    'DonorController', 'lybunt');
$router->get('/donors/sybunt',                    'DonorController', 'sybunt');
$router->get('/donors/batch-receipt',             'DonorController', 'batchReceipt');
$router->get('/donors/create',                    'DonorController', 'create');
$router->post('/donors',                          'DonorController', 'store');
$router->get('/donors',                           'DonorController', 'index');
$router->get('/donors/:id',                       'DonorController', 'show');
$router->get('/donors/:id/edit',                  'DonorController', 'edit');
$router->post('/donors/:id/update',               'DonorController', 'update');

// Volunteers — shifts (before :id)
$router->get('/volunteers/shifts',                'VolunteerController', 'shifts');
$router->get('/volunteers/shifts/create',         'VolunteerController', 'shiftCreate');
$router->post('/volunteers/shifts',               'VolunteerController', 'shiftStore');
$router->post('/volunteers/shifts/:id/complete',  'VolunteerController', 'shiftComplete');
$router->post('/volunteers/hours/:hours_id/approve', 'VolunteerController', 'approveHours');
$router->post('/volunteers/hours/:hours_id/reject',  'VolunteerController', 'rejectHours');
$router->get('/volunteers/create',                'VolunteerController', 'create');
$router->post('/volunteers',                      'VolunteerController', 'store');
$router->get('/volunteers',                       'VolunteerController', 'index');
$router->get('/volunteers/:id',                   'VolunteerController', 'show');
$router->get('/volunteers/:id/edit',              'VolunteerController', 'edit');
$router->post('/volunteers/:id/update',           'VolunteerController', 'update');
$router->post('/volunteers/:id/hours',            'VolunteerController', 'logHours');

// Events
$router->get('/events',                           'EventController', 'index');
$router->get('/events/create',                    'EventController', 'create');
$router->post('/events',                          'EventController', 'store');
$router->get('/events/:id',                       'EventController', 'show');
$router->get('/events/:id/edit',                  'EventController', 'edit');
$router->post('/events/:id/update',               'EventController', 'update');
$router->post('/events/:id/delete',               'EventController', 'delete');
$router->post('/events/:id/register',             'EventController', 'register');
$router->get('/events/:id/checkin',               'EventController', 'checkinView');
$router->post('/events/:id/registrations/:reg_id/checkin', 'EventController', 'checkin');
$router->post('/events/:id/registrations/:reg_id/cancel',  'EventController', 'cancel');

// Grants — funders (before :id)
$router->get('/grants/funders',                   'GrantController', 'funders');
$router->get('/grants/funders/create',            'GrantController', 'funderCreate');
$router->post('/grants/funders',                  'GrantController', 'funderStore');
$router->get('/grants/funders/:id/edit',          'GrantController', 'funderEdit');
$router->post('/grants/funders/:id/update',       'GrantController', 'funderUpdate');
$router->get('/grants/create',                    'GrantController', 'create');
$router->post('/grants',                          'GrantController', 'store');
$router->get('/grants',                           'GrantController', 'index');
$router->get('/grants/:id',                       'GrantController', 'show');
$router->get('/grants/:id/edit',                  'GrantController', 'edit');
$router->post('/grants/:id/update',               'GrantController', 'update');
$router->post('/grants/:id/award',                'GrantController', 'award');
$router->post('/grants/:id/reports',              'GrantController', 'addReport');
$router->post('/grants/:id/reports/:report_id/submit', 'GrantController', 'submitReport');

// Finance
$router->get('/finance',                          'FinanceController', 'index');
$router->get('/finance/export',                   'FinanceController', 'exportCsv');

// Settings
$router->get('/settings',                         'SettingsController', 'index');
$router->post('/settings/org',                    'SettingsController', 'updateOrg');
$router->post('/settings/users/invite',           'SettingsController', 'inviteUser');
$router->post('/settings/users/:id/deactivate',   'SettingsController', 'deactivateUser');
$router->post('/settings/users/:id/role',         'SettingsController', 'updateRole');

// Dispatch
$uri    = $_GET['route'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$router->dispatch($uri, $method);
