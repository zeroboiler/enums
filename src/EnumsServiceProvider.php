<?php

declare(strict_types=1);

namespace NovaForge\Enums;

use Illuminate\Support\ServiceProvider;
use NovaForge\Enums\Console\Commands\InspectEnumCommand;
use NovaForge\Enums\Console\Commands\MakeEnumTestCommand;

final class EnumsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('novaforge.enum', function () {
            return new EnumManager();
        });
    }

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
