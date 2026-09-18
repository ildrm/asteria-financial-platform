<?php

declare(strict_types=1);

/**
 * Bundled dependency-free PSR-4 loader for normal WordPress ZIP installs.
 * Composer's generated loader is preferred in development when available.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Asteria\\FinancialPlatform\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_readable($path)) {
        require $path;
    }
});
