<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums;

use Illuminate\Support\ServiceProvider;
use ZeroBoiler\Enums\Console\Commands\InspectEnumCommand;
use ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand;

final class EnumsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('zeroboiler.enum', function () {
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
