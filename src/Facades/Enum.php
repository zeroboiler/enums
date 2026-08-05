<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static list<array{value: string|int, label: string}> forSelect(string $enumClass)
 * @method static list<array{value: string|int, name: string, label: string, description: ?string, color: string, icon: ?string}> forApi(string $enumClass)
 * @method static ?\BackedEnum tryFromLabel(string $enumClass, string $label)
 */
final class Enum extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'zeroboiler.enum';
    }
}
