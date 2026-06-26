<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Console\Commands;

use Illuminate\Console\Command;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ReflectionEnum;

/**
 * Generate Pest tests for an enum class.
 *
 * Usage:
 *   php artisan zeroboiler:enum-test "App\Enums\UserStatus"
 *   php artisan zeroboiler:enum-test "App\Enums\UserStatus" --dir=tests/Unit/Enums
 */
final class MakeEnumTestCommand extends Command
{
    protected $signature = 'zeroboiler:enum-test {class : The enum class FQN} {--dir= : Output directory}';

    protected $description = 'Generate Pest tests for a NovaForge smart enum';

    public function handle(): int
    {
        $enumClass = (string) $this->argument('class');

        if (!enum_exists($enumClass)) {
            $this->error("Enum class '{$enumClass}' not found.");

            return self::FAILURE;
        }

        $reflection = new ReflectionEnum($enumClass);
        $shortName  = $reflection->getShortName();

        $defaultDir = base_path('tests/Unit/Enums');
        $dir        = (string) ($this->option('dir') ?? $defaultDir);
        $path       = rtrim($dir, '/') . "/{$shortName}Test.php";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path) && !$this->confirm("File {$path} already exists. Overwrite?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $content  = EnumTestGenerator::generate($enumClass);
        $relative = str_replace(base_path() . '/', '', $path);

        file_put_contents($path, $content);

        $this->info("Generated: {$relative}");

        return self::SUCCESS;
    }
}
