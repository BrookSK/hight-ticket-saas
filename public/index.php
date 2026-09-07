<?php

declare(strict_types=1);

/**
 * Front controller.
 *
 * NOTE: This is an initial front controller coherent with the layered
 * architecture. When the official "LRV Web" standard index.php is provided, it
 * must replace this file exactly (never alter its structure).
 *
 * All HTTP requests are routed through here. No business logic lives here.
 */

/** @var App\Core\Kernel $kernel */
$kernel = require dirname(__DIR__) . '/app/bootstrap.php';

$kernel->handle(App\Libraries\Request::fromGlobals());
