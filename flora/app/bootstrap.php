<?php
declare(strict_types=1);
define('APP_ROOT', dirname(__DIR__));
date_default_timezone_set('UTC');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/controllers/HomeController.php';
