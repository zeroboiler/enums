<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums;

use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestTerminated;
use ZeroBoiler\Enums\Console\Commands\InspectEnumCommand;
use ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand;

final class EnumsServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton('zeroboiler.enum', fn (): EnumManager => new EnumManager);

        // Register EnumCache in the container so it can be resolved and managed.
        $this->app->singleton(EnumCache::class, fn (): EnumCache => EnumCache::getInstance());
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeEnumTestCommand::class,
                InspectEnumCommand::class,
            ]);
        }

        $this->registerCacheFlushing();
    }

    /**
     * Flush the enum metadata cache at the end of each request so that
     * long-lived processes (Octane, Swoole, RoadRunner) don't serve
     * stale metadata across requests.
     */
    private function registerCacheFlushing(): void
    {
        // Octane fires this event after each request is served.
        if (class_exists(RequestTerminated::class)) {
            $this->app['events']->listen(
                RequestTerminated::class,
                static fn () => EnumCache::flush(),
            );

            return;
        }

        // Swoole / generic long-lived process fallback: flush when the
        // application is terminating.
        $this->app->terminating(static fn () => EnumCache::flush());
    }
}
