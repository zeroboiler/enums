<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums;

use Illuminate\Support\ServiceProvider;
use ZeroBoiler\Enums\Console\Commands\InspectEnumCommand;
use ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand;

/**
 * Laravel service provider for ZeroBoiler Enums.
 *
 * Registers the EnumManager as a singleton and registers artisan commands
 * for enum inspection and test generation.
 *
 * Auto-discovered via Laravel's package discovery — no manual registration needed.
 *
 * @see \ZeroBoiler\Enums\EnumManager
 * @see \ZeroBoiler\Enums\Console\Commands\InspectEnumCommand
 * @see \ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand
 */
final class EnumsServiceProvider extends ServiceProvider
{
    /**
     * Register the EnumManager as a singleton bound to 'zeroboiler.enum'.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton('zeroboiler.enum', fn (): EnumManager => new EnumManager);
    }

    /**
     * Register artisan commands, configure cache TTL for dev environments,
     * and register cache flush listeners for long-lived processes.
     */
    #[\Override]
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeEnumTestCommand::class,
                InspectEnumCommand::class,
            ]);
        }

        $this->configureDevCacheInvalidation();
        $this->registerCacheFlush();
    }

    /**
     * In local/testing environments, enable TTL-based metadata cache
     * invalidation so that changes to enum classes are picked up
     * automatically without needing a manual cache flush.
     *
     * Default TTL: 2 seconds — long enough to benefit from caching
     * within a single request, short enough to detect code changes
     * on the next page load.
     */
    private function configureDevCacheInvalidation(): void
    {
        if ($this->app->environment('local', 'testing')) {
            EnumCache::getInstance()->setTtl(2);
        }
    }

    /**
     * Flush the EnumCache singleton at the end of each request in
     * long-lived processes (Octane, Swoole, RoadRunner).
     *
     * Enum metadata is cached in a process-wide singleton. In standard
     * PHP-FPM the singleton dies with the process at end of every request,
     * so no manual flush is needed. Long-lived runners keep it around,
     * which can cause stale metadata between deployments and unbounded
     * memory growth as more enum classes are resolved.
     *
     * Silently catches BindingResolutionException if the events dispatcher
     * is not available (e.g., in testing environments without full Laravel).
     */
    private function registerCacheFlush(): void
    {
        try {
            /** @var \Illuminate\Contracts\Events\Dispatcher $events */
            $events = $this->app->make('events');
        } catch (\Illuminate\Contracts\Container\BindingResolutionException) {
            return;
        }

        $events->listen('octane.terminate', function (): void {
            EnumCache::flush();
        });

        $events->listen('laravel.flush', function (): void {
            EnumCache::flush();
        });
    }
}
