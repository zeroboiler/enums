<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Console\Commands;

use Illuminate\Console\Command;
use ReflectionEnum;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

/**
 * Generate Pest tests for an enum class.
 *
 * Usage:
 *   php artisan zeroboiler:enum-test "App\Enums\UserStatus"
 *   php artisan zeroboiler:enum-test "App\Enums\UserStatus" --dir=tests/Unit/Enums
 */
final class MakeEnumTestCommand extends Command
{
    protected string $signature = 'zeroboiler:enum-test {class : The enum class FQN} {--dir= : Output directory}';

    protected string $description = 'Generate Pest tests for a ZeroBoiler smart enum';

    public function handle(): int
    {
        /** @var string $enumClass */
        $enumClass = $this->argument('class');

        if (! enum_exists($enumClass)) {
            $this->error("Enum class '{$enumClass}' not found.");

            return self::FAILURE;
        }

        $reflection = new ReflectionEnum($enumClass);
        $shortName = $reflection->getShortName();

        $defaultDir = \function_exists('base_path') ? base_path('tests/Unit/Enums') : getcwd().'/tests/Unit/Enums';
        $dir = (string) ($this->option('dir') ?? $defaultDir);
        $path = rtrim($dir, '/\\')."/{$shortName}Test.php";

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path) && ! $this->confirm("File {$path} already exists. Overwrite?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $content = EnumTestGenerator::generate($enumClass);
        $relative = str_replace(\function_exists('base_path') ? base_path().'/' : getcwd().'/', '', $path);

        file_put_contents($path, $content);

        $this->info("Generated: {$relative}");

        return self::SUCCESS;
    }
}
