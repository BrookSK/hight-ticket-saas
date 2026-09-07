<?php

declare(strict_types=1);

namespace App\Providers;

/**
 * Lightweight PSR-4 autoloader.
 *
 * Works without Composer so the project can run on any PHP 8.3+ host. If the
 * Composer autoloader is present it is preferred; otherwise this fallback maps
 * PSR-4 namespaces to directories.
 */
final class Autoloader
{
    /** @var array<string, string> Map of namespace prefix => base directory. */
    private array $prefixes = [];

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Register a PSR-4 namespace prefix and its base directory.
     */
    public function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;
        $this->prefixes[$prefix] = $baseDir;
    }

    /**
     * Attempt to load the class file for a fully-qualified class name.
     */
    public function loadClass(string $class): bool
    {
        foreach ($this->prefixes as $prefix => $baseDir) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

            if (is_file($file)) {
                require $file;

                return true;
            }
        }

        return false;
    }
}
