<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Exceptions;

use Exception;

final class InvalidEnumException extends Exception
{
    /**
     * Create an exception for an invalid value lookup.
     *
     * @param  class-string  $enumClass
     */
    public static function value(string $enumClass, mixed $value): self
    {
        $type = get_debug_type($value);

        return new self("Value [{$type}] is not a valid case of [{$enumClass}].");
    }

    /**
     * Create an exception for an invalid case name lookup.
     *
     * @param  class-string  $enumClass
     */
    public static function forName(string $enumClass, string $name): self
    {
        return new self("Case name [{$name}] does not exist on enum [{$enumClass}].");
    }
}
