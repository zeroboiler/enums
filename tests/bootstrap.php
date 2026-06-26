<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test bootstrap for NovaForge Enums package
|--------------------------------------------------------------------------
*/

// Simple autoloader for the package (for standalone testing)
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'NovaForge\\Enums\\' => __DIR__ . '/../src/',
        'NovaForge\\Enums\\Tests\\' => __DIR__ . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) === 0) {
            $relative = substr($class, $len);
            $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        }
    }
});
