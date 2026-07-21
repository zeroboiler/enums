<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Translation helper polyfill for standalone tests
|--------------------------------------------------------------------------
| In a Laravel app, __() is provided by the framework. In standalone tests
| without a full Laravel bootstrap, we provide a simple fallback.
*/
if (! function_exists('__')) {
    function __(?string $key = null, array $replace = [], ?string $locale = null): string|array|null
    {
        return $key;
    }
}

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
*/

uses()
    ->in(__DIR__);
