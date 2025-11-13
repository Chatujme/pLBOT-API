<?php

declare(strict_types=1);

use Apitte\Core\Application\IApplication;

$container = require __DIR__ . '/../app/bootstrap.php';

// Run Apitte Application
$application = $container->getByType(IApplication::class);
$application->run();
