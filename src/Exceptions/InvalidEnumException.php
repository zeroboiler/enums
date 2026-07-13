<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Exceptions;

use Exception;

final class InvalidEnumException extends Exception
{
    public static function value(string $enumClass, mixed $value): self
    {
        if ($value instanceof \BackedEnum) {
            $descriptor = $value::class.'::'.$value->name.' (value: '.var_export($value->value, true).')';
        } else {
            $descriptor = var_export($value, true).' ('.get_debug_type($value).')';
        }

        $shortName = substr($enumClass, (int) strrpos($enumClass, '\\') + 1);

        return new self("Value [{$descriptor}] is not a valid case of enum [{$shortName}] ({$enumClass}).");
    }
}
