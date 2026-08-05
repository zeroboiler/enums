<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Console\Commands;

use BackedEnum;
use Illuminate\Console\Command;
use ReflectionEnum;

/**
 * Inspect and display enum metadata.
 *
 * Usage:
 *   php artisan zeroboiler:enum-inspect "App\Enums\UserStatus"
 */
final class InspectEnumCommand extends Command
{
    #[\Override]
    protected $signature = 'zeroboiler:enum-inspect {class : The enum class FQN}';

    #[\Override]
    protected $description = 'Inspect a ZeroBoiler smart enum — show all metadata in a table';

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

        $this->info("Enum: {$shortName}");
        $this->newLine();

        $rows = [];
        foreach ($enumClass::cases() as $case) {
            $value = $case instanceof BackedEnum ? $case->value : null;

            $rows[] = [
                'Name' => $case->name,
                'Value' => $value ?? '—',
                'Label' => $this->safeCall($case, 'label'),
                'Color' => $this->safeCall($case, 'color'),
                'Icon' => $this->safeCall($case, 'icon') ?? '—',
                'Description' => $this->safeCall($case, 'description') ?? '—',
            ];
        }

        $this->table(['Name', 'Value', 'Label', 'Color', 'Icon', 'Description'], $rows);

        return self::SUCCESS;
    }

    private function safeCall(object $case, string $method): ?string
    {
        if (! method_exists($case, $method)) {
            return null;
        }

        try {
            $result = $case->$method();

            return is_string($result) ? $result : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
