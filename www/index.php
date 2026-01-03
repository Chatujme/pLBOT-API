<?php

declare(strict_types=1);

use Apitte\Core\Application\IApplication;

// Strip base path from REQUEST_URI for Apitte routing
$basePath = '/pLBOT-API/www';
if (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], $basePath)) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], strlen($basePath)) ?: '/';
}

$container = require __DIR__ . '/../app/bootstrap.php';

// Run Apitte Application
$application = $container->getByType(IApplication::class);
$application->run();
