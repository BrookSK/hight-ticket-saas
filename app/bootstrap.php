<?php

declare(strict_types=1);

/**
 * Application bootstrap.
 *
 * Registers the autoloader (preferring Composer's when available), boots the
 * kernel and returns it. Entry points (public/index.php, CLI scripts) require
 * this file to obtain a fully-booted application.
 */

$rootPath = dirname(__DIR__);

// Prefer Composer's autoloader when installed; otherwise use the built-in one.
$composerAutoload = $rootPath . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    require $rootPath . '/app/providers/Autoloader.php';

    $autoloader = new App\Providers\Autoloader();
    $autoloader->addNamespace('App', $rootPath . '/app');
    $autoloader->register();

    // Load global helper functions (Composer would do this via "files").
    require $rootPath . '/app/helpers/functions.php';
}

$kernel = new App\Core\Kernel($rootPath);
$kernel->boot();

return $kernel;
