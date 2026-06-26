<?php

declare(strict_types=1);

namespace NovaForge\Enums\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array forSelect(string $enumClass)
 * @method static array forApi(string $enumClass)
 * @method static mixed tryFromLabel(string $enumClass, string $label)
 */
final class Enum extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'novaforge.enum';
    }
}
