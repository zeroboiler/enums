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
     * Includes the actual value in the message for easier debugging,
     * rather than only the PHP type name.
     *
     * @param  class-string  $enumClass
     * @param  int|string|null  $value  The invalid backed value or case name
     */
    public static function value(string $enumClass, int|string|null $value): self
    {
        $display = $value === null ? 'null' : (string) $value;

        return new self("Value [{$display}] is not a valid case of [{$enumClass}].");
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
