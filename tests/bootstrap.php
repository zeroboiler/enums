<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test bootstrap for ZeroBoiler Enums package
|--------------------------------------------------------------------------
*/

// Simple autoloader for the package (for standalone testing)
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'ZeroBoiler\Enums\\' => __DIR__.'/../src/',
        'ZeroBoiler\Enums\Tests\\' => __DIR__.'/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) === 0) {
            $relative = substr($class, $len);
            $file = $baseDir.str_replace('\\', '/', $relative).'.php';

            if (file_exists($file)) {
                require $file;
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Translation helper polyfill for standalone tests
|--------------------------------------------------------------------------
| In a Laravel app, __() is provided by the framework. In standalone tests,
| we provide a simple fallback that returns the translation key as-is.
*/
if (! function_exists('__')) {
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        return $key;
    }
}
