<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Bootstrap\Configurator;

// Enable debug mode for development
$configurator->setDebugMode(true);
$configurator->enableTracy(__DIR__ . '/../errorlog');

$configurator->setTempDirectory(__DIR__ . '/../temp');

// Register RobotLoader - will load all classes from app directory
$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../vendor/others')
	->register();

// Load configuration files
$configurator->addConfig(__DIR__ . '/config/config.neon');

// Load local config if exists
if (file_exists(__DIR__ . '/config/config.local.neon')) {
	$configurator->addConfig(__DIR__ . '/config/config.local.neon');
}

$container = $configurator->createContainer();

return $container;
