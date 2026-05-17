<?php
// Copy to config.php and fill in values. Never commit config.php.
// APP_VERSION is read from the VERSION file at runtime — do not define it here.

define('DB_HOST',     'localhost');
define('DB_NAME',     'empandahub');
define('DB_USER',     'root');
define('DB_PASS',     'secret');
define('DB_CHARSET',  'utf8mb4');

define('APP_NAME',    'EmpandaHub');
define('APP_URL',     'http://localhost:8080');
define('APP_ENV',     'development'); // development | production

define('SESSION_LIFETIME', 7200); // seconds
define('UPLOAD_DIR',  __DIR__ . '/../public/uploads/');
define('UPLOAD_URL',  APP_URL . '/uploads/');
