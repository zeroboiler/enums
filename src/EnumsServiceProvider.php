<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums;

use Illuminate\Support\ServiceProvider;
use ZeroBoiler\Enums\Console\Commands\InspectEnumCommand;
use ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand;

final class EnumsServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton('zeroboiler.enum', fn (): EnumManager => new EnumManager);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeEnumTestCommand::class,
                InspectEnumCommand::class,
            ]);
        }

        // Flush EnumCache between requests when running under
        // Laravel Octane / Swoole / RoadRunner to prevent stale
        // metadata from persisting across requests in the
        // long-lived worker process.
        if ($this->app->bound('events')) {
            $this->app['events']->listen('octane.flush', static function (): void {
                EnumCache::flush();
            });

            // Also flush on request reset for generic long-lived setups
            $this->app['events']->listen('request.reset', static function (): void {
                EnumCache::flush();
            });
        }
    }
}
