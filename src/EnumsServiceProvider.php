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
     * Register artisan commands for enum inspection and test generation.
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
    }
}
